<?php

namespace App\Entity;

use App\Repository\BookingDisabledExtraRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookingDisabledExtraRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_booking_extra_disabled', columns: ['booking_id', 'extra_id'])]
class BookingDisabledExtra
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'disabledExtras')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Booking $booking = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Extra $extra = null;

    public function getId(): ?int { return $this->id; }
    public function getBooking(): ?Booking { return $this->booking; }
    public function setBooking(?Booking $v): static { $this->booking = $v; return $this; }
    public function getExtra(): ?Extra { return $this->extra; }
    public function setExtra(?Extra $v): static { $this->extra = $v; return $this; }
}
