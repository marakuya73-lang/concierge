<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260616144445 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE booking (id INT AUTO_INCREMENT NOT NULL, guest_name VARCHAR(255) NOT NULL, guest_email VARCHAR(255) NOT NULL, check_in DATE NOT NULL, check_out DATE NOT NULL, guests INT NOT NULL, access_code VARCHAR(5) NOT NULL, source VARCHAR(50) NOT NULL, status VARCHAR(50) NOT NULL, notes LONGTEXT DEFAULT NULL, external_uid VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_E00CEDDE81CC569E (access_code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE booking_extra (id INT AUTO_INCREMENT NOT NULL, quantity INT NOT NULL, status VARCHAR(50) NOT NULL, notes LONGTEXT DEFAULT NULL, requested_by VARCHAR(20) NOT NULL, price_at_booking DOUBLE PRECISION DEFAULT NULL, created_at DATETIME NOT NULL, booking_id INT NOT NULL, extra_id INT NOT NULL, INDEX IDX_DC43F0D03301C60 (booking_id), INDEX IDX_DC43F0D02B959FC6 (extra_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE extra (id INT AUTO_INCREMENT NOT NULL, name_pt VARCHAR(255) NOT NULL, name_en VARCHAR(255) NOT NULL, description_pt LONGTEXT NOT NULL, description_en LONGTEXT NOT NULL, price DOUBLE PRECISION NOT NULL, currency VARCHAR(10) NOT NULL, category VARCHAR(50) NOT NULL, icon VARCHAR(50) NOT NULL, active TINYINT NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE property (id INT AUTO_INCREMENT NOT NULL, name_pt VARCHAR(255) NOT NULL, tagline_pt LONGTEXT NOT NULL, description_pt LONGTEXT NOT NULL, check_in_instructions_pt LONGTEXT NOT NULL, location_details_pt LONGTEXT NOT NULL, arrival_instructions_pt LONGTEXT NOT NULL, name_en VARCHAR(255) NOT NULL, tagline_en LONGTEXT NOT NULL, description_en LONGTEXT NOT NULL, check_in_instructions_en LONGTEXT NOT NULL, location_details_en LONGTEXT NOT NULL, arrival_instructions_en LONGTEXT NOT NULL, wifi_name VARCHAR(100) NOT NULL, wifi_password VARCHAR(100) NOT NULL, check_in_time VARCHAR(10) NOT NULL, check_out_time VARCHAR(10) NOT NULL, pets_policy VARCHAR(255) NOT NULL, smoking_policy VARCHAR(255) NOT NULL, silence_policy VARCHAR(255) NOT NULL, visits_policy VARCHAR(255) NOT NULL, rating DOUBLE PRECISION NOT NULL, bedrooms INT NOT NULL, bathrooms INT NOT NULL, max_guests INT NOT NULL, map_url VARCHAR(500) NOT NULL, latitude VARCHAR(50) NOT NULL, longitude VARCHAR(50) NOT NULL, pix_key VARCHAR(255) NOT NULL, airbnb_ical_url VARCHAR(500) DEFAULT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE booking_extra ADD CONSTRAINT FK_DC43F0D03301C60 FOREIGN KEY (booking_id) REFERENCES booking (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE booking_extra ADD CONSTRAINT FK_DC43F0D02B959FC6 FOREIGN KEY (extra_id) REFERENCES extra (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking_extra DROP FOREIGN KEY FK_DC43F0D03301C60');
        $this->addSql('ALTER TABLE booking_extra DROP FOREIGN KEY FK_DC43F0D02B959FC6');
        $this->addSql('DROP TABLE booking');
        $this->addSql('DROP TABLE booking_extra');
        $this->addSql('DROP TABLE extra');
        $this->addSql('DROP TABLE property');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
