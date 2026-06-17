<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add admin push notification subscriptions table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE admin_push_subscription (id INT AUTO_INCREMENT NOT NULL, endpoint VARCHAR(512) NOT NULL, public_key VARCHAR(255) NOT NULL, auth_token VARCHAR(255) NOT NULL, content_encoding VARCHAR(32) DEFAULT \'aesgcm\' NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX uniq_push_endpoint (endpoint), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE admin_push_subscription');
    }
}
