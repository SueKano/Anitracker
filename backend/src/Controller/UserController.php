<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserSeries;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

class UserController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly UserPasswordHasherInterface $passwordHasher,
                                private readonly ClientRegistry         $clientRegistry, private readonly ValidatorInterface $validator,
                                private readonly TokenStorageInterface  $tokenStorage,
                                #[Target('registrationLimiter')] private readonly RateLimiterFactoryInterface $registrationLimiter,
                                #[Target('passwordChangeLimiter')] private readonly RateLimiterFactoryInterface $passwordChangeLimiter,
                                #[Target('updateUserLimiter')] private readonly RateLimiterFactoryInterface $updateUserLimiter)
    {
    }

    #[Route('/api/createUser', name: 'new_user', methods: ['POST'])]
    public function createUser(Request $request, Security $security): Response
    {
        $data = json_decode($request->getContent(), true);
        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $plainPassword = $data['password'] ?? '';

        if ($error = $this->checkRateLimit($this->registrationLimiter, $request->getClientIp())) return $error;
        if ($error = $this->validatePlainPasswordLength($plainPassword)) return $error;

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                if ($error->getConstraint() instanceof UniqueEntity) {
                    return $this->json(['error' => 'Los datos introducidos no son válidos.'], Response::HTTP_BAD_REQUEST);
                }
            }
            return $this->json(['error' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $security->login($user, 'json_login', 'main');

        return $this->json(['status' => 'ok']);
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(#[CurrentUser] User $user): Response
    {
        return $this->json(['username' => $user->getUserIdentifier()]);
    }

    #[Route('/api/me', name: 'get_current_user', methods: ['GET'])]
    public function getCurrentUser(#[CurrentUser] User $user, #[Autowire('%env(R2_PUBLIC_URL)%')] string $cdnProfileImages): Response
    {
        $profileImage = $user->getProfileImage() ? $cdnProfileImages . '/avatars/' . $user->getProfileImage() : null;

        return new JsonResponse(['username' => $user->getUsername(), 'profileImage' => $profileImage]);
    }

    #[Route('/api/deleteAccount', name: 'delete_user', methods: ['POST'])]
    public function deleteUser(Request $request, #[CurrentUser] User $user): Response
    {
        $this->tokenStorage->setToken(null);
        $request->getSession()->invalidate();
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return new JsonResponse(['status' => 'ok']);
    }

    #[Route('/api/user/updateUser', name: 'update_user', methods: ['POST'])]
    public function updateUser(Request $request, #[CurrentUser] User $user, FilesystemOperator $profileImagesStorage): Response
    {
        $newUsername = trim($request->request->get('username') ?? '');
        $newProfileImage = $request->files->get('newProfileImage');
        if ($error = $this->checkRateLimit($this->updateUserLimiter, $user->getUserIdentifier())) return $error;
        $user->setUsername($newUsername);
        if ($error = $this->validateEntity($user)) return $error;

        if ($newProfileImage) {
            $errors = $this->validator->validate($newProfileImage, [
                new Assert\Image(maxSize: '2M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp'], mimeTypesMessage: 'Sube una imagen válida (JPG, PNG o WebP).'),
            ]);

            if (count($errors) > 0) {
                return new JsonResponse(['error' => (string) $errors], Response::HTTP_BAD_REQUEST);
            }

            $oldKey = $user->getProfileImage();
            if ($oldKey) {
                $profileImagesStorage->delete($oldKey);
            }

            $key = bin2hex(random_bytes(16)) . '.' . $newProfileImage->guessExtension();
            $profileImagesStorage->write($key, $newProfileImage->getContent());
            $user->setProfileImage($key);
        }

        $this->entityManager->flush();

        return $this->json(['status' => 'ok']);
    }

    #[Route('/api/changePassword', name: 'change_password', methods: ['POST'])]
    public function changePassword(Request $request, #[CurrentUser] User $user): Response
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['currentPassword'], $data['newPassword'])) {
            return new JsonResponse(['error' => 'Rellena todos los campos.'], Response::HTTP_BAD_REQUEST);
        }

        if ($error = $this->checkRateLimit($this->passwordChangeLimiter, $user->getUserIdentifier())) return $error;

        if (!$this->passwordHasher->isPasswordValid($user, $data['currentPassword'])) {
            return new JsonResponse(['error' => 'La contraseña actual no es correcta.'], Response::HTTP_FORBIDDEN);
        }

        if ($error = $this->validatePlainPasswordLength($data['newPassword'])) return $error;

        $user->setPassword($this->passwordHasher->hashPassword($user, $data['newPassword']));
        $this->entityManager->flush();

        return $this->json(['status' => 'ok']);
    }
    #[Route('/api/user/getLastUpdates', name: 'get_last_updates', methods: ['GET'])]
    public function getLastUpdatesByUser(#[CurrentUser] User $user): Response
    {
        $lastUpdates = $this->entityManager->getRepository(UserSeries::class)->findByUser($user, ['updatedAt' => 'DESC'], 5);

        return $this->json(['lastUpdates' => $lastUpdates], Response::HTTP_OK, [], ['groups' => ['userProfile:series']]);
    }

    #[Route('/api/auth/google', name: 'handle_google_login_start', methods: ['GET'])]
    public function redirectGoogleLogin(): Response
    {
        return $this->clientRegistry->getClient('google')->redirect(['openid', 'email', 'profile']);
    }

    #[Route('/api/auth/google/handleLogin', name: 'handle_google_login', methods: ['GET'])]
    public function handleGoogleLogin(): Response
    {
        throw new \LogicException('Este endpoint lo maneja el firewall de Symfony.');
    }

    private function validatePlainPasswordLength(string $plainPassword): ?JsonResponse
    {
        if (mb_strlen($plainPassword) < 8) {
            return new JsonResponse(['error' => 'La contraseña debe tener al menos 8 caracteres.'], Response::HTTP_BAD_REQUEST);
        }

        return null;
    }

    private function validateEntity(object $entity): ?JsonResponse
    {
        $errors = $this->validator->validate($entity);
        if (count($errors) > 0) {
            return $this->json(['error' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        return null;
    }

    private function checkRateLimit(RateLimiterFactoryInterface $limiter, string $key): ?JsonResponse
    {
        if (!$limiter->create($key)->consume()->isAccepted()) {
            return new JsonResponse(['error' => 'Demasiados intentos. Inténtalo más tarde.'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        return null;
    }
}
