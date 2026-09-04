<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260904010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'WhatsApp of the Rajaaram therapy client, separate from the stay booker number';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking ADD rajaaram_guest1_whatsapp VARCHAR(30) DEFAULT NULL');
        $this->addSql('ALTER TABLE booking ADD rajaaram_guest2_whatsapp VARCHAR(30) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking DROP rajaaram_guest1_whatsapp');
        $this->addSql('ALTER TABLE booking DROP rajaaram_guest2_whatsapp');
    }
}
