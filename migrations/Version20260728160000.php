<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260728160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Per-booking extra visibility overrides and custom booking extras';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE booking_disabled_extra (id INT AUTO_INCREMENT NOT NULL, booking_id INT NOT NULL, extra_id INT NOT NULL, UNIQUE INDEX uniq_booking_extra_disabled (booking_id, extra_id), INDEX IDX_BDE_BOOKING (booking_id), INDEX IDX_BDE_EXTRA (extra_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE booking_disabled_extra ADD CONSTRAINT FK_BDE_BOOKING FOREIGN KEY (booking_id) REFERENCES booking (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE booking_disabled_extra ADD CONSTRAINT FK_BDE_EXTRA FOREIGN KEY (extra_id) REFERENCES extra (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE booking_extra CHANGE extra_id extra_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE booking_extra ADD custom_name_pt VARCHAR(255) DEFAULT NULL, ADD custom_name_en VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking_disabled_extra DROP FOREIGN KEY FK_BDE_BOOKING');
        $this->addSql('ALTER TABLE booking_disabled_extra DROP FOREIGN KEY FK_BDE_EXTRA');
        $this->addSql('DROP TABLE booking_disabled_extra');
        $this->addSql('ALTER TABLE booking_extra DROP custom_name_pt, DROP custom_name_en');
        $this->addSql('ALTER TABLE booking_extra CHANGE extra_id extra_id INT NOT NULL');
    }
}
