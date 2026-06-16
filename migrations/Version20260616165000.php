<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616165000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove demo seed bookings that are not from Airbnb';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM booking WHERE access_code IN ('MSILV', 'JSANT', 'ECLAR', 'LMEND') OR external_uid LIKE 'seed-booking-%'");
    }

    public function down(Schema $schema): void
    {
        // Demo data intentionally not restored.
    }
}
