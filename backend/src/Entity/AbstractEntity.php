<?php

namespace App\Entity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Uid\UuidV4;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\MappedSuperclass]
class AbstractEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Ignore]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    #[Ignore]
    protected UuidV4 $uuid;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Ignore]
    #[Gedmo\Timestampable(on: 'update')]
    protected \DateTime $updatedAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Ignore]
    #[Gedmo\Timestampable(on: 'create')]
    protected \DateTime $createdAt;

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): AbstractEntity
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): AbstractEntity
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUuid(): UuidV4
    {
        return $this->uuid;
    }

    public function setUuid(UuidV4 $uuid): self
    {
        $this->uuid = $uuid;
        return $this;
    }

    public function __construct()
    {
        $this->uuid = UuidV4::v4();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): AbstractEntity
    {
        $this->id = $id;
        return $this;
    }
}
