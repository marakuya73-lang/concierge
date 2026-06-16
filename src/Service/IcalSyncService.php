<?php

namespace App\Service;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use App\Repository\PropertyRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class IcalSyncService
{
    public function __construct(
        private PropertyRepository $propertyRepository,
        private BookingRepository $bookingRepository,
        private AccessCodeGenerator $accessCodeGenerator,
        private BookingLifecycleService $bookingLifecycleService,
        private HttpClientInterface $httpClient,
    ) {
    }

    /** @return array<string, int|string|null> */
    public function sync(): array
    {
        $property = $this->propertyRepository->getOrCreate();
        $url = $property->getAirbnbIcalUrl();

        if (!$url) {
            return ['created' => 0, 'updated' => 0, 'skipped' => 0, 'siteBookings' => 0, 'completed' => 0, 'cancelled' => 0, 'preserved' => 0, 'message' => 'No iCal URL configured'];
        }

        $response = $this->httpClient->request('GET', $url);
        $content = $response->getContent();
        $events = $this->parseIcal($content);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $siteBookings = 0;
        $today = new \DateTimeImmutable('today');
        $syncedAt = new \DateTimeImmutable();
        $em = $this->bookingRepository->getEntityManager();
        $seenBookingUids = [];

        foreach ($events as $event) {
            if ($event['end'] <= $event['start']) {
                ++$skipped;
                continue;
            }

            $seenBookingUids[] = $event['uid'];
            $existing = $this->bookingRepository->findByExternalUid($event['uid']);
            $isSiteImport = $this->isBlockedEvent($event['summary']);

            if ($existing) {
                if ($isSiteImport) {
                    if ($this->applySiteBookingEvent($existing, $event, $syncedAt, $today)) {
                        ++$siteBookings;
                    }
                } elseif ($this->applyBookingEvent($existing, $event, $syncedAt, $today)) {
                    ++$updated;
                }
                continue;
            }

            if ($isSiteImport) {
                $em->persist($this->createSiteBooking($event, $syncedAt, $today));
                ++$siteBookings;
                continue;
            }

            $booking = new Booking();
            $booking->setExternalUid($event['uid']);
            $booking->setGuestName($this->extractGuestName($event['summary'] ?? 'Airbnb Guest'));
            $booking->setCheckIn($event['start']);
            $booking->setCheckOut($event['end']);
            $booking->setSource(Booking::SOURCE_AIRBNB);
            $booking->setAccessCode($this->accessCodeGenerator->generateUnique());
            $this->applyBookingEvent($booking, $event, $syncedAt, $today);

            $em->persist($booking);
            ++$created;
        }

        $reconciled = $this->reconcileMissingBookings($seenBookingUids, $today, $syncedAt);
        $completed = $this->bookingLifecycleService->markPastBookingsCompleted($today);

        $property->setAirbnbIcalLastSyncAt($syncedAt);
        $em->flush();

        return compact('created', 'updated', 'skipped', 'siteBookings', 'completed')
            + $reconciled
            + ['syncedAt' => $syncedAt->format(\DateTimeInterface::ATOM)];
    }

    /**
     * @param array{uid: string, start: \DateTimeImmutable, end: \DateTimeImmutable, summary: ?string} $event
     */
    private function createSiteBooking(array $event, \DateTimeImmutable $syncedAt, \DateTimeImmutable $today): Booking
    {
        $booking = new Booking();
        $booking->setExternalUid($event['uid']);
        $booking->setGuestName(Booking::GUEST_NAME_PENDING);
        $booking->setCheckIn($event['start']);
        $booking->setCheckOut($event['end']);
        $booking->setSource(Booking::SOURCE_SITE);
        $booking->setIcalSummary($event['summary'] ?? 'Not available');
        $booking->setAccessCode($this->accessCodeGenerator->generateUnique());
        $booking->setLastSyncedAt($syncedAt);
        $this->bookingLifecycleService->refreshStatus($booking, $today);

        return $booking;
    }

    /**
     * @param array{uid: string, start: \DateTimeImmutable, end: \DateTimeImmutable, summary: ?string} $event
     */
    private function applySiteBookingEvent(Booking $booking, array $event, \DateTimeImmutable $syncedAt, \DateTimeImmutable $today): bool
    {
        if (!$booking->isFromAirbnbIcalBlock() && !$booking->isImportedFromAirbnb()) {
            $booking->setSource(Booking::SOURCE_SITE);
        }

        $changed = false;

        if (!$booking->isManualDates()) {
            $changed = $booking->getCheckIn()->format('Y-m-d') !== $event['start']->format('Y-m-d')
                || $booking->getCheckOut()->format('Y-m-d') !== $event['end']->format('Y-m-d');

            $booking->setCheckIn($event['start']);
            $booking->setCheckOut($event['end']);
        }

        $summary = $event['summary'] ?? 'Not available';
        if ($booking->getIcalSummary() !== $summary) {
            $booking->setIcalSummary($summary);
            $changed = true;
        }

        if (!$booking->getAccessCode()) {
            $booking->setAccessCode($this->accessCodeGenerator->generateUnique());
            $changed = true;
        }

        $booking->setLastSyncedAt($syncedAt);
        $previousStatus = $booking->getStatus();
        $this->bookingLifecycleService->refreshStatus($booking, $today);

        if ($previousStatus !== $booking->getStatus()) {
            $changed = true;
        }

        return $changed;
    }

