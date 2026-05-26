<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity()]
class UserEpisodeWatch extends AbstractEntity
{
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private UserInterface $user;

    #[ORM\ManyToOne(targetEntity: Series::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Series $series;

    public function getSeries(): Series
    {
        return $this->series;
    }

    public function setSeries(Series $series): UserEpisodeWatch
    {
        $this->series = $series;
        return $this;
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function setUser(UserInterface $user): UserEpisodeWatch
    {
        $this->user = $user;
        return $this;
    }
}
