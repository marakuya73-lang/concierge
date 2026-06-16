<?php

namespace App\Entity;

use App\Repository\HouseRuleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HouseRuleRepository::class)]
class HouseRule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'houseRules')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Property $property = null;

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

    public function getId(): ?int { return $this->id; }
    public function getProperty(): ?Property { return $this->property; }
    public function setProperty(?Property $v): static { $this->property = $v; return $this; }
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

    public function getTitle(string $locale = 'pt'): string
    {
        return 'en' === $locale ? $this->titleEn : $this->titlePt;
    }

    public function getBody(string $locale = 'pt'): string
    {
        return 'en' === $locale ? $this->bodyEn : $this->bodyPt;
    }
}
