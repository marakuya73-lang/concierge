<?php

namespace App\Service;

use App\Entity\Booking;

class RajaaramTherapySchedule
{
    private const TZ = 'America/Sao_Paulo';

    /** @return array<string, int> */
    private static function durationMinutesByCode(): array
    {
        return [
            Booking::RAJAARAM_THERAPY_RESET_EXPRESS => 90,
            Booking::RAJAARAM_THERAPY_RESET_CEREMONY => 180,
            Booking::RAJAARAM_THERAPY_DEEP_DIVE => 180,
            Booking::RAJAARAM_THERAPY_CHAKRA_ALIGNMENT_EXPRESS => 300,
        ];
    }

    /**
     * @return list<array{
     *     key: string,
     *     therapyCode: string,
     *     therapyLabel: string,
     *     guest: ?string,
     *     start: \DateTimeImmutable,
     *     end: \DateTimeImmutable,
     *     summary: string
     * }>
     */
    public function buildSlots(Booking $booking): array
    {
        $slots = [];

        $first = $this->buildSlot(
            '1',
            $booking->getRajaaramTherapy(),
            $booking->getRajaaramTherapyDate(),
            $booking->getRajaaramTherapyTime(),
            $booking->getRajaaramGuest1Name(),
        );
        if (null !== $first) {
            $slots[] = $first;
        }

        if ($booking->isRajaaramDuo()) {
            $second = $this->buildSlot(
                '2',
                $booking->getRajaaramTherapy2(),
                $booking->getRajaaramTherapy2Date(),
                $booking->getRajaaramTherapy2Time(),
                $booking->getRajaaramGuest2Name(),
            );
            if (null !== $second) {
                $slots[] = $second;
            }
        }

        return $slots;
    }

    /**
     * @return array{
     *     key: string,
     *     therapyCode: string,
     *     therapyLabel: string,
     *     guest: ?string,
     *     start: \DateTimeImmutable,
     *     end: \DateTimeImmutable,
     *     summary: string
     * }|null
     */
    private function buildSlot(
        string $key,
        ?string $therapyCode,
        ?\DateTimeImmutable $date,
        ?string $time,
        ?string $guest,
    ): ?array {
        if (!$therapyCode || !$date || !$time) {
            return null;
        }

        $normalizedTime = $this->normalizeTime($time);
        if (null === $normalizedTime) {
            return null;
        }

        $duration = self::durationMinutesByCode()[$therapyCode] ?? null;
        if (null === $duration) {
            return null;
        }

        $tz = new \DateTimeZone(self::TZ);
        $start = new \DateTimeImmutable($date->format('Y-m-d').' '.$normalizedTime.':00', $tz);
        $end = $start->modify('+'.$duration.' minutes');
        $label = Booking::rajaaramTherapyLabelFor($therapyCode) ?? $therapyCode;
        $guestLabel = trim((string) $guest) ?: 'Hóspede';

        return [
            'key' => $key,
            'therapyCode' => $therapyCode,
            'therapyLabel' => $label,
            'guest' => $guest,
            'start' => $start,
            'end' => $end,
            'summary' => sprintf('%s · %s · G%s', $label, $guestLabel, $key),
        ];
    }

    private function normalizeTime(string $time): ?string
    {
        if (preg_match('/^(\d{1,2}):(\d{2})$/', trim($time), $matches)) {
            $hours = (int) $matches[1];
            $minutes = (int) $matches[2];
            if ($hours >= 0 && $hours <= 23 && $minutes >= 0 && $minutes <= 59) {
                return sprintf('%02d:%02d', $hours, $minutes);
            }
        }

        return null;
    }
}
