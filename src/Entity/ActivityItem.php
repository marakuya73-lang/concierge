<?php

namespace App\Entity;

use App\Repository\ActivityItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityItemRepository::class)]
class ActivityItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'activityItems')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Property $property = null;

    #[ORM\Column(length: 16)]
    private string $icon = '✦';

    #[ORM\Column(length: 255)]
    private string $titlePt = '';

    #[ORM\Column(length: 255)]
    private string $titleEn = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $bodyPt = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $bodyEn = '';

    #[ORM\Column]
    private int $sortOrder = 0;

    #[ORM\Column]
    private bool $active = true;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $linkUrl = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $linkUrl2 = null;

    public function getId(): ?int { return $this->id; }
    public function getProperty(): ?Property { return $this->property; }
    public function setProperty(?Property $v): static { $this->property = $v; return $this; }
    public function getIcon(): string { return $this->icon; }
    public function setIcon(string $v): static { $this->icon = $v; return $this; }
    public function getTitlePt(): string { return $this->titlePt; }
    public function setTitlePt(string $v): static { $this->titlePt = $v; return $this; }
    public function getTitleEn(): string { return $this->titleEn; }
    public function setTitleEn(string $v): static { $this->titleEn = $v; return $this; }
    public function getBodyPt(): string { return $this->bodyPt; }
    public function setBodyPt(string $v): static { $this->bodyPt = $v; return $this; }
    public function getBodyEn(): string { return $this->bodyEn; }
    public function setBodyEn(string $v): static { $this->bodyEn = $v; return $this; }
    public function getSortOrder(): int { return $this->sortOrder; }
    public function setSortOrder(int $v): static { $this->sortOrder = $v; return $this; }
    public function isActive(): bool { return $this->active; }
    public function setActive(bool $v): static { $this->active = $v; return $this; }
    public function getLinkUrl(): ?string { return $this->linkUrl; }
    public function setLinkUrl(?string $v): static { $this->linkUrl = $v ?: null; return $this; }
    public function getLinkUrl2(): ?string { return $this->linkUrl2; }
    public function setLinkUrl2(?string $v): static { $this->linkUrl2 = $v ?: null; return $this; }

    public function getTitle(string $locale = 'pt'): string
    {
        return 'en' === $locale ? $this->titleEn : $this->titlePt;
    }

    public function getBody(string $locale = 'pt'): string
    {
        return 'en' === $locale ? $this->bodyEn : $this->bodyPt;
    }
}
