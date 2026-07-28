<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260728180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Rajaaram duo guest name fields to booking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking ADD rajaaram_guest1_name VARCHAR(255) DEFAULT NULL, ADD rajaaram_guest2_name VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking DROP rajaaram_guest1_name, DROP rajaaram_guest2_name');
    }
}
