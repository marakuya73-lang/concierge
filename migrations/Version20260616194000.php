<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616194000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add activity_item table and activities intro fields on property';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE property ADD activities_intro_pt LONGTEXT DEFAULT NULL, ADD activities_intro_en LONGTEXT DEFAULT NULL');
        $this->addSql("UPDATE property SET activities_intro_pt = 'Descubra trilhas, vivências e encontros especiais nos arredores do Domo.', activities_intro_en = 'Discover trails, experiences, and special encounters around Domo.' WHERE activities_intro_pt IS NULL");
        $this->addSql('ALTER TABLE property MODIFY activities_intro_pt LONGTEXT NOT NULL, MODIFY activities_intro_en LONGTEXT NOT NULL');
        $this->addSql('CREATE TABLE activity_item (id INT AUTO_INCREMENT NOT NULL, icon VARCHAR(16) NOT NULL, title_pt VARCHAR(255) NOT NULL, title_en VARCHAR(255) NOT NULL, body_pt LONGTEXT NOT NULL, body_en LONGTEXT NOT NULL, sort_order INT NOT NULL, active TINYINT(1) NOT NULL, property_id INT NOT NULL, INDEX IDX_ACTIVITY_ITEM_PROPERTY (property_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE activity_item ADD CONSTRAINT FK_ACTIVITY_ITEM_PROPERTY FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE CASCADE');
    }

    public function postUp(Schema $schema): void
    {
        $propertyId = $this->connection->fetchOne('SELECT id FROM property ORDER BY id ASC LIMIT 1');
        if (!$propertyId) {
            return;
        }

        $items = [
            [
                'icon' => '💧',
                'title_pt' => 'Cachoeiras Anjos e Arcanjos',
                'title_en' => 'Anjos & Arcanjos Waterfalls',
                'body_pt' => '10 minutos a pé — uma das quedas mais mágicas da Chapada.',
                'body_en' => '10-minute walk — one of the Chapada\'s most magical falls.',
                'sort_order' => 0,
            ],
            [
                'icon' => '🧘',
                'title_pt' => 'Massagens Rajaaram',
                'title_en' => 'Rajaaram Massages',
                'body_pt' => "Reset Express (1h30) R$ 585 · Cerimônia Reset (3h) R$ 830 · Mergulho Profundo (3h) R$ 1.130.\n\nTerapias profundas conduzidas com presença, aqui no coração do Domo.",
                'body_en' => "Reset Express (1h30) R$ 585 · Reset Ceremony (3h) R$ 830 · Deep Dive (3h) R$ 1,130.\n\nDeep therapies with presence, here at the heart of Domo.",
                'sort_order' => 1,
            ],
            [
                'icon' => '🌿',
                'title_pt' => 'Cerimônias de Medicinas Sagradas',
                'title_en' => 'Sacred Medicine Ceremonies',
                'body_pt' => 'Vivências conduzidas com respeito, preparo e intenção.',
                'body_en' => 'Experiences conducted with respect, preparation, and intention.',
                'sort_order' => 2,
            ],
            [
                'icon' => '✨',
                'title_pt' => 'Observação de Fenômenos Celestes',
                'title_en' => 'Celestial Phenomena Watching',
                'body_pt' => 'Em noites de lua nova, prepare-se para contemplar o cosmos.',
                'body_en' => 'On new moon nights, prepare to contemplate the cosmos.',
                'sort_order' => 3,
            ],
            [
                'icon' => '🚙',
                'title_pt' => 'Excursões com Thiago e Cintia',
                'title_en' => 'Tours with Thiago & Cintia',
                'body_pt' => 'Guias especializados, transporte 4x4 e registros com fotografia e drone.',
                'body_en' => 'Specialized guides, 4x4 transport, photography and drone records.',
                'sort_order' => 4,
            ],
            [
                'icon' => '🐴',
                'title_pt' => 'Passeio a cavalo',
                'title_en' => 'Horseback riding',
                'body_pt' => 'Trilhas guiadas por campos abertos e silêncio.',
                'body_en' => 'Guided trails through open fields and silence.',
                'sort_order' => 5,
            ],
            [
                'icon' => '🛒',
                'title_pt' => 'Feiras locais',
                'title_en' => 'Local markets',
                'body_pt' => 'Feira do Produtor (sáb, ter, qui) e Feira Popular da Agricultura (domingo).',
                'body_en' => 'Producer Fair (Sat, Tue, Thu) and Popular Agriculture Fair (Sunday).',
                'sort_order' => 6,
            ],
        ];

        foreach ($items as $item) {
            $this->connection->insert('activity_item', [
                'property_id' => $propertyId,
                'icon' => $item['icon'],
                'title_pt' => $item['title_pt'],
                'title_en' => $item['title_en'],
                'body_pt' => $item['body_pt'],
                'body_en' => $item['body_en'],
                'sort_order' => $item['sort_order'],
                'active' => 1,
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE activity_item DROP FOREIGN KEY FK_ACTIVITY_ITEM_PROPERTY');
        $this->addSql('DROP TABLE activity_item');
        $this->addSql('ALTER TABLE property DROP activities_intro_pt, DROP activities_intro_en');
    }
}
