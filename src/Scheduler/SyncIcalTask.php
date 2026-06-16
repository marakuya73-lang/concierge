<?php

namespace App\Scheduler;

use App\Service\IcalSyncService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCronTask('0 6 * * *', timezone: 'America/Sao_Paulo')]
final class SyncIcalTask
{
    public function __construct(
        private IcalSyncService $icalSyncService,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(): void
    {
        $result = $this->icalSyncService->sync();

        if (isset($result['message'])) {
            $this->logger->warning('Airbnb iCal auto-sync skipped: {message}', [
                'message' => $result['message'],
            ]);

            return;
        }

        $this->logger->info('Airbnb iCal auto-sync completed', $result);
    }
}