    /**
     * @param array{uid: string, start: \DateTimeImmutable, end: \DateTimeImmutable, summary: ?string} $event
     */
    private function applyBookingEvent(Booking $booking, array $event, \DateTimeImmutable $syncedAt, \DateTimeImmutable $today): bool
    {
        $changed = false;

        if (!$booking->isManualDates()) {
            $changed = $booking->getCheckIn()->format('Y-m-d') !== $event['start']->format('Y-m-d')
                || $booking->getCheckOut()->format('Y-m-d') !== $event['end']->format('Y-m-d');

            $booking->setCheckIn($event['start']);
            $booking->setCheckOut($event['end']);
        }

        if ($event['summary']) {
            if ($booking->getIcalSummary() !== $event['summary']) {
                $changed = true;
            }
            $booking->setIcalSummary($event['summary']);

            if (!$booking->isFromAirbnbIcalBlock()) {
                $guestName = $this->extractGuestName($event['summary']);
                if ($booking->getGuestName() !== $guestName) {
                    $booking->setGuestName($guestName);
                    $changed = true;
                }
            }
        }

        $booking->setLastSyncedAt($syncedAt);
        $previousStatus = $booking->getStatus();
        $this->bookingLifecycleService->refreshStatus($booking, $today);

        if ($previousStatus !== $booking->getStatus()) {
            $changed = true;
        }

        return $changed;
    }

    /** @param list<string> $seenUids @return array{cancelled: int, preserved: int} */
    private function reconcileMissingBookings(array $seenUids, \DateTimeImmutable $today, \DateTimeImmutable $syncedAt): array
    {
        $cancelled = 0;
        $preserved = 0;
        $seenLookup = array_fill_keys($seenUids, true);

        foreach ($this->bookingRepository->findIcalSynced() as $booking) {
            $uid = $booking->getExternalUid();
            if (!$uid || isset($seenLookup[$uid])) {
                continue;
            }

            if ($booking->isManualDates()) {
                ++$preserved;
                continue;
            }

            if ($booking->getCheckOut() <= $today) {
                if (Booking::STATUS_CONFIRMED === $booking->getStatus()) {
                    $booking->setStatus(Booking::STATUS_COMPLETED);
                }
                ++$preserved;
                continue;
            }

            if (Booking::STATUS_CANCELLED === $booking->getStatus() || Booking::STATUS_COMPLETED === $booking->getStatus()) {
                ++$preserved;
                continue;
            }

            $booking->setStatus(Booking::STATUS_CANCELLED);
            $note = sprintf(
                '[Sync %s] Cancelado automaticamente: reserva não consta mais no calendário Airbnb.',
                $syncedAt->format('d/m/Y H:i')
            );
            $existingNotes = trim((string) $booking->getNotes());
            $booking->setNotes($existingNotes ? $existingNotes."\n".$note : $note);
            ++$cancelled;
        }

        return compact('cancelled', 'preserved');
    }

    private function isBlockedEvent(?string $summary): bool
    {
        return Booking::isBlockedIcalSummary($summary);
    }

    /** @return list<array{uid: string, start: \DateTimeImmutable, end: \DateTimeImmutable, summary: ?string}> */
    private function parseIcal(string $content): array
    {
        $events = [];
        $lines = preg_split('/\r\n|\n|\r/', $content) ?: [];
        $current = null;
        $inEvent = false;

        foreach ($lines as $line) {
            if (str_starts_with($line, ' ')) {
                if ($current && isset($current['_lastKey'])) {
                    $current[$current['_lastKey']] .= ltrim($line);
                }
                continue;
            }

            if ('BEGIN:VEVENT' === $line) {
                $inEvent = true;
                $current = [];
                continue;
            }

            if ('END:VEVENT' === $line && $inEvent && $current) {
                if (isset($current['UID'], $current['DTSTART'], $current['DTEND'])) {
                    $events[] = [
                        'uid' => $current['UID'],
                        'start' => $this->parseIcalDate($current['DTSTART']),
                        'end' => $this->parseIcalDate($current['DTEND']),
                        'summary' => $current['SUMMARY'] ?? null,
                    ];
                }
                $inEvent = false;
                $current = null;
                continue;
            }

            if ($inEvent && str_contains($line, ':')) {
                [$key, $value] = explode(':', $line, 2);
                $key = explode(';', $key)[0];
                $current[$key] = $value;
                $current['_lastKey'] = $key;
            }
        }

        return $events;
    }

    private function parseIcalDate(string $value): \DateTimeImmutable
    {
        $value = trim($value);
        if (preg_match('/^(\d{4})(\d{2})(\d{2})/', $value, $m)) {
            return new \DateTimeImmutable(sprintf('%s-%s-%s', $m[1], $m[2], $m[3]));
        }

        return new \DateTimeImmutable($value);
    }

    private function extractGuestName(string $summary): string
    {
        $summary = trim($summary);

        if (preg_match('/^Reserved\s*[-–—:]\s*(.+)$/i', $summary, $m)) {
            return trim($m[1]);
        }

        if (preg_match('/^Airbnb\s*\((.+)\)$/i', $summary, $m)) {
            $name = trim($m[1]);
            if (!$this->isBlockedEvent($name)) {
                return $name;
            }
        }

        if (preg_match('/^Booking\s*[-–—:]\s*(.+)$/i', $summary, $m)) {
            return trim($m[1]);
        }

        return $summary ?: 'Airbnb Guest';
    }
}
