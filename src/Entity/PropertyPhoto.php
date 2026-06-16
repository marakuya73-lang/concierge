<?php

namespace App\Entity;

use App\Repository\PropertyPhotoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PropertyPhotoRepository::class)]
class PropertyPhoto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'photos')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Property $property = null;

    #[ORM\Column(length: 255)]
    private string $filename = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $captionPt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $captionEn = null;

    #[ORM\Column]
    private int $sortOrder = 0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getProperty(): ?Property { return $this->property; }
    public function setProperty(?Property $v): static { $this->property = $v; return $this; }
    public function getFilename(): string { return $this->filename; }
    public function setFilename(string $v): static { $this->filename = $v; return $this; }
    public function getCaptionPt(): ?string { return $this->captionPt; }
    public function setCaptionPt(?string $v): static { $this->captionPt = $v; return $this; }
    public function getCaptionEn(): ?string { return $this->captionEn; }
    public function setCaptionEn(?string $v): static { $this->captionEn = $v; return $this; }
    public function getCaption(string $locale = 'pt'): ?string
    {
        return 'en' === $locale ? $this->captionEn : $this->captionPt;
    }
    public function getSortOrder(): int { return $this->sortOrder; }
    public function setSortOrder(int $v): static { $this->sortOrder = $v; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getPublicPath(): string
    {
        return '/uploads/property/'.$this->filename;
    }
}
