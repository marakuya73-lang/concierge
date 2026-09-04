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

    private const THERAPY_WRITE_PULL = 'pull';

    private const THERAPY_WRITE_ASK = 'ask';

    private const THERAPY_WRITE_PUSH = 'push';

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

    /**
     * @return list<Event>
     */
    public function listRajaaramEventsBetween(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        if (!$this->isTherapyCalendarConfigured()) {
            return [];
        }

        return $this->apiClient->listEventsBetween($this->resolveRajaaramCalendarId(), $from, $to);
    }

    public function attachBookingToTherapyEvent(Booking $booking, string $eventId, string $sessionKey): bool
    {
        if (!$this->isTherapyCalendarConfigured() || '' === trim($eventId)) {
            return false;
        }

        $calendarId = $this->resolveRajaaramCalendarId();

        try {
            $existing = $this->apiClient->getEvent($calendarId, $eventId);
        } catch (\Throwable $exception) {
            $this->logger->warning('Could not load Rajaaram therapy event {id}: {message}', [
                'id' => $eventId,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }

        $private = $existing->getExtendedProperties()?->getPrivate() ?? [];
        $private = array_merge($private, $this->therapyPrivateProperties($booking, $sessionKey));

        $payload = new Event();
        $payload->setExtendedProperties(new EventExtendedProperties(['private' => $private]));

        try {
            $this->apiClient->patchEvent($calendarId, $eventId, $payload);
        } catch (\Throwable $exception) {
            $this->logger->warning('Could not attach booking {booking} to Rajaaram event {id}: {message}', [
                'booking' => $booking->getId(),
                'id' => $eventId,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }

        return true;
    }

    /** @return array{0: ?\DateTimeImmutable, 1: ?\DateTimeImmutable} */
    public function eventRange(Event $event): array
    {
        return $this->parseEventRange($event);
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

    public function pushBooking(Booking $booking, bool $forceTherapy = false): bool
    {
        if (!$this->isConfigured() && !$this->isTherapyCalendarConfigured()) {
            return false;
        }

        $syncedAt = new \DateTimeImmutable();
        $domoChanged = false;
        if ($this->isConfigured()) {
            $domoChanged = $this->upsertStayEvent($this->resolveDomoCalendarId(), $booking, $syncedAt);
        }

        $therapyChanged = $this->syncBookingTherapies(
            $booking,
            $syncedAt,
            $forceTherapy ? self::THERAPY_WRITE_PUSH : self::THERAPY_WRITE_ASK,
        );
        $this->bookingRepository->getEntityManager()->flush();

        return $domoChanged || $therapyChanged['changed'];
    }

    public function pullTherapiesFromRajaaram(Booking $booking): bool
    {
        if (!$this->isTherapyCalendarConfigured()) {
            return false;
        }

        $result = $this->syncBookingTherapies($booking, new \DateTimeImmutable(), self::THERAPY_WRITE_PULL);
        $this->bookingRepository->getEntityManager()->flush();

        return $result['changed'];
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
            $result = $this->syncBookingTherapies($booking, $syncedAt, self::THERAPY_WRITE_PULL);

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

    /**
     * Rajaaram calendar is the source of truth.
     * pull = copy calendar → booking, never rewrite events
     * ask  = keep existing events, record drift instead of overwriting
     * push = admin confirmed writing this booking onto Rajaaram
     *
     * @return array{changed: bool}
     */
    private function syncBookingTherapies(Booking $booking, \DateTimeImmutable $syncedAt, string $mode = self::THERAPY_WRITE_ASK): array
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
            $existing = $this->fetchTherapyEvent($calendarId, $storedIds[$sessionKey] ?? null);

            if ($existing && 'cancelled' !== $existing->getStatus()) {
                $snapshot = $this->therapySnapshotFromEvent($existing);

                if (self::THERAPY_WRITE_PULL === $mode) {
                    if ($this->applySnapshotToBooking($booking, $sessionKey, $snapshot)) {
                        $changed = true;
                    }
                    $this->attachPrivatePropsIfNeeded($booking, $existing, $sessionKey);
                    $nextIds[$sessionKey] = (string) $existing->getId();
                    continue;
                }

                if (self::THERAPY_WRITE_ASK === $mode) {
                    if (!$this->slotMatchesSnapshot($slot, $snapshot)) {
                        $conflicts[] = $this->driftConflict($sessionKey, $slot, $snapshot);
                    } else {
                        $this->attachPrivatePropsIfNeeded($booking, $existing, $sessionKey);
                    }
                    $nextIds[$sessionKey] = (string) $existing->getId();
                    continue;
                }

                $nextIds[$sessionKey] = $this->upsertTherapyEvent(
                    $calendarId,
                    $booking,
                    $slot,
                    (string) $existing->getId(),
                    $syncedAt,
                );
                $changed = true;
                continue;
            }

            if (self::THERAPY_WRITE_PULL === $mode) {
                continue;
            }

            $conflict = self::THERAPY_WRITE_PUSH === $mode
                ? null
                : $this->findForeignTherapyConflict($calendarId, $booking, $slot, null);

            if (null !== $conflict) {
                $conflicts[] = $this->busyConflict($sessionKey, $slot, $conflict);
                continue;
            }

            $nextIds[$sessionKey] = $this->upsertTherapyEvent($calendarId, $booking, $slot, null, $syncedAt);
            $changed = true;
        }

        foreach ($storedIds as $sessionKey => $eventId) {
            if (isset($nextIds[$sessionKey]) || !\is_string($eventId) || '' === $eventId) {
                continue;
            }

            $existing = $this->fetchTherapyEvent($calendarId, $eventId);
            if (!$existing || 'cancelled' === $existing->getStatus()) {
                $changed = true;
                continue;
            }

            if (self::THERAPY_WRITE_PULL === $mode) {
                if ($this->applySnapshotToBooking($booking, (string) $sessionKey, $this->therapySnapshotFromEvent($existing))) {
                    $changed = true;
                }
                $nextIds[(string) $sessionKey] = (string) $existing->getId();
                continue;
            }

            if (self::THERAPY_WRITE_ASK === $mode && $this->isRajaaramAuthored($existing)) {
                $conflicts[] = $this->leftoverDriftConflict((string) $sessionKey, $this->therapySnapshotFromEvent($existing));
                $nextIds[(string) $sessionKey] = (string) $existing->getId();
                continue;
            }

            if ($this->cancelTherapyEvent($calendarId, $eventId)) {
                $changed = true;
            }
        }

        $booking->setGoogleCalendarTherapyEventIds([] !== $nextIds ? $nextIds : null);
        $booking->setGoogleCalendarTherapyConflicts([] !== $conflicts ? $conflicts : null);

        return ['changed' => $changed || [] !== $conflicts];
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
            $existing = $this->fetchTherapyEvent($calendarId, $eventId);
            if ($existing && $this->isRajaaramAuthored($existing)) {
                continue;
            }
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

        if (!$this->isDomoManagedTherapyEvent($existing) || $this->isRajaaramAuthored($existing)) {
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
            'private' => $this->therapyPrivateProperties($booking, $slot['key'], $syncedAt, $slot),
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
            'Quem reservou: '.$booking->getGuestName(),
            'Check-in: '.$booking->getCheckIn()->format('d/m/Y'),
            'Check-out: '.$booking->getCheckOut()->format('d/m/Y'),
            'Origem: '.$booking->getSource(),
            'Status: '.$booking->getStatus(),
        ];

        if ($booking->getExtraGuestNames()) {
            $lines[] = 'Hóspedes extra: '.implode(', ', $booking->getExtraGuestNames());
        }

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
            'Hóspede: '.($slot['guest'] ?: $booking->getRajaaramGuest1Name() ?: $booking->getGuestName()),
            'Início: '.$slot['start']->format('d/m/Y H:i'),
            'Fim: '.$slot['end']->format('H:i'),
        ]);
    }

    /**
     * Private Google Calendar keys written on Rajaaram therapy events.
     * Rajaaram should keep the same names if it wants Domo to recognize a linked session.
     *
     * @param array{
     *     key?: string,
     *     therapyCode?: string,
     *     guest?: ?string
     * }|null $slot
     *
     * @return array<string, string>
     */
    private function therapyPrivateProperties(
        Booking $booking,
        string $sessionKey,
        ?\DateTimeImmutable $syncedAt = null,
        ?array $slot = null,
    ): array {
        $properties = [
            'domoBookingId' => (string) $booking->getId(),
            'domoTherapySession' => $sessionKey,
            'domoManaged' => 'therapy',
            'domoAccessCode' => $booking->getAccessCode(),
        ];

        if ($syncedAt) {
            $properties['domoLastSync'] = $syncedAt->format(\DateTimeInterface::ATOM);
        }

        if (isset($slot['therapyCode']) && '' !== (string) $slot['therapyCode']) {
            $properties['domoTherapyCode'] = (string) $slot['therapyCode'];
        }

        $guest = trim((string) ($slot['guest'] ?? ''));
        if ('' === $guest) {
            $guest = trim((string) (
                '2' === $sessionKey
                    ? $booking->getRajaaramGuest2Name()
                    : $booking->getRajaaramGuest1Name()
            ));
        }
        if ('' !== $guest) {
            $properties['domoTherapyGuest'] = $guest;
            $properties['rajaaramGuestName'] = $guest;
        }

        return $properties;
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

    private function fetchTherapyEvent(string $calendarId, ?string $eventId): ?Event
    {
        if (null === $eventId || '' === trim($eventId)) {
            return null;
        }

        try {
            return $this->apiClient->getEvent($calendarId, $eventId);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{
     *     therapyCode: ?string,
     *     therapyLabel: ?string,
     *     guest: ?string,
     *     start: ?\DateTimeImmutable,
     *     summary: string
     * }
     */
    private function therapySnapshotFromEvent(Event $event): array
    {
        [$start] = $this->eventRange($event);
        if ($start) {
            $start = $start->setTimezone(new \DateTimeZone(self::TZ));
        }

        $properties = $event->getExtendedProperties()?->getPrivate() ?? [];
        $guest = trim((string) ($properties['rajaaramGuestName'] ?? $properties['domoTherapyGuest'] ?? ''));
        if ('' === $guest) {
            $guest = RajaaramTherapySchedule::extractGuestNameFromDescription($event->getDescription()) ?? '';
        }
        if ('' === $guest) {
            $guest = RajaaramTherapySchedule::extractGuestName((string) $event->getSummary()) ?? '';
        }

        $text = trim((string) $event->getSummary().' '.(string) $event->getDescription());
        $code = trim((string) ($properties['domoTherapyCode'] ?? ''));
        if ('' === $code) {
            $code = (string) (RajaaramTherapySchedule::detectTherapyCode($text) ?? '');
        }

        return [
            'therapyCode' => '' !== $code ? $code : null,
            'therapyLabel' => '' !== $code ? (Booking::rajaaramTherapyLabelFor($code) ?? $code) : null,
            'guest' => '' !== $guest ? $guest : null,
            'start' => $start,
            'summary' => trim((string) $event->getSummary()),
        ];
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
     * @param array{
     *     therapyCode: ?string,
     *     therapyLabel: ?string,
     *     guest: ?string,
     *     start: ?\DateTimeImmutable,
     *     summary: string
     * } $snapshot
     */
    private function slotMatchesSnapshot(array $slot, array $snapshot): bool
    {
        if (!$snapshot['start'] instanceof \DateTimeImmutable) {
            return false;
        }

        if ($slot['start']->format('Y-m-d H:i') !== $snapshot['start']->format('Y-m-d H:i')) {
            return false;
        }

        if (null !== $snapshot['therapyCode'] && $snapshot['therapyCode'] !== $slot['therapyCode']) {
            return false;
        }

        return RajaaramTherapySchedule::fold(trim((string) $slot['guest']))
            === RajaaramTherapySchedule::fold(trim((string) $snapshot['guest']));
    }

    /**
     * @param array{
     *     therapyCode: ?string,
     *     therapyLabel: ?string,
     *     guest: ?string,
     *     start: ?\DateTimeImmutable,
     *     summary: string
     * } $snapshot
     */
    private function applySnapshotToBooking(Booking $booking, string $sessionKey, array $snapshot): bool
    {
        if (!$snapshot['start'] instanceof \DateTimeImmutable) {
            return false;
        }

        $date = $snapshot['start']->setTime(0, 0);
        $time = $snapshot['start']->format('H:i');
        $guest = $snapshot['guest'];
        $code = $snapshot['therapyCode'];
        $changed = false;

        if ('2' === $sessionKey) {
            if (!$booking->isRajaaramDuo()) {
                $booking->setRajaaramIsDuo(true);
                $changed = true;
            }
            if (null !== $code && $booking->getRajaaramTherapy2() !== $code) {
                $booking->setRajaaramTherapy2($code);
                $changed = true;
            }
            if ($booking->getRajaaramTherapy2Date()?->format('Y-m-d') !== $date->format('Y-m-d')) {
                $booking->setRajaaramTherapy2Date($date);
                $changed = true;
            }
            if ($booking->getRajaaramTherapy2Time() !== $time) {
                $booking->setRajaaramTherapy2Time($time);
                $changed = true;
            }
            if (trim((string) $booking->getRajaaramGuest2Name()) !== trim((string) $guest)) {
                $booking->setRajaaramGuest2Name($guest);
                $changed = true;
            }

            return $changed;
        }

        if (null !== $code && $booking->getRajaaramTherapy() !== $code) {
            $booking->setRajaaramTherapy($code);
            $changed = true;
        }
        if ($booking->getRajaaramTherapyDate()?->format('Y-m-d') !== $date->format('Y-m-d')) {
            $booking->setRajaaramTherapyDate($date);
            $changed = true;
        }
        if ($booking->getRajaaramTherapyTime() !== $time) {
            $booking->setRajaaramTherapyTime($time);
            $changed = true;
        }
        if (trim((string) $booking->getRajaaramGuest1Name()) !== trim((string) $guest)) {
            $booking->setRajaaramGuest1Name($guest);
            $changed = true;
        }

        return $changed;
    }

    private function attachPrivatePropsIfNeeded(Booking $booking, Event $event, string $sessionKey): void
    {
        $properties = $event->getExtendedProperties()?->getPrivate() ?? [];
        if (($properties['domoBookingId'] ?? '') === (string) $booking->getId()
            && ($properties['domoTherapySession'] ?? '') === $sessionKey) {
            return;
        }

        $this->attachBookingToTherapyEvent($booking, (string) $event->getId(), $sessionKey);
    }

    /**
     * @param array{
     *     key?: string,
     *     therapyCode?: string,
     *     therapyLabel?: string,
     *     guest?: ?string,
     *     start?: \DateTimeImmutable,
     *     summary?: string
     * } $slot
     * @param array{
     *     therapyCode: ?string,
     *     therapyLabel: ?string,
     *     guest: ?string,
     *     start: ?\DateTimeImmutable,
     *     summary: string
     * } $snapshot
     *
     * @return array<string, mixed>
     */
    private function driftConflict(string $sessionKey, array $slot, array $snapshot): array
    {
        return [
            'kind' => 'drift',
            'session' => $sessionKey,
            'therapy' => $slot['therapyLabel'] ?? '',
            'date' => isset($slot['start']) ? $slot['start']->format('d/m/Y') : '',
            'time' => isset($slot['start']) ? $slot['start']->format('H:i') : '',
            'message' => sprintf(
                'Sessão %s: o calendário Rajaaram tem «%s», a reserva tem «%s».',
                $sessionKey,
                $this->formatTherapySnapshot($snapshot),
                $this->formatTherapySlot($slot),
            ),
            'calendarSummary' => $snapshot['summary'],
            'calendarGuest' => $snapshot['guest'],
            'calendarDate' => $snapshot['start']?->format('d/m/Y'),
            'calendarTime' => $snapshot['start']?->format('H:i'),
            'savedTherapy' => $slot['therapyLabel'] ?? null,
            'savedGuest' => $slot['guest'] ?? null,
            'savedDate' => isset($slot['start']) ? $slot['start']->format('d/m/Y') : null,
            'savedTime' => isset($slot['start']) ? $slot['start']->format('H:i') : null,
        ];
    }

    /**
     * @param array{
     *     therapyCode: ?string,
     *     therapyLabel: ?string,
     *     guest: ?string,
     *     start: ?\DateTimeImmutable,
     *     summary: string
     * } $snapshot
     *
     * @return array<string, mixed>
     */
    private function leftoverDriftConflict(string $sessionKey, array $snapshot): array
    {
        return [
            'kind' => 'drift',
            'session' => $sessionKey,
            'therapy' => $snapshot['therapyLabel'] ?? $snapshot['summary'],
            'date' => $snapshot['start']?->format('d/m/Y'),
            'time' => $snapshot['start']?->format('H:i'),
            'message' => sprintf(
                'Sessão %s continua no calendário Rajaaram (%s) e já não está nesta reserva.',
                $sessionKey,
                $this->formatTherapySnapshot($snapshot),
            ),
            'calendarSummary' => $snapshot['summary'],
            'calendarGuest' => $snapshot['guest'],
            'calendarDate' => $snapshot['start']?->format('d/m/Y'),
            'calendarTime' => $snapshot['start']?->format('H:i'),
            'savedTherapy' => null,
            'savedGuest' => null,
            'savedDate' => null,
            'savedTime' => null,
        ];
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
     *
     * @return array<string, mixed>
     */
    private function busyConflict(string $sessionKey, array $slot, string $busyTitle): array
    {
        return [
            'kind' => 'busy',
            'session' => $sessionKey,
            'therapy' => $slot['therapyLabel'],
            'date' => $slot['start']->format('d/m/Y'),
            'time' => $slot['start']->format('H:i'),
            'busyTitle' => $busyTitle,
            'message' => sprintf(
                'Este horário parece ocupado no calendário Rajaaram: %s %s (%s).',
                $slot['start']->format('d/m/Y'),
                $slot['start']->format('H:i'),
                $busyTitle,
            ),
        ];
    }

    /**
     * @param array{
     *     therapyCode: ?string,
     *     therapyLabel: ?string,
     *     guest: ?string,
     *     start: ?\DateTimeImmutable,
     *     summary: string
     * } $snapshot
     */
    private function formatTherapySnapshot(array $snapshot): string
    {
        $parts = array_filter([
            $snapshot['summary'] ?: $snapshot['therapyLabel'],
            $snapshot['guest'],
            $snapshot['start']?->format('d/m/Y H:i'),
        ], static fn (mixed $value): bool => null !== $value && '' !== $value);

        return implode(' · ', $parts) ?: 'evento Rajaaram';
    }

    /**
     * @param array{
     *     key?: string,
     *     therapyCode?: string,
     *     therapyLabel?: string,
     *     guest?: ?string,
     *     start?: \DateTimeImmutable,
     *     summary?: string
     * } $slot
     */
    private function formatTherapySlot(array $slot): string
    {
        $parts = array_filter([
            $slot['summary'] ?? $slot['therapyLabel'] ?? null,
            $slot['guest'] ?? null,
            isset($slot['start']) ? $slot['start']->format('d/m/Y H:i') : null,
        ], static fn (mixed $value): bool => null !== $value && '' !== $value);

        return implode(' · ', $parts) ?: 'terapia na reserva';
    }

    private function isRajaaramAuthored(Event $event): bool
    {
        $properties = $event->getExtendedProperties()?->getPrivate() ?? [];
        if ('' !== trim((string) ($properties['rajaaramBookingId'] ?? ''))) {
            return true;
        }
        if ('' !== trim((string) ($properties['rajaaramProductKind'] ?? ''))) {
            return true;
        }

        return ($properties['domoManaged'] ?? '') !== 'therapy';
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
        if ($booking->isFromAirbnbIcalBlock() || $booking->hasRajaaramSession()) {
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
