<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616223000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add editable dome entrance code on property';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE property ADD dome_entrance_code VARCHAR(20) NOT NULL DEFAULT '3666'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE property DROP dome_entrance_code');
    }
}
