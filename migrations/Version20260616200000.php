<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Rajaaram journey fields to booking (therapy, time, breakfast)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking ADD rajaaram_therapy VARCHAR(50) DEFAULT NULL, ADD rajaaram_therapy_time VARCHAR(5) DEFAULT NULL, ADD rajaaram_breakfast_included TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking DROP rajaaram_therapy, DROP rajaaram_therapy_time, DROP rajaaram_breakfast_included');
    }
}
