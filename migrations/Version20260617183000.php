<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617183000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add guest client error reporting for admin alerts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE guest_client_error (id INT AUTO_INCREMENT NOT NULL, booking_id INT DEFAULT NULL, message VARCHAR(500) NOT NULL, route VARCHAR(120) NOT NULL, source VARCHAR(20) NOT NULL, access_code VARCHAR(5) DEFAULT NULL, http_status INT DEFAULT NULL, context LONGTEXT DEFAULT NULL, fingerprint VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_GUEST_CLIENT_ERROR_CREATED (created_at), INDEX IDX_GUEST_CLIENT_ERROR_FINGERPRINT (fingerprint), INDEX IDX_GUEST_CLIENT_ERROR_BOOKING (booking_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE guest_client_error ADD CONSTRAINT FK_GUEST_CLIENT_ERROR_BOOKING FOREIGN KEY (booking_id) REFERENCES booking (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE guest_client_error DROP FOREIGN KEY FK_GUEST_CLIENT_ERROR_BOOKING');
        $this->addSql('DROP TABLE guest_client_error');
    }
}
