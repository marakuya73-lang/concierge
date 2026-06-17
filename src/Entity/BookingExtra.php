<?php

namespace App\Entity;

use App\Repository\BookingExtraRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookingExtraRepository::class)]
class BookingExtra
{
    public const STATUS_REQUESTED = 'requested';
    public const STATUS_PAID = 'paid';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';

    public const REQUESTED_BY_GUEST = 'guest';
    public const REQUESTED_BY_HOST = 'host';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'bookingExtras')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Booking $booking = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Extra $extra = null;

    #[ORM\Column]
    private int $quantity = 1;

    #[ORM\Column(length: 50)]
    private string $status = self::STATUS_REQUESTED;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 20)]
    private string $requestedBy = self::REQUESTED_BY_GUEST;

    #[ORM\Column(nullable: true)]
    private ?float $priceAtBooking = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getBooking(): ?Booking { return $this->booking; }
    public function setBooking(?Booking $v): static { $this->booking = $v; return $this; }
    public function getExtra(): ?Extra { return $this->extra; }
    public function setExtra(?Extra $v): static { $this->extra = $v; return $this; }
    public function getQuantity(): int { return $this->quantity; }
    public function setQuantity(int $v): static { $this->quantity = $v; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $v): static { $this->status = $v; return $this; }
    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $v): static { $this->notes = $v; return $this; }
    public function getRequestedBy(): string { return $this->requestedBy; }
    public function setRequestedBy(string $v): static { $this->requestedBy = $v; return $this; }
    public function getPriceAtBooking(): ?float { return $this->priceAtBooking; }
    public function setPriceAtBooking(?float $v): static { $this->priceAtBooking = $v; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function canBeCancelledByGuest(): bool
    {
        return \in_array($this->status, [self::STATUS_REQUESTED, self::STATUS_PAID], true);
    }
}
