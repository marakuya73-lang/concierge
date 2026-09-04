<?php

namespace App\Service;

use App\Entity\Booking;
use Google\Service\Calendar\Event;
use Psr\Log\LoggerInterface;

class RajaaramCalendarSuggestionService
{
    private const TZ = 'America/Sao_Paulo';

    public function __construct(
        private GoogleCalendarSyncService $googleCalendarSyncService,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return list<array{
     *     eventId: string,
     *     therapyCode: string,
     *     therapyLabel: string,
     *     guest: ?string,
     *     date: string,
     *     time: string,
     *     start: \DateTimeImmutable,
     *     nameMatch: bool
     * }>
     */
    public function suggestionsFor(Booking $booking): array
    {
        if (!$this->shouldSuggest($booking) || !$this->googleCalendarSyncService->isTherapyCalendarConfigured()) {
            return [];
        }

        try {
            $tz = new \DateTimeZone(self::TZ);
            $events = $this->googleCalendarSyncService->listRajaaramEventsBetween(
                new \DateTimeImmutable($booking->getCheckIn()->format('Y-m-d').' 00:00:00', $tz),
                new \DateTimeImmutable($booking->getCheckOut()->format('Y-m-d').' 00:00:00', $tz),
            );
        } catch (\Throwable $exception) {
            $this->logger->error('Rajaaram calendar lookup failed for booking {id}: {message}', [
                'id' => $booking->getId(),
                'message' => $exception->getMessage(),
            ]);

            return [];
        }

        $dismissed = $booking->getRajaaramDismissedTherapyEventIds();
        $ownedIds = array_values($booking->getGoogleCalendarTherapyEventIds() ?? []);
        $parsed = [];

        foreach ($events as $event) {
            $suggestion = $this->parseSuggestion($event, $booking);
            if (null === $suggestion) {
                continue;
            }
            if (\in_array($suggestion['eventId'], $dismissed, true) || \in_array($suggestion['eventId'], $ownedIds, true)) {
                continue;
            }

            $parsed[] = $suggestion;
        }

        usort($parsed, static fn (array $a, array $b): int => $a['start'] <=> $b['start']);

        $matched = array_values(array_filter($parsed, static fn (array $item): bool => $item['nameMatch']));

        return [] !== $matched ? $matched : $parsed;
    }

    /**
     * @param list<string> $eventIds
     */
    public function apply(Booking $booking, array $eventIds): int
    {
        $wanted = array_values(array_unique(array_filter($eventIds)));
        $selected = array_values(array_filter(
            $this->suggestionsFor($booking),
            static fn (array $item): bool => \in_array($item['eventId'], $wanted, true),
        ));

        if ([] === $selected) {
            return 0;
        }

        $selected = \array_slice($selected, 0, 2);
        $first = $selected[0];
        $booking->setSource(Booking::SOURCE_RAJAARAM);
        $booking->setRajaaramTherapy($first['therapyCode']);
        $booking->setRajaaramTherapyDate($first['start']->setTime(0, 0));
        $booking->setRajaaramTherapyTime($first['start']->format('H:i'));
        $booking->setRajaaramGuest1Name($first['guest']);

        $ids = $booking->getGoogleCalendarTherapyEventIds() ?? [];
        $ids['1'] = $first['eventId'];
        $this->googleCalendarSyncService->attachBookingToTherapyEvent($booking, $first['eventId'], '1');

        if (isset($selected[1])) {
            $second = $selected[1];
            $booking->setRajaaramIsDuo(true);
            $booking->setRajaaramTherapy2($second['therapyCode']);
            $booking->setRajaaramTherapy2Date($second['start']->setTime(0, 0));
            $booking->setRajaaramTherapy2Time($second['start']->format('H:i'));
            $booking->setRajaaramGuest2Name($second['guest']);
            $ids['2'] = $second['eventId'];
            $this->googleCalendarSyncService->attachBookingToTherapyEvent($booking, $second['eventId'], '2');
        } else {
            $booking->setRajaaramIsDuo(false);
        }

        $booking->setGoogleCalendarTherapyEventIds([] !== $ids ? $ids : null);

        return \count($selected);
    }

    /**
     * @param list<string> $eventIds
     */
    public function dismiss(Booking $booking, array $eventIds): void
    {
        $current = $booking->getRajaaramDismissedTherapyEventIds();
        foreach ($eventIds as $eventId) {
            if (\is_string($eventId) && '' !== $eventId && !\in_array($eventId, $current, true)) {
                $current[] = $eventId;
            }
        }

        $booking->setRajaaramDismissedTherapyEventIds($current);
    }

    private function shouldSuggest(Booking $booking): bool
    {
        if (Booking::STATUS_CANCELLED === $booking->getStatus()) {
            return false;
        }

        return null === $booking->getRajaaramTherapy();
    }

    /**
     * @return array{
     *     eventId: string,
     *     therapyCode: string,
     *     therapyLabel: string,
     *     guest: ?string,
     *     date: string,
     *     time: string,
     *     start: \DateTimeImmutable,
     *     nameMatch: bool
     * }|null
     */
    private function parseSuggestion(Event $event, Booking $booking): ?array
    {
        if ('cancelled' === $event->getStatus() || !$event->getId()) {
            return null;
        }

        $properties = $event->getExtendedProperties()?->getPrivate() ?? [];
        if ('ceremony' === ($properties['rajaaramProductKind'] ?? '')
            || 'alert' === ($properties['rajaaramProductKind'] ?? '')
            || '' !== trim((string) ($properties['rajaaramAlertKind'] ?? ''))
            || 1 === preg_match('/^\s*(?:\[NOT PAID\]\s*)?Alert\s*·/u', (string) $event->getSummary())) {
            return null;
        }

        $ownerId = $properties['domoBookingId'] ?? '';
        if ('' !== $ownerId && (string) $booking->getId() !== $ownerId) {
            return null;
        }

        [$start] = $this->googleCalendarSyncService->eventRange($event);
        if (!$start) {
            return null;
        }

        $tz = new \DateTimeZone(self::TZ);
        $start = $start->setTimezone($tz);
        $stayDay = $start->format('Y-m-d');
        if ($stayDay < $booking->getCheckIn()->format('Y-m-d') || $stayDay >= $booking->getCheckOut()->format('Y-m-d')) {
            return null;
        }

        if ($event->getStart()?->getDate() && !$event->getStart()?->getDateTime()) {
            return null;
        }

        $text = trim((string) $event->getSummary().' '.(string) $event->getDescription());
        $therapyCode = RajaaramTherapySchedule::detectTherapyCode($text);
        if (null === $therapyCode) {
            return null;
        }

        $guest = $this->therapyGuestNameFromEvent($event, $properties);

        return [
            'eventId' => (string) $event->getId(),
            'therapyCode' => $therapyCode,
            'therapyLabel' => Booking::rajaaramTherapyLabelFor($therapyCode) ?? $therapyCode,
            'guest' => $guest,
            'date' => $start->format('d/m/Y'),
            'time' => $start->format('H:i'),
            'start' => $start,
            'nameMatch' => $this->matchesBookingNames($text.' '.$guest, $booking),
        ];
    }

    private function matchesBookingNames(string $eventText, Booking $booking): bool
    {
        $haystack = RajaaramTherapySchedule::fold($eventText);
        foreach ($this->bookingNameTokens($booking) as $token) {
            if (str_contains($haystack, $token)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    private function bookingNameTokens(Booking $booking): array
    {
        $names = array_merge(
            [$booking->getGuestName(), $booking->getRajaaramGuest1Name(), $booking->getRajaaramGuest2Name()],
            $booking->getExtraGuestNames(),
        );
        $tokens = [];

        foreach ($names as $name) {
            $folded = RajaaramTherapySchedule::fold((string) $name);
            if ('' === $folded || str_contains($folded, 'reserva directa')) {
                continue;
            }

            foreach (preg_split('/\s+/', $folded) ?: [] as $part) {
                if (mb_strlen($part) >= 3) {
                    $tokens[] = $part;
                }
            }
        }

        return array_values(array_unique($tokens));
    }

    /**
     * Prefer the client name saved on the Rajaaram therapy event — never the
     * Airbnb stay name, which can be a different person.
     *
     * @param array<string, string> $properties
     */
    private function therapyGuestNameFromEvent(Event $event, array $properties): ?string
    {
        foreach (['rajaaramGuestName', 'domoTherapyGuest'] as $key) {
            $fromProps = trim((string) ($properties[$key] ?? ''));
            if ('' !== $fromProps) {
                return $fromProps;
            }
        }

        $fromDescription = RajaaramTherapySchedule::extractGuestNameFromDescription($event->getDescription());
        if (null !== $fromDescription) {
            return $fromDescription;
        }

        return RajaaramTherapySchedule::extractGuestName((string) $event->getSummary());
    }
}
