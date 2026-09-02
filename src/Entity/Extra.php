<?php

namespace App\Entity;

use App\Repository\ExtraRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExtraRepository::class)]
class Extra
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $namePt = '';

    #[ORM\Column(length: 255)]
    private string $nameEn = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $descriptionPt = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $descriptionEn = '';

    #[ORM\Column]
    private float $price = 0.0;

    #[ORM\Column(length: 10)]
    private string $currency = 'BRL';

    #[ORM\Column(length: 50)]
    private string $category = 'outro';

    #[ORM\Column(length: 50)]
    private string $icon = 'star';

    #[ORM\Column]
    private bool $active = true;

    #[ORM\Column]
    private int $minGuests = 1;

    #[ORM\Column(nullable: true)]
    private ?int $maxGuests = null;

    #[ORM\Column(nullable: true)]
    private ?int $leadTimeHours = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getName(string $locale = 'pt'): string { return 'en' === $locale ? $this->nameEn : $this->namePt; }
    public function getDescription(string $locale = 'pt'): string { return 'en' === $locale ? $this->descriptionEn : $this->descriptionPt; }
    public function getNamePt(): string { return $this->namePt; }
    public function setNamePt(string $v): static { $this->namePt = $v; return $this; }
    public function getNameEn(): string { return $this->nameEn; }
    public function setNameEn(string $v): static { $this->nameEn = $v; return $this; }
    public function getDescriptionPt(): string { return $this->descriptionPt; }
    public function setDescriptionPt(string $v): static { $this->descriptionPt = $v; return $this; }
    public function getDescriptionEn(): string { return $this->descriptionEn; }
    public function setDescriptionEn(string $v): static { $this->descriptionEn = $v; return $this; }
    public function getPrice(): float { return $this->price; }
    public function setPrice(float $v): static { $this->price = $v; return $this; }
    public function getCurrency(): string { return $this->currency; }
    public function setCurrency(string $v): static { $this->currency = $v; return $this; }
    public function getCategory(): string { return $this->category; }
    public function setCategory(string $v): static { $this->category = $v; return $this; }
    public function getIcon(): string { return $this->icon; }
    public function setIcon(string $v): static { $this->icon = $v; return $this; }
    public function isActive(): bool { return $this->active; }
    public function setActive(bool $v): static { $this->active = $v; return $this; }
    public function getMinGuests(): int { return $this->minGuests; }
    public function setMinGuests(int $v): static { $this->minGuests = $v; return $this; }
    public function getMaxGuests(): ?int { return $this->maxGuests; }
    public function setMaxGuests(?int $v): static { $this->maxGuests = $v; return $this; }
    public function getLeadTimeHours(): ?int { return $this->leadTimeHours; }
    public function setLeadTimeHours(?int $v): static { $this->leadTimeHours = $v; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function canBeBookedBefore(\DateTimeImmutable $checkInAt, ?\DateTimeImmutable $now = null): bool
    {
        if (null === $this->leadTimeHours || $this->leadTimeHours <= 0) {
            return true;
        }

        $now ??= new \DateTimeImmutable();
        $deadline = $checkInAt->modify('-' . $this->leadTimeHours . ' hours');

        return $now < $deadline;
    }

    public function isAvailableForGuestCount(int $guests): bool
    {
        if ($guests < $this->minGuests) {
            return false;
        }

        return null === $this->maxGuests || $guests <= $this->maxGuests;
    }

    public function isRajaaramExtra(): bool
    {
        return (bool) preg_match('/rajaaram/i', $this->namePt.' '.$this->nameEn);
    }

    public function isBreakfast(): bool
    {
        $haystack = $this->namePt.' '.$this->nameEn;
        if (preg_match('/chef/i', $haystack)) {
            return false;
        }

        return 'coffee' === $this->icon || (bool) preg_match('/café da manhã|breakfast/i', $haystack);
    }

    public function isBreakfastCouple(): bool
    {
        return $this->isBreakfast() && (bool) preg_match('/\b(casal|couple)s?\b/i', $this->namePt.' '.$this->nameEn);
    }

    public function isBreakfastSingle(): bool
    {
        return $this->isBreakfast() && (bool) preg_match('/individual|\bsingle\b/i', $this->namePt.' '.$this->nameEn);
    }

    public function getBreakfastStyle(): ?string
    {
        if (!$this->isBreakfast()) {
            return null;
        }

        $haystack = mb_strtolower($this->namePt.' '.$this->nameEn);
        if (str_contains($haystack, 'gourmet')) {
            return 'gourmet';
        }
        if (str_contains($haystack, 'simples') || preg_match('/\bsimple\b/', $haystack)) {
            return 'simple';
        }

        return 'other';
    }

    public function getBreakfastBaseName(string $locale = 'pt'): string
    {
        $name = $this->getName($locale);

        return trim((string) preg_replace('/\s*\((?:individual|casal|single|couple)s?\)\s*$/i', '', $name));
    }
}
