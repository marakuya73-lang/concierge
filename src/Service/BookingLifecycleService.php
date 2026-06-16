<?php

namespace App\Service;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;

class BookingLifecycleService
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private EntityManagerInterface $em,
    ) {
    }

    public function refreshStatus(Booking $booking, ?\DateTimeImmutable $today = null): void
    {
        $today ??= new \DateTimeImmutable('today');

        if (Booking::STATUS_CANCELLED === $booking->getStatus()) {
            return;
        }

        if ($booking->getCheckOut() <= $today) {
            $booking->setStatus(Booking::STATUS_COMPLETED);
        } elseif (Booking::STATUS_COMPLETED === $booking->getStatus() && $booking->getCheckOut() > $today) {
            $booking->setStatus(Booking::STATUS_CONFIRMED);
        }
    }

    public function markPastBookingsCompleted(?\DateTimeImmutable $today = null): int
    {
        $today ??= new \DateTimeImmutable('today');
        $count = 0;

        foreach ($this->bookingRepository->findConfirmedPast($today) as $booking) {
            $booking->setStatus(Booking::STATUS_COMPLETED);
            ++$count;
        }

        if ($count > 0) {
            $this->em->flush();
        }

        return $count;
    }
}
