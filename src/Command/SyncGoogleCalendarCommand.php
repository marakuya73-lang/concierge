<?php

namespace App\Command;

use App\Service\GoogleCalendarSyncService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:sync-google-calendar', description: 'Bidirectional sync between bookings and Google Calendar')]
class SyncGoogleCalendarCommand extends Command
{
    public function __construct(private GoogleCalendarSyncService $googleCalendarSyncService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $result = $this->googleCalendarSyncService->sync();

        if (isset($result['message'])) {
            $io->warning($result['message']);

            return Command::SUCCESS;
        }

        $io->success(sprintf(
            'Google Calendar sync complete: %d updated from Google, %d stays pushed (%d created), %d therapies pushed (%d created), %d therapy conflicts.',
            $result['updatedFromGoogle'] ?? 0,
            $result['pushed'] ?? 0,
            $result['created'] ?? 0,
            $result['therapyPushed'] ?? 0,
            $result['therapyCreated'] ?? 0,
            $result['therapyConflicts'] ?? 0,
        ));

        return Command::SUCCESS;
    }
}
