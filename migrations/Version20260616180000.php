<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add stay price to bookings and notes to blocked periods';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking ADD stay_price DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE blocked_period ADD notes LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking DROP stay_price');
        $this->addSql('ALTER TABLE blocked_period DROP notes');
    }
}
