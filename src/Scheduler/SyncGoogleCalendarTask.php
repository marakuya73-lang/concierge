<?php

namespace App\Scheduler;

use App\Service\GoogleCalendarSyncService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCronTask('*/15 * * * *', timezone: 'America/Sao_Paulo')]
final class SyncGoogleCalendarTask
{
    public function __construct(
        private GoogleCalendarSyncService $googleCalendarSyncService,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(): void
    {
        try {
            $result = $this->googleCalendarSyncService->sync();
        } catch (\Throwable $exception) {
            $this->logger->error('Google Calendar auto-sync failed: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return;
        }

        if (isset($result['message'])) {
            $this->logger->info('Google Calendar auto-sync skipped: {message}', [
                'message' => $result['message'],
            ]);

            return;
        }

        $this->logger->info('Google Calendar auto-sync completed', $result);
    }
}
