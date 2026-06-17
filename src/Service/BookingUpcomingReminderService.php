<?php

namespace App\Service;

use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class BookingUpcomingReminderService
{
    private const TIMEZONE = 'America/Sao_Paulo';

    public function __construct(
        private BookingRepository $bookingRepository,
        private BookingUpcomingReminderNotificationService $notificationService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    /** @return array{sent: int, skipped: int} */
    public function sendDueReminders(?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable('now', new \DateTimeZone(self::TIMEZONE));
        $checkInDate = $now->modify('+24 hours')->setTime(0, 0);
        $bookings = $this->bookingRepository->findNeedingUpcomingReminder($checkInDate);

        $sent = 0;

        foreach ($bookings as $booking) {
            $this->notificationService->notifyAdmin($booking);
            $booking->setUpcomingReminderSentAt(new \DateTimeImmutable());
            ++$sent;
        }

        if ($sent > 0) {
            $this->entityManager->flush();
        }

        $this->logger->info('Booking upcoming reminders processed', [
            'checkInDate' => $checkInDate->format('Y-m-d'),
            'sent' => $sent,
        ]);

        return ['sent' => $sent, 'skipped' => 0];
    }
}
