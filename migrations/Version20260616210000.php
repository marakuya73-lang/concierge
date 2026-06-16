<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add guest WhatsApp number to booking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking ADD guest_whatsapp VARCHAR(30) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking DROP guest_whatsapp');
    }
}
