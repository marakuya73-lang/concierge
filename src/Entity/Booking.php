<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints\BookingDatesValid;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[UniqueEntity(fields: ['accessCode'], message: 'Este código já está em uso por outra reserva.', groups: ['with_access_code'])]
#[BookingDatesValid]
class Booking
{
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

    public const SOURCE_AIRBNB = 'Airbnb';
    public const SOURCE_SITE = 'Site';
    public const SOURCE_RAJAARAM = 'Rajaaram';
    public const SOURCE_TUCANTO = 'Tucanto';

    /** @return list<string> */
    public static function sourceChoices(): array
    {
        return [
            self::SOURCE_AIRBNB,
            self::SOURCE_SITE,
            self::SOURCE_RAJAARAM,
            self::SOURCE_TUCANTO,
        ];
    }

    public const GUEST_NAME_PENDING = 'Reserva directa';

    public const RAJAARAM_THERAPY_RESET_EXPRESS = 'reset_express';
    public const RAJAARAM_THERAPY_RESET_CEREMONY = 'reset_ceremony';
    public const RAJAARAM_THERAPY_DEEP_DIVE = 'deep_dive';
    public const RAJAARAM_THERAPY_CHAKRA_ALIGNMENT_EXPRESS = 'chakra_alignment_express';

    /** @return array<string, string> */
    public static function rajaaramTherapyChoices(): array
    {
        return [
            'Reset Express (1h30)' => self::RAJAARAM_THERAPY_RESET_EXPRESS,
            'Cerimônia Reset (3h)' => self::RAJAARAM_THERAPY_RESET_CEREMONY,
            'Mergulho Profundo (3h)' => self::RAJAARAM_THERAPY_DEEP_DIVE,
            'Alinhamento dos Chakras Express (5h)' => self::RAJAARAM_THERAPY_CHAKRA_ALIGNMENT_EXPRESS,
        ];
    }

