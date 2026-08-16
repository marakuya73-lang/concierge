<?php

namespace App\Service;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use App\Repository\PropertyRepository;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\EventExtendedProperties;
use Psr\Log\LoggerInterface;

class GoogleCalendarSyncService
{
    private const DESCRIPTION_MARKER = "\n\n---\nDomo (auto-sync)\n";
    private const TZ = 'America/Sao_Paulo';

    public function __construct(
        private GoogleCalendarApiClient $apiClient,
        private PropertyRepository $propertyRepository,
        private BookingRepository $bookingRepository,
        private BookingLifecycleService $bookingLifecycleService,
        private RajaaramTherapySchedule $therapySchedule,
        private LoggerInterface $logger,
        private string $defaultDomoCalendarId = '',
        private string $defaultRajaaramCalendarId = '',
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->apiClient->isConfigured() && '' !== $this->resolveDomoCalendarId();
    }

    public function isTherapyCalendarConfigured(): bool
    {
        return $this->apiClient->isConfigured() && '' !== $this->resolveRajaaramCalendarId();
    }

    /** @return array<string, int|string|null> */
    public function sync(): array
    {
        if (!$this->isConfigured()) {
            return ['message' => 'Google Calendar não configurado. Defina GOOGLE_SERVICE_ACCOUNT_JSON_PATH e GOOGLE_CALENDAR_DOMO_ID (mesmos valores do projecto Rajaaram).'];
        }

        $property = $this->propertyRepository->getOrCreate();
        $domoCalendarId = $this->resolveDomoCalendarId();
        $syncedAt = new \DateTimeImmutable();
        $today = new \DateTimeImmutable('today');

        $pulled = $this->pullDomoFromGoogle($property, $domoCalendarId, $today);
        $pushed = $this->pushAllStays($domoCalendarId, $syncedAt);
        $therapies = $this->pushAllTherapies($syncedAt);

        $property->setGoogleCalendarLastSyncAt($syncedAt);
        $this->bookingRepository->getEntityManager()->flush();

        return $pulled + $pushed + $therapies + ['syncedAt' => $syncedAt->format(\DateTimeInterface::ATOM)];
    }

    public function pushBooking(Booking $booking): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        $syncedAt = new \DateTimeImmutable();
        $domoChanged = $this->upsertStayEvent($this->resolveDomoCalendarId(), $booking, $syncedAt);
        $therapyChanged = $this->syncBookingTherapies($booking, $syncedAt);
        $this->bookingRepository->getEntityManager()->flush();

