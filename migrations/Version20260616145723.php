<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260616145723 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE property_photo (id INT AUTO_INCREMENT NOT NULL, filename VARCHAR(255) NOT NULL, caption_pt VARCHAR(255) DEFAULT NULL, caption_en VARCHAR(255) DEFAULT NULL, sort_order INT NOT NULL, created_at DATETIME NOT NULL, property_id INT NOT NULL, INDEX IDX_D2A44515549213EC (property_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE property_photo ADD CONSTRAINT FK_D2A44515549213EC FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE property_photo DROP FOREIGN KEY FK_D2A44515549213EC');
        $this->addSql('DROP TABLE property_photo');
    }
}
