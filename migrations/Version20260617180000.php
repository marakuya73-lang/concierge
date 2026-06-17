<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Correct property address to Rua do Paraíso, Moinho';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE property SET address_pt = 'Rua do Paraíso, Moinho, Alto Paraíso de Goiás, GO', address_en = 'Rua do Paraíso, Moinho, Alto Paraíso de Goiás, GO, Brazil'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE property SET address_pt = 'Estrada de terra do km 14 da GO-239, 2,3 km após o entroncamento, Alto Paraíso de Goiás, GO', address_en = 'Dirt road at km 14 of GO-239, 2.3 km after the junction, Alto Paraíso de Goiás, GO, Brazil'");
    }
}
