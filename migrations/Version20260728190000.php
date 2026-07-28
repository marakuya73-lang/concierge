<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260728190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Backfill existing Rajaaram bookings as individual therapies';
    }

    public function up(Schema $schema): void
    {
        $columns = array_keys($this->connection->createSchemaManager()->listTableColumns('booking'));

        if (!in_array('rajaaram_guest1_name', $columns, true)) {
            $this->addSql('ALTER TABLE booking ADD rajaaram_guest1_name VARCHAR(255) DEFAULT NULL, ADD rajaaram_guest2_name VARCHAR(255) DEFAULT NULL');
        }

        if (!in_array('rajaaram_is_duo', $columns, true)) {
            $this->addSql('ALTER TABLE booking ADD rajaaram_therapy_date DATE DEFAULT NULL, ADD rajaaram_is_duo TINYINT(1) DEFAULT NULL, ADD rajaaram_therapy2 VARCHAR(50) DEFAULT NULL, ADD rajaaram_therapy2_date DATE DEFAULT NULL, ADD rajaaram_therapy2_time VARCHAR(5) DEFAULT NULL');
        }

        $this->addSql(<<<'SQL'
            UPDATE booking
            SET rajaaram_is_duo = 0,
                rajaaram_guest1_name = NULL,
                rajaaram_guest2_name = NULL,
                rajaaram_therapy2 = NULL,
                rajaaram_therapy2_date = NULL,
                rajaaram_therapy2_time = NULL
            WHERE (rajaaram_is_duo IS NULL OR rajaaram_is_duo = 0)
              AND (
                source = 'Rajaaram'
                OR rajaaram_therapy IS NOT NULL
                OR rajaaram_therapy_date IS NOT NULL
                OR rajaaram_therapy_time IS NOT NULL
                OR rajaaram_breakfast_included IS NOT NULL
              )
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE booking
            SET rajaaram_is_duo = NULL
            WHERE rajaaram_is_duo = 0
              AND (
                source = 'Rajaaram'
                OR rajaaram_therapy IS NOT NULL
                OR rajaaram_therapy_date IS NOT NULL
                OR rajaaram_therapy_time IS NOT NULL
                OR rajaaram_breakfast_included IS NOT NULL
              )
            SQL);
    }
}
