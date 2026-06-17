<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove em dashes from arrival instructions (Como chegar)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE property SET arrival_instructions_pt = REPLACE(arrival_instructions_pt, ' — ', ', '), arrival_instructions_en = REPLACE(arrival_instructions_en, ' — ', ', ')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE property SET arrival_instructions_pt = REPLACE(arrival_instructions_pt, ', ', ' — '), arrival_instructions_en = REPLACE(arrival_instructions_en, ', ', ' — ')");
    }
}
