<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260728150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Track guest login counts and page view activity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking ADD last_login_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD login_count INT DEFAULT 0 NOT NULL');
        $this->addSql('CREATE TABLE guest_activity_log (id INT AUTO_INCREMENT NOT NULL, booking_id INT DEFAULT NULL, access_code VARCHAR(5) DEFAULT NULL, type VARCHAR(20) NOT NULL, section VARCHAR(80) NOT NULL, label VARCHAR(120) DEFAULT NULL, fingerprint VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_GUEST_ACTIVITY_CREATED (created_at), INDEX IDX_GUEST_ACTIVITY_FINGERPRINT (fingerprint), INDEX IDX_GUEST_ACTIVITY_BOOKING (booking_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE guest_activity_log ADD CONSTRAINT FK_GUEST_ACTIVITY_BOOKING FOREIGN KEY (booking_id) REFERENCES booking (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE guest_activity_log DROP FOREIGN KEY FK_GUEST_ACTIVITY_BOOKING');
        $this->addSql('DROP TABLE guest_activity_log');
        $this->addSql('ALTER TABLE booking DROP last_login_at, DROP login_count');
    }
}
