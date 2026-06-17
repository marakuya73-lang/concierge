<?php

namespace App\Scheduler;

use App\Service\BookingUpcomingReminderService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCronTask('0 * * * *', timezone: 'America/Sao_Paulo')]
final class BookingUpcomingReminderTask
{
    public function __construct(
        private BookingUpcomingReminderService $reminderService,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(): void
    {
        $result = $this->reminderService->sendDueReminders();

        if (0 === $result['sent']) {
            return;
        }

        $this->logger->info('Booking upcoming reminder task completed', $result);
    }
}
