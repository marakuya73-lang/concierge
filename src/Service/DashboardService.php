<?php

namespace App\Service;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use App\Repository\ExtraRepository;
use App\Repository\PropertyRepository;

class DashboardService
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private ExtraRepository $extraRepository,
        private PropertyRepository $propertyRepository,
    ) {
    }

    public function getStats(?int $year = null, ?int $month = null): array
    {
        $today = new \DateTimeImmutable('today');
        $year ??= (int) $today->format('Y');
        $month ??= (int) $today->format('n');
        $month = max(1, min(12, $month));
        $year = max(2020, min(2035, $year));

        $allBookings = $this->bookingRepository->findAllOrdered();

        $upcomingBookings = array_values(array_filter(
            $allBookings,
            fn (Booking $b) => Booking::STATUS_CONFIRMED === $b->getStatus() && $b->getCheckIn() > $today
        ));

        $currentBookings = array_values(array_filter(
            $allBookings,
            fn (Booking $b) => $b->isActiveOn($today)
        ));

        $pendingSite = $this->bookingRepository->findPendingSiteBookings($today);

        $yearStart = new \DateTimeImmutable($today->format('Y').'-01-01');
        $yearEnd = new \DateTimeImmutable($today->format('Y').'-12-31');
        $totalDaysOccupied = 0;

        foreach ($allBookings as $booking) {
            if (Booking::STATUS_CONFIRMED !== $booking->getStatus()) {
                continue;
            }
            if ($booking->getCheckOut() < $yearStart || $booking->getCheckIn() > $yearEnd) {
                continue;
            }

            $start = max($booking->getCheckIn(), $yearStart);
            $end = min($booking->getCheckOut(), $yearEnd);
            $days = max(0, $start->diff($end)->days);
            $totalDaysOccupied += $days;
        }

        $occupancyRate = (int) round(($totalDaysOccupied / 365) * 100);

        usort($upcomingBookings, fn (Booking $a, Booking $b) => $a->getCheckIn() <=> $b->getCheckIn());
        usort($currentBookings, fn (Booking $a, Booking $b) => $a->getCheckOut() <=> $b->getCheckOut());

        $nextUpcoming = $upcomingBookings[0] ?? null;
        $current = $currentBookings[0] ?? null;
        $property = $this->propertyRepository->getOrCreate();

        return [
            'lastIcalSyncAt' => $property->getAirbnbIcalLastSyncAt(),
            'totalBookings' => count($allBookings),
            'currentGuests' => array_sum(array_map(fn (Booking $b) => $b->getGuests(), $currentBookings)),
            'upcomingBookings' => count($upcomingBookings),
            'pendingSite' => count($pendingSite),
            'activeExtras' => count($this->extraRepository->findActive()),
            'occupancyRate' => $occupancyRate,
            'nextCheckIn' => $nextUpcoming?->getCheckIn()->format('Y-m-d'),
            'nextCheckOut' => $current?->getCheckOut()->format('Y-m-d'),
            'currentBooking' => $current,
            'upcoming' => array_slice($upcomingBookings, 0, 5),
            'pendingSiteBookings' => array_slice($pendingSite, 0, 5),
            'calendar' => $this->buildCalendar($year, $month, $today, $allBookings),
        ];
    }

    /** @param Booking[] $bookings */
    private function buildCalendar(int $year, int $month, \DateTimeImmutable $today, array $bookings): array
    {
        $firstDay = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $daysInMonth = (int) $firstDay->format('t');
        $startDow = (int) $firstDay->format('w');
        $isCurrentMonth = $year === (int) $today->format('Y') && $month === (int) $today->format('n');

        $prev = $firstDay->modify('-1 month');
        $next = $firstDay->modify('+1 month');

        $occupiedDays = [];
        $bookingsByDay = [];

        for ($day = 1; $day <= $daysInMonth; ++$day) {
            $date = new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day));

            foreach ($bookings as $booking) {
                if (Booking::STATUS_CANCELLED === $booking->getStatus()) {
                    continue;
                }

                if (!$booking->appearsOnCalendar($date)) {
                    continue;
                }

                $occupiedDays[$day] = true;
                $bookingsByDay[$day][] = [
                    'id' => $booking->getId(),
                    'guestName' => $booking->getGuestName(),
                    'source' => $booking->getSource(),
                    'checkIn' => $booking->getCheckIn()->format('Y-m-d'),
                    'checkOut' => $booking->getCheckOut()->format('Y-m-d'),
                ];
            }
        }

        foreach ($bookingsByDay as &$dayEntries) {
            usort($dayEntries, static fn (array $a, array $b): int => [$a['checkIn'], $a['guestName']] <=> [$b['checkIn'], $b['guestName']]);
        }
        unset($dayEntries);

        return [
            'year' => $year,
            'month' => $month,
            'monthLabel' => $this->formatMonthLabel($firstDay),
            'daysInMonth' => $daysInMonth,
            'startDow' => $startDow,
            'isCurrentMonth' => $isCurrentMonth,
            'today' => $isCurrentMonth ? (int) $today->format('j') : null,
            'occupiedDays' => array_keys($occupiedDays),
            'bookingsByDay' => $bookingsByDay,
            'prev' => ['year' => (int) $prev->format('Y'), 'month' => (int) $prev->format('n')],
            'next' => ['year' => (int) $next->format('Y'), 'month' => (int) $next->format('n')],
        ];
    }

    private function formatMonthLabel(\DateTimeImmutable $date): string
    {
        $months = [
            1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
            5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
            9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro',
        ];

        return ($months[(int) $date->format('n')] ?? $date->format('F')).' '.$date->format('Y');
    }
}
