<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617181000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update check-in instructions: ring the bell instead of honk';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE property SET check_in_instructions_pt = REPLACE(check_in_instructions_pt, 'Ao chegar, buzine e aguarde.', 'Ao chegar, toque o sino e aguarde.'), check_in_instructions_en = REPLACE(check_in_instructions_en, 'When you arrive, honk and wait.', 'When you arrive, ring the bell and wait.')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE property SET check_in_instructions_pt = REPLACE(check_in_instructions_pt, 'Ao chegar, toque o sino e aguarde.', 'Ao chegar, buzine e aguarde.'), check_in_instructions_en = REPLACE(check_in_instructions_en, 'When you arrive, ring the bell and wait.', 'When you arrive, honk and wait.')");
    }
}
