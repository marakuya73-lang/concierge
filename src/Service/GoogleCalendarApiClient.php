<?php

namespace App\Service;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\FreeBusyCalendar;
use Google\Service\Calendar\FreeBusyRequest;
use Google\Service\Calendar\FreeBusyRequestItem;
use Psr\Log\LoggerInterface;

class GoogleCalendarApiClient
{
    private ?Calendar $calendar = null;

    public function __construct(
        private string $serviceAccountJsonPath,
        private LoggerInterface $logger,
    ) {
    }

    public function isConfigured(): bool
    {
        $path = trim($this->serviceAccountJsonPath);

        return '' !== $path && is_readable($path);
    }

    /**
     * @return list<Event>
     */
    public function listChangedEvents(string $calendarId, ?string $syncToken): array
    {
        $service = $this->getCalendarService();
        $events = [];
        $pageToken = null;

        $nextSyncToken = $syncToken;

        do {
            $params = [
                'maxResults' => 250,
                'showDeleted' => true,
                'singleEvents' => true,
            ];

            if ($syncToken) {
                $params['syncToken'] = $syncToken;
            } else {
                $params['timeMin'] = (new \DateTimeImmutable('-2 years'))->format(\DateTimeInterface::RFC3339);
            }

            if ($pageToken) {
                $params['pageToken'] = $pageToken;
            }

            try {
                $result = $service->events->listEvents($calendarId, $params);
            } catch (\Google\Service\Exception $exception) {
                if (410 === $exception->getCode()) {
                    $this->logger->warning('Google Calendar sync token expired, performing full resync.');

                    return $this->listChangedEvents($calendarId, null);
                }

                throw $exception;
            }

            foreach ($result->getItems() ?? [] as $event) {
                $events[] = $event;
            }

            if ($result->getNextSyncToken()) {
                $nextSyncToken = $result->getNextSyncToken();
            }

            $pageToken = $result->getNextPageToken();
        } while ($pageToken);

        $this->lastSyncToken = $nextSyncToken;

        return $events;
    }

    private ?string $lastSyncToken = null;

    public function getLastSyncToken(): ?string
    {
        return $this->lastSyncToken;
    }

    public function getEvent(string $calendarId, string $eventId): Event
    {
        return $this->getCalendarService()->events->get($calendarId, $eventId);
    }

    public function insertEvent(string $calendarId, Event $event): Event
    {
        return $this->getCalendarService()->events->insert($calendarId, $event);
    }

    public function patchEvent(string $calendarId, string $eventId, Event $event): Event
    {
        return $this->getCalendarService()->events->patch($calendarId, $eventId, $event);
    }

    /**
     * @return list<Event>
     */
    public function listEventsBetween(string $calendarId, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $service = $this->getCalendarService();
        $events = [];
        $pageToken = null;

        do {
            $result = $service->events->listEvents($calendarId, [
                'timeMin' => $from->format(\DateTimeInterface::RFC3339),
                'timeMax' => $to->format(\DateTimeInterface::RFC3339),
                'singleEvents' => true,
                'orderBy' => 'startTime',
                'maxResults' => 250,
                'pageToken' => $pageToken,
            ]);

            foreach ($result->getItems() ?? [] as $event) {
                $events[] = $event;
            }

            $pageToken = $result->getNextPageToken();
        } while ($pageToken);

        return $events;
    }

    /**
     * @return list<array{start: \DateTimeImmutable, end: \DateTimeImmutable}>
     */
    public function getBusyIntervals(string $calendarId, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        if ('' === trim($calendarId)) {
            return [];
        }

        try {
            $service = $this->getCalendarService();
            $request = new FreeBusyRequest();
            $request->setTimeMin($from->format(\DateTimeInterface::RFC3339));
            $request->setTimeMax($to->format(\DateTimeInterface::RFC3339));
            $item = new FreeBusyRequestItem();
            $item->setId($calendarId);
            $request->setItems([$item]);
            $response = $service->freebusy->query($request);
        } catch (\Throwable $exception) {
            $this->logger->error('Google Calendar FreeBusy query failed: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return [];
        }

        $calendars = $response->getCalendars();
        if (!$calendars instanceof \Traversable && !\is_array($calendars)) {
            return [];
        }

        $busy = [];
        foreach ($calendars as $calendar) {
            if (!$calendar instanceof FreeBusyCalendar) {
                continue;
            }
            foreach ($calendar->getBusy() ?? [] as $interval) {
                if (!isset($interval['start'], $interval['end'])) {
                    continue;
                }
                $busy[] = [
                    'start' => new \DateTimeImmutable($interval['start']),
                    'end' => new \DateTimeImmutable($interval['end']),
                ];
            }
        }

        return $busy;
    }

    private function getCalendarService(): Calendar
    {
        if (null !== $this->calendar) {
            return $this->calendar;
        }

        $credentials = $this->resolveCredentialsPath();
        $client = new Client();
        $client->setAuthConfig($credentials);
        $client->setScopes([Calendar::CALENDAR]);

        $this->calendar = new Calendar($client);

        return $this->calendar;
    }

    private function resolveCredentialsPath(): string
    {
        $value = trim($this->serviceAccountJsonPath);

        if (is_file($value)) {
            return $value;
        }

        $projectRelative = dirname(__DIR__, 2).'/'.$value;
        if (is_file($projectRelative)) {
            return $projectRelative;
        }

        throw new \RuntimeException(sprintf('Google service account credentials not found at "%s".', $value));
    }
}