        return $domoChanged || $therapyChanged['changed'];
    }

    public function cancelBookingEvent(Booking $booking): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        $changed = false;

        if ($booking->getGoogleCalendarEventId()) {
            $changed = $this->cancelStayEvent($booking) || $changed;
        }

        if ($this->isTherapyCalendarConfigured()) {
            $changed = $this->cancelOwnedTherapyEvents($booking) || $changed;
        }

        return $changed;
    }

    /** @return array{imported: int, updatedFromGoogle: int, skipped: int} */
    private function pullDomoFromGoogle(\App\Entity\Property $property, string $calendarId, \DateTimeImmutable $today): array
    {
        $imported = 0;
        $updatedFromGoogle = 0;
        $skipped = 0;

        try {
            $events = $this->apiClient->listChangedEvents($calendarId, $property->getGoogleCalendarSyncToken());
        } catch (\Throwable $exception) {
            $this->logger->error('Google Calendar pull failed: {message}', [
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $property->setGoogleCalendarSyncToken($this->apiClient->getLastSyncToken());

        foreach ($events as $event) {
            $bookingId = $this->extractDomoBookingId($event);
            if (null === $bookingId) {
                ++$skipped;
                continue;
            }

            $booking = $this->bookingRepository->find($bookingId);
            if (!$booking) {
                ++$skipped;
                continue;
            }

            if ('cancelled' === $event->getStatus()) {
                if ($this->applyCancelledFromGoogle($booking, $event)) {
                    ++$updatedFromGoogle;
                }
                continue;
            }

            if ($this->applyStayEventFromGoogle($booking, $event, $today)) {
                ++$updatedFromGoogle;
            }
        }

        return compact('imported', 'updatedFromGoogle', 'skipped');
    }

    /** @return array{pushed: int, created: int, unchanged: int} */
    private function pushAllStays(string $calendarId, \DateTimeImmutable $syncedAt): array
    {
        $pushed = 0;
        $created = 0;
        $unchanged = 0;

        foreach ($this->bookingRepository->findForGoogleCalendarSync() as $booking) {
            $hadEvent = null !== $booking->getGoogleCalendarEventId();
            $changed = $this->upsertStayEvent($calendarId, $booking, $syncedAt);

            if (!$changed) {
                ++$unchanged;
                continue;
            }

            ++$pushed;
            if (!$hadEvent) {
                ++$created;
            }
        }

        return compact('pushed', 'created', 'unchanged');
    }

    /** @return array{therapyPushed: int, therapyCreated: int, therapyConflicts: int} */
    private function pushAllTherapies(\DateTimeImmutable $syncedAt): array
    {
        $therapyPushed = 0;
        $therapyCreated = 0;
        $therapyConflicts = 0;

        if (!$this->isTherapyCalendarConfigured()) {
            return compact('therapyPushed', 'therapyCreated', 'therapyConflicts');
        }

        foreach ($this->bookingRepository->findForGoogleCalendarSync() as $booking) {
            $beforeIds = $booking->getGoogleCalendarTherapyEventIds() ?? [];
            $result = $this->syncBookingTherapies($booking, $syncedAt);

            if ($result['changed']) {
                ++$therapyPushed;
            }

            $afterIds = $booking->getGoogleCalendarTherapyEventIds() ?? [];
            foreach ($afterIds as $sessionKey => $eventId) {
                if (!isset($beforeIds[$sessionKey]) && '' !== $eventId) {
                    ++$therapyCreated;
                }
            }

            $therapyConflicts += \count($booking->getGoogleCalendarTherapyConflicts() ?? []);
        }

        return compact('therapyPushed', 'therapyCreated', 'therapyConflicts');
    }

    /** @return array{changed: bool} */
    private function syncBookingTherapies(Booking $booking, \DateTimeImmutable $syncedAt): array
    {
        if (!$this->isTherapyCalendarConfigured()) {
            return ['changed' => false];
        }

        if (Booking::STATUS_CANCELLED === $booking->getStatus()) {
            return ['changed' => $this->cancelOwnedTherapyEvents($booking)];
        }

        $calendarId = $this->resolveRajaaramCalendarId();
        $desiredSlots = $this->therapySchedule->buildSlots($booking);
        $storedIds = $booking->getGoogleCalendarTherapyEventIds() ?? [];
        $conflicts = [];
        $nextIds = [];
        $changed = false;

        foreach ($desiredSlots as $slot) {
            $sessionKey = $slot['key'];
            $conflict = $this->findForeignTherapyConflict($calendarId, $booking, $slot, $storedIds[$sessionKey] ?? null);

            if (null !== $conflict) {
                $conflicts[] = [
                    'session' => $sessionKey,
                    'therapy' => $slot['therapyLabel'],
                    'date' => $slot['start']->format('d/m/Y'),
                    'time' => $slot['start']->format('H:i'),
                    'message' => sprintf(
                        'Horário %s %s ocupado no calendário Rajaaram (%s). Actualize a terapia na reserva.',
                        $slot['start']->format('d/m/Y'),
                        $slot['start']->format('H:i'),
                        $conflict,
                    ),
                ];
                if (isset($storedIds[$sessionKey])) {
                    $nextIds[$sessionKey] = $storedIds[$sessionKey];
                }
                continue;
            }

            $eventId = $storedIds[$sessionKey] ?? null;
            $nextIds[$sessionKey] = $this->upsertTherapyEvent($calendarId, $booking, $slot, $eventId, $syncedAt);
            $changed = true;
        }

        foreach ($storedIds as $sessionKey => $eventId) {
            if (isset($nextIds[$sessionKey]) || !isset($eventId) || '' === $eventId) {
                continue;
            }
            if ($this->cancelTherapyEvent($calendarId, $eventId)) {
                $changed = true;
            }
        }

        $booking->setGoogleCalendarTherapyEventIds([] !== $nextIds ? $nextIds : null);
        $booking->setGoogleCalendarTherapyConflicts([] !== $conflicts ? $conflicts : null);

        return ['changed' => $changed];
    }

    /**
     * @param array{
     *     key: string,
     *     therapyCode: string,
     *     therapyLabel: string,
     *     guest: ?string,
     *     start: \DateTimeImmutable,
     *     end: \DateTimeImmutable,
     *     summary: string
     * } $slot
     */
    private function findForeignTherapyConflict(
        string $calendarId,
        Booking $booking,
        array $slot,
        ?string $ownedEventId,
    ): ?string {
        $padding = new \DateInterval('PT1M');
        $from = $slot['start']->sub($padding);
        $to = $slot['end']->add($padding);

        foreach ($this->apiClient->listEventsBetween($calendarId, $from, $to) as $event) {
            if ('cancelled' === $event->getStatus()) {
                continue;
            }

            if ($ownedEventId && $event->getId() === $ownedEventId) {
                continue;
            }

            if ($this->isOwnedTherapyEvent($event, $booking, $slot['key'])) {
                continue;
            }

            [$eventStart, $eventEnd] = $this->parseEventRange($event);
            if (!$eventStart || !$eventEnd) {
                continue;
            }

            if (!$this->intervalsOverlap($slot['start'], $slot['end'], $eventStart, $eventEnd)) {
                continue;
            }

            return trim((string) $event->getSummary()) ?: 'evento existente';
        }

        return null;
    }

    /**
     * @param array{
     *     key: string,
     *     therapyCode: string,
     *     therapyLabel: string,
     *     guest: ?string,
     *     start: \DateTimeImmutable,
     *     end: \DateTimeImmutable,
     *     summary: string
     * } $slot
     */
    private function upsertTherapyEvent(
        string $calendarId,
        Booking $booking,
        array $slot,
        ?string $eventId,
        \DateTimeImmutable $syncedAt,
    ): string {
        $payload = $this->buildTherapyEventPayload($booking, $slot, $syncedAt);

        if ($eventId) {
            try {
                $existing = $this->apiClient->getEvent($calendarId, $eventId);
                if ($this->isOwnedTherapyEvent($existing, $booking, $slot['key'])) {
                    $payload->setDescription($this->mergeDescription($existing->getDescription(), $this->buildTherapyDescription($booking, $slot)));
                    $event = $this->apiClient->patchEvent($calendarId, $eventId, $payload);

                    return (string) $event->getId();
                }
            } catch (\Throwable) {
                // fall through to insert
            }
        }

        $payload->setDescription($this->mergeDescription(null, $this->buildTherapyDescription($booking, $slot)));
        $event = $this->apiClient->insertEvent($calendarId, $payload);

        return (string) $event->getId();
    }

    private function cancelOwnedTherapyEvents(Booking $booking): bool
    {
        if (!$this->isTherapyCalendarConfigured()) {
            return false;
        }

        $calendarId = $this->resolveRajaaramCalendarId();
        $changed = false;

        foreach ($booking->getGoogleCalendarTherapyEventIds() ?? [] as $eventId) {
            if ($this->cancelTherapyEvent($calendarId, $eventId)) {
                $changed = true;
            }
        }

        $booking->setGoogleCalendarTherapyEventIds(null);
        $booking->setGoogleCalendarTherapyConflicts(null);

        return $changed;
    }

    private function cancelTherapyEvent(string $calendarId, string $eventId): bool
    {
        if ('' === trim($eventId)) {
            return false;
        }

        try {
            $existing = $this->apiClient->getEvent($calendarId, $eventId);
        } catch (\Throwable) {
            return false;
        }

        if (!$this->isDomoManagedTherapyEvent($existing)) {
            return false;
        }

        $payload = new Event();
        $payload->setStatus('cancelled');

        try {
            $this->apiClient->patchEvent($calendarId, $eventId, $payload);
        } catch (\Throwable) {
            return false;
        }

        return true;
    }

    private function cancelStayEvent(Booking $booking): bool
    {
        if (!$booking->getGoogleCalendarEventId()) {
            return false;
        }

        $calendarId = $this->resolveDomoCalendarId();
        $payload = new Event();
        $payload->setStatus('cancelled');
        $payload->setSummary('[Removida] '.$this->buildStaySummary($booking));

        try {
            $event = $this->apiClient->patchEvent($calendarId, $booking->getGoogleCalendarEventId(), $payload);
        } catch (\Throwable) {
            return false;
        }

        $booking->setGoogleCalendarEtag($event->getEtag());
        $booking->setGoogleCalendarSyncedAt(new \DateTimeImmutable());

        return true;
    }

    private function upsertStayEvent(string $calendarId, Booking $booking, \DateTimeImmutable $syncedAt): bool
    {
        $payload = $this->buildStayEventPayload($booking, $syncedAt);
        $contentHash = $this->buildStayContentHash($booking);

        if ($booking->getGoogleCalendarEventId()) {
            $existing = null;

            try {
                $existing = $this->apiClient->getEvent($calendarId, $booking->getGoogleCalendarEventId());
            } catch (\Throwable) {
                $booking->setGoogleCalendarEventId(null);
            }

            if ($existing && $this->extractContentHash($existing) === $contentHash) {
                $booking->setGoogleCalendarEtag($existing->getEtag());
                $booking->setGoogleCalendarSyncedAt($syncedAt);

                return false;
            }

            if ($existing) {
                $payload->setDescription($this->mergeDescription($existing->getDescription(), $this->buildStayDescription($booking)));
                $event = $this->apiClient->patchEvent($calendarId, $booking->getGoogleCalendarEventId(), $payload);
                $this->storeStayEventMetadata($booking, $event, $syncedAt);

                return true;
            }
        }

        $payload->setDescription($this->mergeDescription(null, $this->buildStayDescription($booking)));
        $event = $this->apiClient->insertEvent($calendarId, $payload);
        $this->storeStayEventMetadata($booking, $event, $syncedAt);

        return true;
    }

    private function applyStayEventFromGoogle(Booking $booking, Event $event, \DateTimeImmutable $today): bool
    {
        if ($event->getEtag() && $event->getEtag() === $booking->getGoogleCalendarEtag()) {
            return false;
        }

        $changed = false;
        [$checkIn, $checkOut] = $this->parseEventDates($event);

        if ($checkIn && $checkOut && !$booking->isManualDates()) {
            if ($booking->getCheckIn()->format('Y-m-d') !== $checkIn->format('Y-m-d')
                || $booking->getCheckOut()->format('Y-m-d') !== $checkOut->format('Y-m-d')) {
                $booking->setCheckIn($checkIn);
                $booking->setCheckOut($checkOut);
                $changed = true;
            }
        }

        $summary = trim((string) $event->getSummary());
        if ('' !== $summary && $this->shouldUpdateGuestNameFromGoogle($booking, $summary)) {
            $guestName = $this->extractGuestNameFromSummary($summary);
            if ($guestName !== $booking->getGuestName()) {
                $booking->setGuestName($guestName);
                $changed = true;
            }
        }

        if ($event->getId() && $event->getId() !== $booking->getGoogleCalendarEventId()) {
            $booking->setGoogleCalendarEventId($event->getId());
            $changed = true;
        }

        $booking->setGoogleCalendarEtag($event->getEtag());
        $booking->setGoogleCalendarSyncedAt(new \DateTimeImmutable());

        $previousStatus = $booking->getStatus();
        $this->bookingLifecycleService->refreshStatus($booking, $today);
        if ($previousStatus !== $booking->getStatus()) {
            $changed = true;
        }

        return $changed;
    }

    private function applyCancelledFromGoogle(Booking $booking, Event $event): bool
    {
        if (Booking::STATUS_CANCELLED === $booking->getStatus()) {
            $booking->setGoogleCalendarEtag($event->getEtag());
            $booking->setGoogleCalendarSyncedAt(new \DateTimeImmutable());

            return false;
        }

        $booking->setStatus(Booking::STATUS_CANCELLED);
        $booking->setGoogleCalendarEtag($event->getEtag());
        $booking->setGoogleCalendarSyncedAt(new \DateTimeImmutable());

        return true;
    }

    private function buildStayEventPayload(Booking $booking, \DateTimeImmutable $syncedAt): Event
    {
        $category = $this->resolveStayCategory($booking);
        $event = new Event();
        $event->setSummary($this->buildStaySummary($booking));
        $event->setStart($this->buildAllDayDate($booking->getCheckIn()));
        $event->setEnd($this->buildAllDayDate($booking->getCheckOut()));
        $event->setColorId($this->colorIdForCategory($category));
        $event->setExtendedProperties(new EventExtendedProperties([
            'private' => [
                'domoBookingId' => (string) $booking->getId(),
                'domoAccessCode' => $booking->getAccessCode(),
                'domoCategory' => $category,
                'domoSource' => $booking->getSource(),
                'domoStatus' => $booking->getStatus(),
                'domoContentHash' => $this->buildStayContentHash($booking),
                'domoLastSync' => $syncedAt->format(\DateTimeInterface::ATOM),
            ],
        ]));

        if (Booking::STATUS_CANCELLED === $booking->getStatus()) {
            $event->setStatus('cancelled');
        }

        return $event;
    }

    /**
     * @param array{
     *     key: string,
     *     therapyCode: string,
     *     therapyLabel: string,
     *     guest: ?string,
     *     start: \DateTimeImmutable,
     *     end: \DateTimeImmutable,
     *     summary: string
     * } $slot
     */
    private function buildTherapyEventPayload(Booking $booking, array $slot, \DateTimeImmutable $syncedAt): Event
    {
        $event = new Event();
        $event->setSummary($slot['summary']);
        $event->setStart(new EventDateTime([
            'dateTime' => $slot['start']->format(\DateTimeInterface::RFC3339),
            'timeZone' => self::TZ,
        ]));
        $event->setEnd(new EventDateTime([
            'dateTime' => $slot['end']->format(\DateTimeInterface::RFC3339),
            'timeZone' => self::TZ,
        ]));
        $event->setExtendedProperties(new EventExtendedProperties([
            'private' => [
                'domoBookingId' => (string) $booking->getId(),
                'domoTherapySession' => $slot['key'],
                'domoManaged' => 'therapy',
                'domoAccessCode' => $booking->getAccessCode(),
                'domoLastSync' => $syncedAt->format(\DateTimeInterface::ATOM),
            ],
        ]));

        return $event;
    }

    private function buildStaySummary(Booking $booking): string
    {
        $category = $this->resolveStayCategory($booking);
        $prefix = match ($booking->getStatus()) {
            Booking::STATUS_CANCELLED => '[Cancelada] ',
            Booking::STATUS_COMPLETED => '[Concluída] ',
            default => '',
        };

        if ($booking->isFromAirbnbIcalBlock()) {
            return $prefix.'Indisponível · '.$category;
        }

        return $prefix.$booking->getGuestName().' · '.$category;
    }

    private function resolveStayCategory(Booking $booking): string
    {
        return $booking->getSource();
    }

    private function colorIdForCategory(string $category): string
    {
        return match ($category) {
            Booking::SOURCE_AIRBNB => '1',
            Booking::SOURCE_SITE => '8',
            Booking::SOURCE_RAJAARAM => '5',
            Booking::SOURCE_TUCANTO => '7',
            default => '9',
        };
    }

    private function buildStayDescription(Booking $booking): string
    {
        $category = $this->resolveStayCategory($booking);
        $lines = [
            'Categoria: '.$category,
            'Código: '.$booking->getAccessCode(),
            'Hóspedes: '.$booking->getGuests(),
            'Check-in: '.$booking->getCheckIn()->format('d/m/Y'),
            'Check-out: '.$booking->getCheckOut()->format('d/m/Y'),
            'Origem: '.$booking->getSource(),
            'Status: '.$booking->getStatus(),
        ];

        if ($booking->getGuestWhatsapp()) {
            $lines[] = 'WhatsApp: '.$booking->getGuestWhatsapp();
        }

        if ($booking->getPlannedArrivalTime()) {
            $lines[] = 'Chegada prevista: '.$booking->getPlannedArrivalTime();
        }

        if ($booking->getStayPrice()) {
            $lines[] = 'Valor: R$ '.number_format($booking->getStayPrice(), 2, ',', '.');
        }

        if ($booking->getNotes()) {
            $lines[] = 'Notas: '.$booking->getNotes();
        }

        return implode("\n", $lines);
    }

    /**
     * @param array{
     *     key: string,
     *     therapyCode: string,
     *     therapyLabel: string,
     *     guest: ?string,
     *     start: \DateTimeImmutable,
     *     end: \DateTimeImmutable,
     *     summary: string
     * } $slot
     */
    private function buildTherapyDescription(Booking $booking, array $slot): string
    {
        return implode("\n", [
            'Domo booking #'.$booking->getId(),
            'Código: '.$booking->getAccessCode(),
            'Terapia: '.$slot['therapyLabel'],
            'Hóspede: '.($slot['guest'] ?: $booking->getGuestName()),
            'Início: '.$slot['start']->format('d/m/Y H:i'),
            'Fim: '.$slot['end']->format('H:i'),
        ]);
    }

    private function mergeDescription(?string $existingDescription, string $managedDescription): string
    {
        $existingDescription = (string) $existingDescription;
        $markerPos = strpos($existingDescription, self::DESCRIPTION_MARKER);

        if (false === $markerPos) {
            $manual = rtrim($existingDescription);

            return ('' !== $manual ? $manual.self::DESCRIPTION_MARKER : ltrim(self::DESCRIPTION_MARKER, "\n")).$managedDescription;
        }

        $manual = rtrim(substr($existingDescription, 0, $markerPos));

        return ('' !== $manual ? $manual.self::DESCRIPTION_MARKER : ltrim(self::DESCRIPTION_MARKER, "\n")).$managedDescription;
    }

    private function buildStayContentHash(Booking $booking): string
    {
        return hash('sha256', implode('|', [
            $this->buildStaySummary($booking),
            $this->resolveStayCategory($booking),
            $booking->getCheckIn()->format('Y-m-d'),
            $booking->getCheckOut()->format('Y-m-d'),
            $booking->getStatus(),
            $booking->getGuestName(),
            $booking->getGuests(),
            (string) $booking->getGuestWhatsapp(),
            (string) $booking->getPlannedArrivalTime(),
            (string) $booking->getStayPrice(),
            (string) $booking->getNotes(),
        ]));
    }

    private function extractContentHash(Event $event): ?string
    {
        $properties = $event->getExtendedProperties()?->getPrivate() ?? [];

        return $properties['domoContentHash'] ?? null;
    }

    private function extractDomoBookingId(Event $event): ?int
    {
        $properties = $event->getExtendedProperties()?->getPrivate() ?? [];
        $bookingId = $properties['domoBookingId'] ?? null;

        if (!$bookingId) {
            return null;
        }

        return (int) $bookingId;
    }

    private function isOwnedTherapyEvent(Event $event, Booking $booking, string $sessionKey): bool
    {
        $properties = $event->getExtendedProperties()?->getPrivate() ?? [];

        return ($properties['domoBookingId'] ?? '') === (string) $booking->getId()
            && ($properties['domoTherapySession'] ?? '') === $sessionKey;
    }

    private function isDomoManagedTherapyEvent(Event $event): bool
    {
        $properties = $event->getExtendedProperties()?->getPrivate() ?? [];

        return ($properties['domoManaged'] ?? '') === 'therapy'
            && isset($properties['domoBookingId'], $properties['domoTherapySession']);
    }

    /** @return array{0: ?\DateTimeImmutable, 1: ?\DateTimeImmutable} */
    private function parseEventDates(Event $event): array
    {
        return $this->parseEventRange($event);
    }

    /** @return array{0: ?\DateTimeImmutable, 1: ?\DateTimeImmutable} */
    private function parseEventRange(Event $event): array
    {
        $start = $this->parseEventDateTime($event->getStart());
        $end = $this->parseEventDateTime($event->getEnd());

        return [$start, $end];
    }

    private function parseEventDateTime(?EventDateTime $dateTime): ?\DateTimeImmutable
    {
        if (!$dateTime) {
            return null;
        }

        if ($dateTime->getDate()) {
            return new \DateTimeImmutable($dateTime->getDate());
        }

        if ($dateTime->getDateTime()) {
            return new \DateTimeImmutable($dateTime->getDateTime());
        }

        return null;
    }

    private function buildAllDayDate(\DateTimeImmutable $date): EventDateTime
    {
        return new EventDateTime(['date' => $date->format('Y-m-d')]);
    }

    private function intervalsOverlap(
        \DateTimeImmutable $startA,
        \DateTimeImmutable $endA,
        \DateTimeImmutable $startB,
        \DateTimeImmutable $endB,
    ): bool {
        return $startA < $endB && $endA > $startB;
    }

    private function storeStayEventMetadata(Booking $booking, Event $event, \DateTimeImmutable $syncedAt): void
    {
        $booking->setGoogleCalendarEventId($event->getId());
        $booking->setGoogleCalendarEtag($event->getEtag());
        $booking->setGoogleCalendarSyncedAt($syncedAt);
    }

    private function shouldUpdateGuestNameFromGoogle(Booking $booking, string $summary): bool
    {
        if ($booking->isFromAirbnbIcalBlock()) {
            return false;
        }

        $currentName = trim($booking->getGuestName());

        return '' === $currentName
            || Booking::GUEST_NAME_PENDING === $currentName
            || str_contains($summary, $currentName);
    }

    private function extractGuestNameFromSummary(string $summary): string
    {
        $summary = preg_replace('/^\[(Cancelada|Concluída|Removida)\]\s*/u', '', $summary) ?? $summary;
        $parts = explode('·', $summary, 2);

        return trim($parts[0]) ?: $summary;
    }

    private function resolveDomoCalendarId(): string
    {
        $property = $this->propertyRepository->getOrCreate();

        return trim((string) ($property->getGoogleCalendarId() ?: $this->defaultDomoCalendarId));
    }

    private function resolveRajaaramCalendarId(): string
    {
        return trim($this->defaultRajaaramCalendarId);
    }
}
