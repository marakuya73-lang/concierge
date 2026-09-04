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
            Booking::RAJAARAM_THERAPY_COMPLETE_CHAKRA => 240,
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

    public static function detectTherapyCode(string $text): ?string
    {
        $haystack = self::fold($text);

        $needles = [
            Booking::RAJAARAM_THERAPY_COMPLETE_CHAKRA => [
                'complete chakras',
                'chakras completos',
            ],
            Booking::RAJAARAM_THERAPY_CHAKRA_ALIGNMENT_EXPRESS => [
                'alinhamento dos chakras express',
                'chakra alignment express',
                'alinhamento dos chakras',
                'chakra alignment',
            ],
            Booking::RAJAARAM_THERAPY_RESET_CEREMONY => [
                'cerimonia reset',
                'ceremonia reset',
                'reset ceremony',
            ],
            Booking::RAJAARAM_THERAPY_RESET_EXPRESS => [
                'reset express',
            ],
            Booking::RAJAARAM_THERAPY_DEEP_DIVE => [
                'mergulho profundo',
                'deep dive',
            ],
        ];

        foreach ($needles as $code => $aliases) {
            foreach ($aliases as $alias) {
                if (str_contains($haystack, $alias)) {
                    return $code;
                }
            }
        }

        foreach (Booking::rajaaramTherapyChoices() as $label => $code) {
            if (str_contains($haystack, self::fold($label))) {
                return $code;
            }
        }

        return null;
    }

    public static function extractGuestNameFromDescription(?string $description): ?string
    {
        if (!\is_string($description) || '' === trim($description)) {
            return null;
        }

        if (preg_match('/^Guest:\s*(.+)$/mi', $description, $matches)) {
            $name = trim($matches[1]);
            if ('' !== $name && '-' !== $name) {
                return $name;
            }
        }

        if (preg_match('/^Hóspede:\s*(.+)$/mi', $description, $matches)) {
            $name = trim($matches[1]);
            if ('' !== $name && '-' !== $name) {
                return $name;
            }
        }

        return null;
    }

    public static function extractGuestName(string $summary): ?string
    {
        if (preg_match('/·\s*(.+?)\s*·\s*G\d+\s*$/u', $summary, $matches)) {
            $fromTitle = trim($matches[1]);
            if ('' !== $fromTitle) {
                return $fromTitle;
            }
        }

        $name = $summary;
        $labels = [
            ...array_keys(Booking::rajaaramTherapyChoices()),
            ...array_values(Booking::rajaaramTherapyLabelsEn()),
            'Reset Express',
            'Cerimônia Reset',
            'Cerimonia Reset',
            'Reset Ceremony',
            'Mergulho Profundo',
            'Deep Dive',
            'Complete Chakras',
            'Alinhamento dos Chakras Express',
            'Alinhamento dos Chakras',
            'Chakra Alignment Express',
            'Chakra Alignment',
        ];
        usort($labels, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));
        foreach ($labels as $label) {
            $name = str_ireplace($label, ' ', $name);
        }
        $name = preg_replace('/\(\s*\d+\s*h(?:\s*\d+)?\s*\)/iu', ' ', $name) ?? $name;
        $name = preg_replace('/\bG[12]\b/i', ' ', $name) ?? $name;
        $name = preg_replace('/[·•|\-–—,:]+/u', ' ', $name) ?? $name;
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? $name);

        return '' !== $name ? $name : null;
    }

    public static function fold(string $text): string
    {
        if (class_exists(\Transliterator::class)) {
            static $transliterator = null;
            if (null === $transliterator) {
                $transliterator = \Transliterator::create('Any-Latin; Latin-ASCII') ?: false;
            }
            if (false !== $transliterator) {
                $folded = $transliterator->transliterate($text);
                if (false !== $folded && '' !== $folded) {
                    $text = $folded;
                }
            }
        } else {
            $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            $text = $ascii !== false ? $ascii : $text;
            $text = str_replace(['^', '~', '`', "'", '"'], '', $text);
        }

        return mb_strtolower($text);
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
