<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleAuthenticator extends Oauth2Authenticator
{
    public function __construct(private readonly ClientRegistry $clientRegistry, private readonly EntityManagerInterface $entityManager,
                                private readonly string $frontendUrl)
    {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'handle_google_login';
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $client->getAccessToken();
        $googleUser = $client->fetchUserFromToken($accessToken);
        assert($googleUser instanceof GoogleUser);
        $user = $this->handleGoogleLogin($googleUser);

        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier(), fn() => $user)
        );
    }

    private function handleGoogleLogin(GoogleUser $googleUser): UserInterface
    {
        $googleId = $googleUser->getId();
        $email = $googleUser->getEmail();

        $foundUserByGoogle = $this->entityManager->getRepository(User::class)->findOneByGoogleId($googleId);
        if ($foundUserByGoogle) {
            return $foundUserByGoogle;
        }
        $foundUserByEmail = $this->entityManager->getRepository(User::class)->findOneByEmail($email);
        if ($foundUserByEmail) {
            if ($foundUserByEmail->getGoogleId() !== null) {
                throw new AuthenticationException('Este email ya está vinculado a otra cuenta Google');
            }
            $foundUserByEmail->setGoogleId($googleId);
            $this->entityManager->flush();
            return $foundUserByEmail;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setGoogleId($googleId);
        $user->setUsername('user_' . bin2hex(random_bytes(4)));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new RedirectResponse($this->frontendUrl);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new RedirectResponse($this->frontendUrl . '/?error=google_auth_failed');
    }
}
