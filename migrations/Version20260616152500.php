<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616152500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Connect Airbnb iCal calendar URL';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
UPDATE property SET airbnb_ical_url = 'https://www.airbnb.com/calendar/ical/1324818447207417708.ics?t=7f8c792dd44d490e80de52cfc3e7ea63'
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE property SET airbnb_ical_url = NULL');
    }
}
