<?php

namespace App\Service;

use App\Entity\Booking;
use Psr\Log\LoggerInterface;

class BookingCalendarSyncDispatcher
{
    public function __construct(
        private GoogleCalendarSyncService $googleCalendarSyncService,
        private LoggerInterface $logger,
    ) {
    }

    public function afterBookingSaved(Booking $booking, bool $forceTherapy = false): void
    {
        if (!$this->googleCalendarSyncService->isConfigured()
            && !$this->googleCalendarSyncService->isTherapyCalendarConfigured()) {
            return;
        }

        try {
            $this->googleCalendarSyncService->pushBooking($booking, $forceTherapy);
        } catch (\Throwable $exception) {
            $this->logger->error('Google Calendar push failed for booking {id}: {message}', [
                'id' => $booking->getId(),
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function pullTherapiesFromRajaaram(Booking $booking): void
    {
        if (!$this->googleCalendarSyncService->isTherapyCalendarConfigured()) {
            return;
        }

        try {
            $this->googleCalendarSyncService->pullTherapiesFromRajaaram($booking);
        } catch (\Throwable $exception) {
            $this->logger->error('Rajaaram calendar pull failed for booking {id}: {message}', [
                'id' => $booking->getId(),
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function afterBookingDeleted(Booking $booking): void
    {
        if (!$this->googleCalendarSyncService->isConfigured() || !$booking->getGoogleCalendarEventId()) {
            return;
        }

        try {
            $this->googleCalendarSyncService->cancelBookingEvent($booking);
        } catch (\Throwable $exception) {
            $this->logger->error('Google Calendar cancel failed for booking {id}: {message}', [
                'id' => $booking->getId(),
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function afterIcalSync(): void
    {
        if (!$this->googleCalendarSyncService->isConfigured()) {
            return;
        }

        try {
            $this->googleCalendarSyncService->sync();
        } catch (\Throwable $exception) {
            $this->logger->error('Google Calendar sync after iCal failed: {message}', [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
