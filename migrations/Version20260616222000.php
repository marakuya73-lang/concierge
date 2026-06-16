<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616222000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update property Google Maps link to correct short URL';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE property SET map_url = 'https://maps.app.goo.gl/ZbJV4Bdd977fVCyV7'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE property SET map_url = 'https://maps.google.com/?q=-14.1306,-47.5083'");
    }
}
