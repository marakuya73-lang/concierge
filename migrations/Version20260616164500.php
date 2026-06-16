<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616164500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed sample past bookings for admin history';
    }

    public function up(Schema $schema): void
    {
        // Intentionally empty — past bookings come from Airbnb iCal sync or manual admin entry.
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM booking WHERE access_code IN ('MSILV', 'JSANT', 'ECLAR', 'LMEND')");
    }
}
