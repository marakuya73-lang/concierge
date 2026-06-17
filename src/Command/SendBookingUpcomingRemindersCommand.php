<?php

namespace App\Command;

use App\Service\BookingUpcomingReminderService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:send-booking-upcoming-reminders', description: 'Send admin reminders 24h before booking check-in')]
class SendBookingUpcomingRemindersCommand extends Command
{
    public function __construct(private BookingUpcomingReminderService $reminderService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $result = $this->reminderService->sendDueReminders();

        if (0 === $result['sent']) {
            $io->info('No upcoming booking reminders due right now.');

            return Command::SUCCESS;
        }

        $io->success(sprintf('Sent %d upcoming booking reminder(s).', $result['sent']));

        return Command::SUCCESS;
    }
}
