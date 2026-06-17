<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update property Google Maps link to correct short URL';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE property SET map_url = 'https://maps.app.goo.gl/APPumVUKa8P1LPgg8', latitude = '-14.058769', longitude = '-47.466251'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE property SET map_url = 'https://www.google.com/maps/search/?api=1&query=-14.061994,-47.467243', latitude = '-14.061994', longitude = '-47.467243'");
    }
}
