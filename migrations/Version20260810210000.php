<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260810210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add guest locale to booking (default Portuguese)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE booking ADD guest_locale VARCHAR(2) DEFAULT 'pt' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking DROP guest_locale');
    }
}
