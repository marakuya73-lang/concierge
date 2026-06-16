<?php

namespace App\Entity;

use App\Repository\KitchenUtensilRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KitchenUtensilRepository::class)]
class KitchenUtensil
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'kitchenUtensils')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Property $property = null;

    #[ORM\Column(length: 255)]
    private string $namePt = '';

    #[ORM\Column(length: 255)]
    private string $nameEn = '';

    #[ORM\Column(length: 100)]
    private string $categoryPt = '';

    #[ORM\Column(length: 100)]
    private string $categoryEn = '';

    #[ORM\Column]
    private int $sortOrder = 0;

    #[ORM\Column]
    private bool $active = true;

    public function getId(): ?int { return $this->id; }
    public function getProperty(): ?Property { return $this->property; }
    public function setProperty(?Property $v): static { $this->property = $v; return $this; }
    public function getNamePt(): string { return $this->namePt; }
    public function setNamePt(string $v): static { $this->namePt = $v; return $this; }
    public function getNameEn(): string { return $this->nameEn; }
    public function setNameEn(string $v): static { $this->nameEn = $v; return $this; }
    public function getCategoryPt(): string { return $this->categoryPt; }
    public function setCategoryPt(string $v): static { $this->categoryPt = $v; return $this; }
    public function getCategoryEn(): string { return $this->categoryEn; }
    public function setCategoryEn(string $v): static { $this->categoryEn = $v; return $this; }
    public function getSortOrder(): int { return $this->sortOrder; }
    public function setSortOrder(int $v): static { $this->sortOrder = $v; return $this; }
    public function isActive(): bool { return $this->active; }
    public function setActive(bool $v): static { $this->active = $v; return $this; }

    public function getName(string $locale = 'pt'): string
    {
        return 'en' === $locale ? $this->nameEn : $this->namePt;
    }

    public function getCategory(string $locale = 'pt'): string
    {
        return 'en' === $locale ? $this->categoryEn : $this->categoryPt;
    }
}
