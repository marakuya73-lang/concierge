<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616201000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add check-in end time to property';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE property ADD check_in_time_end VARCHAR(10) DEFAULT '18:00' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE property DROP check_in_time_end');
    }
}
