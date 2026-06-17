<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set 24h lead time on transfer extra';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE extra SET lead_time_hours = 24 WHERE category = 'transfer'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE extra SET lead_time_hours = NULL WHERE category = 'transfer'");
    }
}