    /** @return array<string, string> */
    public static function rajaaramTherapyLabelsEn(): array
    {
        return [
            self::RAJAARAM_THERAPY_RESET_EXPRESS => 'Reset Express (1h30)',
            self::RAJAARAM_THERAPY_RESET_CEREMONY => 'Reset Ceremony (3h)',
            self::RAJAARAM_THERAPY_DEEP_DIVE => 'Deep Dive (3h)',
            self::RAJAARAM_THERAPY_CHAKRA_ALIGNMENT_EXPRESS => 'Chakra Alignment Express (5h)',
        ];
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $guestName = '';

    #[ORM\Column(length: 255)]
    private string $guestEmail = '';

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $guestWhatsapp = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $checkIn;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $checkOut;

    #[ORM\Column]
    private int $guests = 1;

    #[ORM\Column(length: 5, unique: true)]
    #[Assert\NotBlank(message: 'Informe o código de acesso.', groups: ['with_access_code'])]
    #[Assert\Length(exactly: 5, exactMessage: 'O código deve ter exactamente 5 caracteres.', groups: ['with_access_code'])]
    #[Assert\Regex(
        pattern: '/^[A-HJ-NP-Z2-9]{5}$/',
        message: 'Use apenas letras A-Z e números 2-9 (sem I, O, 0 ou 1).',
        groups: ['with_access_code'],
    )]
    private string $accessCode = '';

    #[ORM\Column(length: 50)]
    private string $source = self::SOURCE_SITE;

    #[ORM\Column(length: 50)]
    private string $status = self::STATUS_CONFIRMED;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(nullable: true)]
    private ?float $stayPrice = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $externalUid = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $icalSummary = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastSyncedAt = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $rajaaramTherapy = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $rajaaramTherapyTime = null;

    #[ORM\Column(nullable: true)]
    private ?bool $rajaaramBreakfastIncluded = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $manualDates = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, BookingExtra> */
    #[ORM\OneToMany(targetEntity: BookingExtra::class, mappedBy: 'booking', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $bookingExtras;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->checkIn = new \DateTimeImmutable();
        $this->checkOut = new \DateTimeImmutable('+1 day');
        $this->bookingExtras = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getGuestName(): string { return $this->guestName; }
    public function setGuestName(string $v): static { $this->guestName = $v; return $this; }
    public function getGuestEmail(): string { return $this->guestEmail; }
    public function setGuestEmail(string $v): static { $this->guestEmail = $v; return $this; }
    public function getGuestWhatsapp(): ?string { return $this->guestWhatsapp; }
    public function setGuestWhatsapp(?string $v): static { $this->guestWhatsapp = $v; return $this; }
    public function getCheckIn(): \DateTimeImmutable { return $this->checkIn; }
    public function setCheckIn(\DateTimeImmutable $v): static { $this->checkIn = $v; return $this; }
    public function getCheckOut(): \DateTimeImmutable { return $this->checkOut; }
    public function setCheckOut(\DateTimeImmutable $v): static { $this->checkOut = $v; return $this; }
    public function getGuests(): int { return $this->guests; }
    public function setGuests(int $v): static { $this->guests = $v; return $this; }
    public function getAccessCode(): string { return $this->accessCode; }
    public function setAccessCode(string $v): static { $this->accessCode = strtoupper($v); return $this; }
    public function getSource(): string { return $this->source; }
    public function setSource(string $v): static { $this->source = $v; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $v): static { $this->status = $v; return $this; }
    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $v): static { $this->notes = $v; return $this; }
    public function getStayPrice(): ?float { return $this->stayPrice; }
    public function setStayPrice(?float $v): static { $this->stayPrice = $v; return $this; }
    public function getExternalUid(): ?string { return $this->externalUid; }
    public function setExternalUid(?string $v): static { $this->externalUid = $v; return $this; }
    public function getIcalSummary(): ?string { return $this->icalSummary; }
    public function setIcalSummary(?string $v): static { $this->icalSummary = $v; return $this; }
    public function getLastSyncedAt(): ?\DateTimeImmutable { return $this->lastSyncedAt; }
    public function setLastSyncedAt(?\DateTimeImmutable $v): static { $this->lastSyncedAt = $v; return $this; }
    public function getRajaaramTherapy(): ?string { return $this->rajaaramTherapy; }
    public function setRajaaramTherapy(?string $v): static { $this->rajaaramTherapy = $v; return $this; }
    public function getRajaaramTherapyTime(): ?string { return $this->rajaaramTherapyTime; }
    public function setRajaaramTherapyTime(?string $v): static { $this->rajaaramTherapyTime = $v; return $this; }
    public function isRajaaramBreakfastIncluded(): ?bool { return $this->rajaaramBreakfastIncluded; }
    public function setRajaaramBreakfastIncluded(?bool $v): static { $this->rajaaramBreakfastIncluded = $v; return $this; }
    public function isManualDates(): bool { return $this->manualDates; }
    public function setManualDates(bool $v): static { $this->manualDates = $v; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function isRajaaram(): bool
    {
        return self::SOURCE_RAJAARAM === $this->source;
    }

    public function hasRajaaramSession(): bool
    {
        return $this->isRajaaram()
            || null !== $this->rajaaramTherapy
            || null !== $this->rajaaramTherapyTime;
    }

    public function getRajaaramTherapyLabel(string $locale = 'pt'): ?string
    {
        if (!$this->rajaaramTherapy) {
            return null;
        }

        if ('en' === $locale) {
            return self::rajaaramTherapyLabelsEn()[$this->rajaaramTherapy] ?? $this->rajaaramTherapy;
        }

        foreach (self::rajaaramTherapyChoices() as $label => $value) {
            if ($value === $this->rajaaramTherapy) {
                return $label;
            }
        }

        return $this->rajaaramTherapy;
    }

    public function clearRajaaramDetails(): static
    {
        $this->rajaaramTherapy = null;
        $this->rajaaramTherapyTime = null;
        $this->rajaaramBreakfastIncluded = null;

        return $this;
    }

    public function getGuestWhatsappDigits(): ?string
    {
        if (!$this->guestWhatsapp) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $this->guestWhatsapp) ?? '';

        return '' !== $digits ? $digits : null;
    }

    public function getGuestWhatsappUrl(): ?string
    {
        $digits = $this->getGuestWhatsappDigits();

        return $digits ? 'https://wa.me/'.$digits : null;
    }

    public function isImportedFromAirbnb(): bool
    {
        return self::SOURCE_AIRBNB === $this->source && null !== $this->externalUid;
    }

    public function isFromAirbnbIcalBlock(): bool
    {
        return self::SOURCE_SITE === $this->source
            && null !== $this->externalUid
            && self::isBlockedIcalSummary($this->icalSummary);
    }

    public static function isBlockedIcalSummary(?string $summary): bool
    {
        if (!$summary) {
            return true;
        }

        $normalized = strtolower(trim($summary));

        return str_contains($normalized, 'not available')
            || str_contains($normalized, 'unavailable')
            || str_contains($normalized, 'blocked')
            || 'airbnb' === $normalized;
    }

    public function isIcalSynced(): bool
    {
        return null !== $this->externalUid && ($this->isImportedFromAirbnb() || $this->isFromAirbnbIcalBlock());
    }

    public function needsGuestInfo(): bool
    {
        if (!$this->isFromAirbnbIcalBlock()) {
            return false;
        }

        return self::GUEST_NAME_PENDING === $this->guestName;
    }

    /** @return Collection<int, BookingExtra> */
    public function getBookingExtras(): Collection { return $this->bookingExtras; }

    public function isActiveOn(\DateTimeImmutable $date): bool
    {
        return self::STATUS_CONFIRMED === $this->status
            && $this->checkIn <= $date
            && $this->checkOut > $date;
    }

    /** Inclusive of check-out date — matches the date range shown in admin lists. */
    public function appearsOnCalendar(\DateTimeImmutable $date): bool
    {
        return self::STATUS_CONFIRMED === $this->status
            && $this->checkIn <= $date
            && $this->checkOut >= $date;
    }

    public function isExpired(\DateTimeImmutable $date): bool
    {
        return $this->checkOut <= $date;
    }

    public function overlapsPeriod(\DateTimeImmutable $checkIn, \DateTimeImmutable $checkOut): bool
    {
        return $this->checkIn < $checkOut && $this->checkOut > $checkIn;
    }
}
