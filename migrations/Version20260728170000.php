<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260728170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Rajaaram duo therapy fields and therapy dates to booking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking ADD rajaaram_therapy_date DATE DEFAULT NULL, ADD rajaaram_is_duo TINYINT(1) DEFAULT NULL, ADD rajaaram_therapy2 VARCHAR(50) DEFAULT NULL, ADD rajaaram_therapy2_date DATE DEFAULT NULL, ADD rajaaram_therapy2_time VARCHAR(5) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking DROP rajaaram_therapy_date, DROP rajaaram_is_duo, DROP rajaaram_therapy2, DROP rajaaram_therapy2_date, DROP rajaaram_therapy2_time');
    }
}
