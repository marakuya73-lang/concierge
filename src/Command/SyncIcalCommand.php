<?php

namespace App\Command;

use App\Service\IcalSyncService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:sync-ical', description: 'Sync bookings from Airbnb iCal feed')]
class SyncIcalCommand extends Command
{
    public function __construct(private IcalSyncService $icalSyncService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $result = $this->icalSyncService->sync();

        if (isset($result['message'])) {
            $io->warning($result['message']);

            return Command::SUCCESS;
        }

        $io->success(sprintf(
            'Sync complete: %d created, %d updated, %d site (Airbnb calendar), %d completed, %d cancelled, %d historical preserved.',
            $result['created'],
            $result['updated'],
            $result['siteBookings'] ?? 0,
            $result['completed'] ?? 0,
            $result['cancelled'] ?? 0,
            $result['preserved'] ?? 0
        ));

        return Command::SUCCESS;
    }
}
