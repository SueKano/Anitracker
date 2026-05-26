<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;

#[ORM\Entity]
class UserSeries extends AbstractEntity
{
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Ignore]
    private UserInterface $user;

    #[ORM\ManyToOne(targetEntity: Series::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['recap:userSeries', 'userProfile:series', 'home:userSeries'])]
    private Series $series;

    #[ORM\Column(type:'boolean', length: 70, nullable: false)]
    #[Groups(['home:userSeries', 'detail:userSeries'])]
    private bool $isFavourite = false;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $countEpisodesCompleted = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['userProfile:series', 'home:userSeries', 'detail:userSeries'])]
    private int $lastEpisodeWatchedCount = 0;

    #[Gedmo\Timestampable(on: 'change', field: 'isCompleted', value: true)]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['recap:userSeries'])]
    private ?\DateTime $completedAt = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['userProfile:series', 'home:userSeries','detail:userSeries'])]
    private bool $isCompleted = false;

    public function isCompleted(): bool
    {
        return $this->isCompleted;
    }

    public function setIsCompleted(bool $isCompleted): UserSeries
    {
        $this->isCompleted = $isCompleted;
        return $this;
    }

    public function getCompletedAt(): ?\DateTime
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTime $completedAt): UserSeries
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function getCountEpisodesCompleted(): int
    {
        return $this->countEpisodesCompleted;
    }

    public function setCountEpisodesCompleted(int $countEpisodesCompleted): UserSeries
    {
        $this->countEpisodesCompleted = $countEpisodesCompleted;
        return $this;
    }

    public function isFavourite(): bool
    {
        return $this->isFavourite;
    }

    public function setIsFavourite(bool $isFavourite): UserSeries
    {
        $this->isFavourite = $isFavourite;
        return $this;
    }

    public function getLastEpisodeWatchedCount(): int
    {
        return $this->lastEpisodeWatchedCount;
    }

    public function setLastEpisodeWatchedCount(int $lastEpisodeWatchedCount): UserSeries
    {
        $this->lastEpisodeWatchedCount = $lastEpisodeWatchedCount;
        return $this;
    }


    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function setUser(UserInterface $user): UserSeries
    {
        $this->user = $user;
        return $this;
    }

    public function getSeries(): Series
    {
        return $this->series;
    }

    public function setSeries(Series $series): UserSeries
    {
        $this->series = $series;
        return $this;
    }

}
