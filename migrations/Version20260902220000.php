<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260902220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remember dismissed Rajaaram calendar therapy suggestions on bookings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking ADD rajaaram_dismissed_therapy_event_ids JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking DROP rajaaram_dismissed_therapy_event_ids');
    }
}
