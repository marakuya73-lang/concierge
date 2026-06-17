<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Track admin upcoming booking reminder delivery';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking ADD upcoming_reminder_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking DROP upcoming_reminder_sent_at');
    }
}
