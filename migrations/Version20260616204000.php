<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616204000 extends AbstractMigration
{
    private const WA = 'https://wa.me/+5561999972991';

    public function getDescription(): string
    {
        return 'Activity links, richer descriptions, replace tours with local guide recommendations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE activity_item ADD link_url VARCHAR(500) DEFAULT NULL, ADD link_url2 VARCHAR(500) DEFAULT NULL');
    }

    public function postUp(Schema $schema): void
    {
        $propertyId = $this->connection->fetchOne('SELECT id FROM property ORDER BY id ASC LIMIT 1');
        if (!$propertyId) {
            return;
        }

        $this->connection->executeStatement(
            'UPDATE property SET activities_intro_pt = ?, activities_intro_en = ? WHERE id = ?',
            [
                'A Chapada guarda trilhas, cachoeiras, céus estrelados e vivências que ficam na memória. Algumas começam a poucos passos do Domo.',
                'Chapada holds trails, waterfalls, starry skies, and experiences that stay with you. Some begin just steps from Domo.',
                $propertyId,
            ]
        );

        $items = [
            [
                'match' => 'Cachoeiras Anjos e Arcanjos',
                'icon' => '💧',
                'title_pt' => 'Cachoeiras Anjos e Arcanjos',
                'title_en' => 'Anjos & Arcanjos Waterfalls',
                'body_pt' => 'A poucos minutos a pé, águas turquesa caem entre paredões de quartzito — uma das cachoeiras mais encantadas da Chapada, quase no quintal do Domo.',
                'body_en' => 'A short walk away, turquoise water spills over quartzite cliffs — one of the Chapada\'s most enchanting falls, almost in Domo\'s backyard.',
                'link_url' => 'https://g.co/kgs/wEwiRTU',
                'link_url2' => null,
                'sort_order' => 0,
            ],
            [
                'match' => 'Massagens Rajaaram',
                'icon' => '🧘',
                'title_pt' => 'Massagens Rajaaram',
                'title_en' => 'Rajaaram Massages',
                'body_pt' => "Rajaaram conduz terapias de presença profunda aqui no Domo.\n\nReset Express (1h30) R$ 585 · Cerimônia Reset (3h) R$ 830 · Mergulho Profundo (3h) R$ 1.130.",
                'body_en' => "Rajaaram offers deep presence therapies here at Domo.\n\nReset Express (1h30) R$ 585 · Reset Ceremony (3h) R$ 830 · Deep Dive (3h) R$ 1,130.",
                'link_url' => 'http://www.rajaaram.com.br/',
                'link_url2' => null,
                'sort_order' => 1,
            ],
            [
                'match' => 'Cerimônias de Medicinas Sagradas',
                'icon' => '🌿',
                'title_pt' => 'Cerimônias de Medicinas Sagradas',
                'title_en' => 'Sacred Medicine Ceremonies',
                'body_pt' => 'Vivências ancestrais conduzidas com cuidado, preparo e intenção. Fale conosco para saber mais sobre datas, orientação e o que esperar.',
                'body_en' => 'Ancestral experiences conducted with care, preparation, and intention. Contact us to learn about dates, guidance, and what to expect.',
                'link_url' => self::WA.'?text=Estou%20me%20hospedando%20no%20Domo%20Xang%C3%B4%2C%20gostaria%20de%20saber%20mais%20sobre%20as%20cerimonias%20de%20medicinas%20sagradas',
                'link_url2' => null,
                'sort_order' => 2,
            ],
            [
                'match' => 'Observação de Fenômenos Celestes',
                'icon' => '✨',
                'title_pt' => 'Observação de Fenômenos Celestes',
                'title_en' => 'Celestial Phenomena Watching',
                'body_pt' => 'Alto Paraíso é o "Vale dos E.T.s" — em noites de lua nova, o céu se abre sem poluição luminosa. Contemple do deck ou visite pontos da região.',
                'body_en' => 'Alto Paraíso is the "E.T. Valley" — on new moon nights, the sky opens without light pollution. Contemplate from the deck or visit spots in the region.',
                'link_url' => 'https://www.google.com.br/maps/search/-14.059734,+-47.466942',
                'link_url2' => null,
                'sort_order' => 3,
            ],
            [
                'match' => 'Excursões com Thiago e Cintia',
                'icon' => '🗺️',
                'title_pt' => 'Guias locais',
                'title_en' => 'Local guides',
                'body_pt' => 'Temos recomendações de guias locais se precisarem — trilhas, cachoeiras e a Chapada com quem conhece cada canto.',
                'body_en' => 'We have recommendations for local guides if you need them — trails, waterfalls, and Chapada with those who know every corner.',
                'link_url' => self::WA.'?text=Estou%20me%20hospedando%20no%20Domo%20Xang%C3%B4%2C%20gostaria%20de%20recomendações%20de%20guias%20locais',
                'link_url2' => null,
                'sort_order' => 4,
            ],
            [
                'match' => 'Passeio a cavalo',
                'icon' => '🐴',
                'title_pt' => 'Passeio a cavalo',
                'title_en' => 'Horseback riding',
                'body_pt' => 'Cavalgadas por campos abertos, trilhas suaves e o silêncio da Chapada no ritmo do cavalo. Pergunte conosco sobre opções e horários.',
                'body_en' => 'Rides through open fields, gentle trails, and Chapada silence at the horse\'s pace. Ask us about options and times.',
                'link_url' => self::WA.'?text=Estou%20me%20hospedando%20no%20Domo%20Xang%C3%B4%2C%20gostaria%20de%20saber%20sobre%20passeios%20a%20cavalo',
                'link_url2' => null,
                'sort_order' => 5,
            ],
            [
                'match' => 'Feiras locais',
                'icon' => '🛒',
                'title_pt' => 'Feiras locais',
                'title_en' => 'Local markets',
                'body_pt' => 'Produtos frescos direto do produtor — feira aos sábados, terças e quintas; aos domingos, a Feira Popular da Agricultura Familiar enche a cidade de cores e sabores.',
                'body_en' => 'Fresh produce straight from growers — market on Sat, Tue, Thu; on Sundays, the Popular Family Agriculture Fair fills the town with color and flavor.',
                'link_url' => 'https://g.co/kgs/tsmnBMD',
                'link_url2' => 'https://g.co/kgs/CqXxt2r',
                'sort_order' => 6,
            ],
        ];

        foreach ($items as $item) {
            $updated = $this->connection->executeStatement(
                'UPDATE activity_item SET icon = ?, title_pt = ?, title_en = ?, body_pt = ?, body_en = ?, link_url = ?, link_url2 = ?, sort_order = ? WHERE property_id = ? AND title_pt = ?',
                [
                    $item['icon'],
                    $item['title_pt'],
                    $item['title_en'],
                    $item['body_pt'],
                    $item['body_en'],
                    $item['link_url'],
                    $item['link_url2'],
                    $item['sort_order'],
                    $propertyId,
                    $item['match'],
                ]
            );

            if (0 === $updated && 'Excursões com Thiago e Cintia' === $item['match']) {
                $this->connection->executeStatement(
                    'UPDATE activity_item SET icon = ?, title_pt = ?, title_en = ?, body_pt = ?, body_en = ?, link_url = ?, link_url2 = ?, sort_order = ? WHERE property_id = ? AND sort_order = 4',
                    [
                        $item['icon'],
                        $item['title_pt'],
                        $item['title_en'],
                        $item['body_pt'],
                        $item['body_en'],
                        $item['link_url'],
                        $item['link_url2'],
                        $item['sort_order'],
                        $propertyId,
                    ]
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE activity_item DROP link_url, DROP link_url2');
    }
}
