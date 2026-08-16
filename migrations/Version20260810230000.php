<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260810230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Google Calendar therapy event IDs and conflict suggestions on booking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking ADD google_calendar_therapy_event_ids JSON DEFAULT NULL, ADD google_calendar_therapy_conflicts JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking DROP google_calendar_therapy_event_ids, DROP google_calendar_therapy_conflicts');
    }
}
