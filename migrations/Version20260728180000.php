<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260728180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add planned arrival time fields to booking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking ADD planned_arrival_time VARCHAR(5) DEFAULT NULL, ADD planned_arrival_submitted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking DROP planned_arrival_time, DROP planned_arrival_submitted_at');
    }
}
