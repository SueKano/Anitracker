<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity()]
#[ORM\Table(name: '"users"')]
#[UniqueEntity(fields: ['username'], message: 'Ya existe una cuenta con ese nombre de usuario.')]
#[UniqueEntity(fields: ['email'], message: 'Ya existe una cuenta con ese email.')]
class User extends AbstractEntity implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Column(type:'string', length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 30)]
    private string $username;

    #[Assert\When(expression: 'this.getGoogleId() === null', constraints: [new Assert\NotBlank(message: 'La contraseña es obligatoria')])]
    #[ORM\Column(type:'string', nullable: true)]
    private ?string $password = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $profileImage = null;

    #[ORM\Column(type:'string', length: 60, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email;
    #[ORM\Column(type:'string', unique: true, nullable: true)]
    private ?string $googleId = null;
    #[ORM\OneToMany(targetEntity: UserSeries::class, mappedBy: 'user', cascade: ['remove'], orphanRemoval: true)]
    private Collection $userSeries;
    public function __construct()
    {
        parent::__construct();
        $this->userSeries = new ArrayCollection();
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): User
    {
        $this->email = $email;
        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): User
    {
        $this->googleId = $googleId;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): User
    {
        $this->password = $password;
        return $this;
    }

    public function getProfileImage(): ?string
    {
        return $this->profileImage;
    }

    public function setProfileImage(?string $profileImage): User
    {
        $this->profileImage = $profileImage;
        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): User
    {
        $this->username = $username;
        return $this;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }
}
