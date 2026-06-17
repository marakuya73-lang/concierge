<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix property map coordinates to Moinho (Rajaaram) location';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE property SET latitude = '-14.061994', longitude = '-47.467243', map_url = 'https://www.google.com/maps/search/?api=1&query=-14.061994,-47.467243'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE property SET latitude = '-14.1306', longitude = '-47.5083', map_url = 'https://maps.app.goo.gl/ZbJV4Bdd977fVCyV7'");
    }
}
