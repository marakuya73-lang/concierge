<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616182000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize booking source values to Airbnb, Site, Rajaaram, Tucanto';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE booking SET source = 'Site' WHERE source IN ('Direct', 'Direct (Airbnb)', 'Booking', 'Other')");
    }

    public function down(Schema $schema): void
    {
    }
}
