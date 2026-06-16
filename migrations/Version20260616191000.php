<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616191000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add bilingual address fields for guest location page';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE property ADD address_pt LONGTEXT DEFAULT NULL, ADD address_en LONGTEXT DEFAULT NULL');
        $this->addSql("UPDATE property SET address_pt = 'Estrada de terra do km 14 da GO-239, 2,3 km após o entroncamento — Alto Paraíso de Goiás, GO', address_en = 'Dirt road at km 14 of GO-239, 2.3 km after the junction — Alto Paraíso de Goiás, GO, Brazil' WHERE address_pt IS NULL");
        $this->addSql('ALTER TABLE property MODIFY address_pt LONGTEXT NOT NULL, MODIFY address_en LONGTEXT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE property DROP address_pt, DROP address_en');
    }
}
