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

    public const LOCALE_PT = 'pt';
    public const LOCALE_EN = 'en';

    /** @return array<string, string> */
    public static function guestLocaleChoices(): array
    {
        return [
            'Português' => self::LOCALE_PT,
            'English' => self::LOCALE_EN,
        ];
    }

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

    /** @var list<string>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $extraGuestNames = null;

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

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $rajaaramTherapyDate = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $rajaaramTherapyTime = null;

    #[ORM\Column(nullable: true)]
    private ?bool $rajaaramIsDuo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $rajaaramGuest1Name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $rajaaramGuest2Name = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $rajaaramTherapy2 = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $rajaaramTherapy2Date = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $rajaaramTherapy2Time = null;

    #[ORM\Column(nullable: true)]
    private ?bool $rajaaramBreakfastIncluded = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $manualDates = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $selfCheckInRequested = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $selfCheckInRequestedAt = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $plannedArrivalTime = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $plannedArrivalSubmittedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $upcomingReminderSentAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $loginCount = 0;

    #[ORM\Column(length: 2, options: ['default' => 'pt'])]
    private string $guestLocale = self::LOCALE_PT;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $googleCalendarEventId = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $googleCalendarSyncedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $googleCalendarEtag = null;

    /** @var array<string, string>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $googleCalendarTherapyEventIds = null;

    /** @var list<array<string, string>>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $googleCalendarTherapyConflicts = null;

    /** @var Collection<int, BookingExtra> */
    #[ORM\OneToMany(targetEntity: BookingExtra::class, mappedBy: 'booking', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $bookingExtras;

    /** @var Collection<int, BookingDisabledExtra> */
    #[ORM\OneToMany(targetEntity: BookingDisabledExtra::class, mappedBy: 'booking', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $disabledExtras;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->checkIn = new \DateTimeImmutable();
        $this->checkOut = new \DateTimeImmutable('+1 day');
        $this->bookingExtras = new ArrayCollection();
        $this->disabledExtras = new ArrayCollection();
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
    /** @return list<string> */
    public function getExtraGuestNames(): array
    {
        $names = [];
        foreach ($this->extraGuestNames ?? [] as $name) {
            if (!\is_string($name)) {
                continue;
            }

            $name = trim($name);
            if ('' !== $name) {
                $names[] = $name;
            }
        }

        return $names;
    }
    /** @param list<string>|null $v */
    public function setExtraGuestNames(?array $v): static
    {
        $this->extraGuestNames = [] !== ($names = $this->normalizeExtraGuestNames($v)) ? $names : null;

        return $this;
    }
    /** @param list<string>|null $names */
    private function normalizeExtraGuestNames(?array $names): array
    {
        $normalized = [];
        foreach ($names ?? [] as $name) {
            if (!\is_string($name)) {
                continue;
            }

            $name = trim($name);
            if ('' !== $name) {
                $normalized[] = $name;
            }
        }

        return $normalized;
    }
    public function getPartySize(): int
    {
        return max($this->guests, 1 + \count($this->getExtraGuestNames()));
    }
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
    public function getRajaaramTherapyDate(): ?\DateTimeImmutable { return $this->rajaaramTherapyDate; }
    public function setRajaaramTherapyDate(?\DateTimeImmutable $v): static { $this->rajaaramTherapyDate = $v; return $this; }
    public function getRajaaramTherapyTime(): ?string { return $this->rajaaramTherapyTime; }
    public function setRajaaramTherapyTime(?string $v): static { $this->rajaaramTherapyTime = $v; return $this; }
    public function isRajaaramDuo(): bool { return true === $this->rajaaramIsDuo; }
    public function isRajaaramIndividual(): bool { return !$this->isRajaaramDuo(); }
    public function getRajaaramIsDuo(): ?bool { return $this->rajaaramIsDuo; }
    public function setRajaaramIsDuo(?bool $v): static { $this->rajaaramIsDuo = $v; return $this; }
    public function getRajaaramGuest1Name(): ?string { return $this->rajaaramGuest1Name; }
    public function setRajaaramGuest1Name(?string $v): static { $this->rajaaramGuest1Name = $v; return $this; }
    public function getRajaaramGuest2Name(): ?string { return $this->rajaaramGuest2Name; }
    public function setRajaaramGuest2Name(?string $v): static { $this->rajaaramGuest2Name = $v; return $this; }
    public function getRajaaramTherapy2(): ?string { return $this->rajaaramTherapy2; }
    public function setRajaaramTherapy2(?string $v): static { $this->rajaaramTherapy2 = $v; return $this; }
    public function getRajaaramTherapy2Date(): ?\DateTimeImmutable { return $this->rajaaramTherapy2Date; }
    public function setRajaaramTherapy2Date(?\DateTimeImmutable $v): static { $this->rajaaramTherapy2Date = $v; return $this; }
    public function getRajaaramTherapy2Time(): ?string { return $this->rajaaramTherapy2Time; }
    public function setRajaaramTherapy2Time(?string $v): static { $this->rajaaramTherapy2Time = $v; return $this; }
    public function isRajaaramBreakfastIncluded(): ?bool { return $this->rajaaramBreakfastIncluded; }
    public function setRajaaramBreakfastIncluded(?bool $v): static { $this->rajaaramBreakfastIncluded = $v; return $this; }
    public function isManualDates(): bool { return $this->manualDates; }
    public function setManualDates(bool $v): static { $this->manualDates = $v; return $this; }
    public function isSelfCheckInRequested(): bool { return $this->selfCheckInRequested; }
    public function setSelfCheckInRequested(bool $v): static { $this->selfCheckInRequested = $v; return $this; }
    public function getSelfCheckInRequestedAt(): ?\DateTimeImmutable { return $this->selfCheckInRequestedAt; }
    public function setSelfCheckInRequestedAt(?\DateTimeImmutable $v): static { $this->selfCheckInRequestedAt = $v; return $this; }
    public function getPlannedArrivalTime(): ?string { return $this->plannedArrivalTime; }
    public function setPlannedArrivalTime(?string $v): static { $this->plannedArrivalTime = $v; return $this; }
    public function getPlannedArrivalSubmittedAt(): ?\DateTimeImmutable { return $this->plannedArrivalSubmittedAt; }
    public function setPlannedArrivalSubmittedAt(?\DateTimeImmutable $v): static { $this->plannedArrivalSubmittedAt = $v; return $this; }
    public function getUpcomingReminderSentAt(): ?\DateTimeImmutable { return $this->upcomingReminderSentAt; }
    public function setUpcomingReminderSentAt(?\DateTimeImmutable $v): static { $this->upcomingReminderSentAt = $v; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getLastLoginAt(): ?\DateTimeImmutable { return $this->lastLoginAt; }
    public function setLastLoginAt(?\DateTimeImmutable $v): static { $this->lastLoginAt = $v; return $this; }
    public function getLoginCount(): int { return $this->loginCount; }
    public function setLoginCount(int $v): static { $this->loginCount = $v; return $this; }
    public function getGuestLocale(): string { return $this->guestLocale; }
    public function setGuestLocale(string $v): static { $this->guestLocale = $v; return $this; }
    public function getGoogleCalendarEventId(): ?string { return $this->googleCalendarEventId; }
    public function setGoogleCalendarEventId(?string $v): static { $this->googleCalendarEventId = $v; return $this; }
    public function getGoogleCalendarSyncedAt(): ?\DateTimeImmutable { return $this->googleCalendarSyncedAt; }
    public function setGoogleCalendarSyncedAt(?\DateTimeImmutable $v): static { $this->googleCalendarSyncedAt = $v; return $this; }
    public function getGoogleCalendarEtag(): ?string { return $this->googleCalendarEtag; }
    public function setGoogleCalendarEtag(?string $v): static { $this->googleCalendarEtag = $v; return $this; }
    /** @return array<string, string>|null */
    public function getGoogleCalendarTherapyEventIds(): ?array { return $this->googleCalendarTherapyEventIds; }
    /** @param array<string, string>|null $v */
    public function setGoogleCalendarTherapyEventIds(?array $v): static { $this->googleCalendarTherapyEventIds = $v; return $this; }
    /** @return list<array<string, string>>|null */
    public function getGoogleCalendarTherapyConflicts(): ?array { return $this->googleCalendarTherapyConflicts; }
    /** @param list<array<string, string>>|null $v */
    public function setGoogleCalendarTherapyConflicts(?array $v): static { $this->googleCalendarTherapyConflicts = $v; return $this; }

    public function isRajaaram(): bool
    {
        return self::SOURCE_RAJAARAM === $this->source;
    }

    public function hasRajaaramSession(): bool
    {
        return $this->isRajaaram()
            || null !== $this->rajaaramTherapy
            || null !== $this->rajaaramTherapyDate
            || null !== $this->rajaaramTherapyTime
            || null !== $this->rajaaramGuest1Name
            || null !== $this->rajaaramGuest2Name
            || null !== $this->rajaaramTherapy2
            || null !== $this->rajaaramTherapy2Date
            || null !== $this->rajaaramTherapy2Time;
    }

    public function getRajaaramTherapyLabel(string $locale = 'pt'): ?string
    {
        return self::rajaaramTherapyLabelFor($this->rajaaramTherapy, $locale);
    }

    public function getRajaaramTherapy2Label(string $locale = 'pt'): ?string
    {
        return self::rajaaramTherapyLabelFor($this->rajaaramTherapy2, $locale);
    }

    public static function rajaaramTherapyLabelFor(?string $therapy, string $locale = 'pt'): ?string
    {
        if (!$therapy) {
            return null;
        }

        if ('en' === $locale) {
            return self::rajaaramTherapyLabelsEn()[$therapy] ?? $therapy;
        }

        foreach (self::rajaaramTherapyChoices() as $label => $value) {
            if ($value === $therapy) {
                return $label;
            }
        }

        return $therapy;
    }

    /** @return list<array{guest: ?string, therapy: ?string, date: ?string, time: ?string}> */
    public function getRajaaramSessions(string $locale = 'pt'): array
    {
        $sessions = [[
            'guest' => $this->rajaaramGuest1Name,
            'therapy' => $this->getRajaaramTherapyLabel($locale),
            'date' => $this->rajaaramTherapyDate?->format('d/m/Y'),
            'time' => $this->rajaaramTherapyTime,
        ]];

        if ($this->isRajaaramDuo()) {
            $sessions[] = [
                'guest' => $this->rajaaramGuest2Name,
                'therapy' => $this->getRajaaramTherapy2Label($locale),
                'date' => $this->rajaaramTherapy2Date?->format('d/m/Y'),
                'time' => $this->rajaaramTherapy2Time,
            ];
        }

        return $sessions;
    }

    public function clearRajaaramDetails(): static
    {
        $this->rajaaramTherapy = null;
        $this->rajaaramTherapyDate = null;
        $this->rajaaramTherapyTime = null;
        $this->rajaaramIsDuo = null;
        $this->rajaaramGuest1Name = null;
        $this->rajaaramGuest2Name = null;
        $this->rajaaramTherapy2 = null;
        $this->rajaaramTherapy2Date = null;
        $this->rajaaramTherapy2Time = null;
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

    /**
     * Bookings edited in admin (Rajaaram, Tucanto, filled site stays, locked dates)
     * must not be rewritten or cancelled by Airbnb iCal sync.
     */
    public function isLocallyManaged(): bool
    {
        if ($this->isManualDates() || $this->hasRajaaramSession() || $this->isRajaaram()) {
            return true;
        }

        if (self::SOURCE_TUCANTO === $this->source) {
            return true;
        }

        if (self::SOURCE_AIRBNB === $this->source) {
            return false;
        }

        return !($this->isFromAirbnbIcalBlock() && $this->needsGuestInfo());
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

    /** @return Collection<int, BookingDisabledExtra> */
    public function getDisabledExtras(): Collection { return $this->disabledExtras; }

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

    /** Guest concierge access ends after checkout + 1 day (inclusive grace day). */
    public function isExpired(\DateTimeImmutable $date): bool
    {
        return $date > $this->checkOut->modify('+1 day');
    }

    public function overlapsPeriod(\DateTimeImmutable $checkIn, \DateTimeImmutable $checkOut): bool
    {
        return $this->checkIn < $checkOut && $this->checkOut > $checkIn;
    }
}
