<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616202000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Extras: lead time hours and remove 24h text from breakfast descriptions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE extra ADD lead_time_hours INT DEFAULT NULL');

        $this->addSql("UPDATE extra SET lead_time_hours = 24 WHERE name_pt LIKE 'Café da manhã%'");

        $this->addSql("UPDATE extra SET description_pt = REPLACE(description_pt, ' Reserve com 24h de antecedência.', '') WHERE lead_time_hours IS NOT NULL");
        $this->addSql("UPDATE extra SET description_en = REPLACE(description_en, ' Book 24h in advance.', '') WHERE lead_time_hours IS NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE extra SET description_pt = CONCAT(description_pt, ' Reserve com 24h de antecedência.') WHERE lead_time_hours = 24 AND description_pt NOT LIKE '%24h de antecedência%'");
        $this->addSql("UPDATE extra SET description_en = CONCAT(description_en, ' Book 24h in advance.') WHERE lead_time_hours = 24 AND description_en NOT LIKE '%24h in advance%'");
        $this->addSql('ALTER TABLE extra DROP lead_time_hours');
    }
}
