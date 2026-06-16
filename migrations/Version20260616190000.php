<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add guide_spot table for editable welcome content with images';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE guide_spot (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(20) NOT NULL, title_pt VARCHAR(255) DEFAULT NULL, title_en VARCHAR(255) DEFAULT NULL, body_pt LONGTEXT NOT NULL, body_en LONGTEXT NOT NULL, image_filename VARCHAR(255) DEFAULT NULL, sort_order INT NOT NULL, active TINYINT(1) NOT NULL, property_id INT NOT NULL, INDEX IDX_GUIDE_SPOT_PROPERTY (property_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE guide_spot ADD CONSTRAINT FK_GUIDE_SPOT_PROPERTY FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE CASCADE');

        $this->addSql("INSERT INTO guide_spot (property_id, type, title_pt, title_en, body_pt, body_en, image_filename, sort_order, active) SELECT p.id, 'hero', 'Querido hóspede,', 'Dear guest,', 'Bem-vindo ao Domo Xangô! Aqui, natureza e sofisticação se entrelaçam para oferecer uma vivência sensorial e memorável. Permita-se desacelerar, contemplar o céu estrelado e habitar o silêncio deste refúgio sagrado.\n\nEstamos por perto, com carinho e atenção, sempre que precisar.', 'Welcome to Domo Xangô! Here, nature and sophistication intertwine for a sensory, memorable stay. Slow down, gaze at the starry sky, and inhabit the silence of this sacred refuge.\n\nWe are nearby, with care and attention, whenever you need us.', NULL, 0, 1 FROM property p ORDER BY p.id ASC LIMIT 1");
        $this->addSql("INSERT INTO guide_spot (property_id, type, title_pt, title_en, body_pt, body_en, image_filename, sort_order, active) SELECT p.id, 'region', 'Alto Paraíso — uma frequência', 'Alto Paraíso — a frequency', 'Um território de natureza exuberante, sabedoria ancestral e vibração elevada. Estar aqui é atravessar um véu sutil — e deixar que a natureza, o céu e o silêncio revelem o que as palavras não alcançam.', 'A land of lush nature, ancestral wisdom, and elevated vibration. Being here is crossing a subtle veil — letting nature, sky, and silence reveal what words cannot reach.', NULL, 1, 1 FROM property p ORDER BY p.id ASC LIMIT 1");
        $this->addSql("INSERT INTO guide_spot (property_id, type, title_pt, title_en, body_pt, body_en, image_filename, sort_order, active) SELECT p.id, 'spot', 'Paralelo 14 Sul', '14th Parallel South', 'A cidade está sobre a mesma linha geográfica que Machu Picchu — um eixo planetário de expansão de consciência.', 'The town sits on the same geographic line as Machu Picchu — a planetary axis of expanding consciousness.', NULL, 2, 1 FROM property p ORDER BY p.id ASC LIMIT 1");
        $this->addSql("INSERT INTO guide_spot (property_id, type, title_pt, title_en, body_pt, body_en, image_filename, sort_order, active) SELECT p.id, 'spot', 'Placa de Cristal de Quartzo', 'Quartz Crystal Plate', 'A Chapada repousa sobre uma gigantesca formação de quartzo. Para muitos, isso potencializa cura, intuição e presença.', 'The Chapada rests on a giant quartz formation. For many, this amplifies healing, intuition, and presence.', NULL, 3, 1 FROM property p ORDER BY p.id ASC LIMIT 1");
        $this->addSql("INSERT INTO guide_spot (property_id, type, title_pt, title_en, body_pt, body_en, image_filename, sort_order, active) SELECT p.id, 'spot', 'Avistamentos no céu', 'Sky sightings', 'Relatos de luzes e fenômenos inexplicáveis são frequentes — Alto Paraíso é carinhosamente o \"Vale dos E.T.s\".', 'Reports of lights and unexplained phenomena are common — Alto Paraíso is affectionately the \"E.T. Valley\".', NULL, 4, 1 FROM property p ORDER BY p.id ASC LIMIT 1");
        $this->addSql("INSERT INTO guide_spot (property_id, type, title_pt, title_en, body_pt, body_en, image_filename, sort_order, active) SELECT p.id, 'spot', 'Portal natural', 'Natural portal', 'Considerada um portal interdimensional, a região atrai buscadores de espiritualidade, artistas e viajantes do mundo inteiro.', 'Considered an interdimensional portal, the region draws spiritual seekers, artists, and travelers from around the world.', NULL, 5, 1 FROM property p ORDER BY p.id ASC LIMIT 1");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE guide_spot DROP FOREIGN KEY FK_GUIDE_SPOT_PROPERTY');
        $this->addSql('DROP TABLE guide_spot');
    }
}
