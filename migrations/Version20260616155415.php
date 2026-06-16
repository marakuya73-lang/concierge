<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260616155415 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE blocked_period ADD last_synced_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE booking ADD ical_summary VARCHAR(255) DEFAULT NULL, ADD last_synced_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE extra CHANGE min_guests min_guests INT NOT NULL');
        $this->addSql('ALTER TABLE property ADD airbnb_ical_last_sync_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE blocked_period DROP last_synced_at');
        $this->addSql('ALTER TABLE booking DROP ical_summary, DROP last_synced_at');
        $this->addSql('ALTER TABLE extra CHANGE min_guests min_guests INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE property DROP airbnb_ical_last_sync_at');
    }
}
