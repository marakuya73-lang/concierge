<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616211000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add manual_dates flag to protect admin-edited booking dates from iCal sync';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking ADD manual_dates TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking DROP manual_dates');
    }
}
