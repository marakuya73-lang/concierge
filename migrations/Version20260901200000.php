<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260901200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store extra guest names on bookings, separate from the booker and therapy recipients';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking ADD extra_guest_names JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking DROP extra_guest_names');
    }
}
