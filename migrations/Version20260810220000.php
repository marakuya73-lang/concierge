<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260810220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Google Calendar sync fields on booking and property';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking ADD google_calendar_event_id VARCHAR(255) DEFAULT NULL, ADD google_calendar_synced_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD google_calendar_etag VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE property ADD google_calendar_id VARCHAR(255) DEFAULT NULL, ADD google_calendar_sync_token TEXT DEFAULT NULL, ADD google_calendar_last_sync_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking DROP google_calendar_event_id, DROP google_calendar_synced_at, DROP google_calendar_etag');
        $this->addSql('ALTER TABLE property DROP google_calendar_id, DROP google_calendar_sync_token, DROP google_calendar_last_sync_at');
    }
}
