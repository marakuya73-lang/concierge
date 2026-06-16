<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616205000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enrich regional curiosities with fuller text and two new guide spots';
    }

    public function up(Schema $schema): void
    {
        // Schema unchanged; content only.
    }

    public function postUp(Schema $schema): void
    {
        $propertyId = $this->connection->fetchOne('SELECT id FROM property ORDER BY id ASC LIMIT 1');
        if (!$propertyId) {
            return;
        }

        $this->connection->executeStatement(
            'UPDATE guide_spot SET title_pt = ?, title_en = ?, body_pt = ?, body_en = ? WHERE property_id = ? AND type = ?',
            [
                'Alto Paraíso, uma frequência',
                'Alto Paraíso, a frequency',
                'Um território onde o cerrado encontra o quartzito, a sabedoria ancestral conversa com a cosmologia moderna, e cada nascer do sol parece inaugurar algo novo. Estar aqui é desacelerar o suficiente para perceber que a Chapada pulsa em outra frequência.',
                'A land where the cerrado meets quartzite, ancestral wisdom meets modern cosmology, and every sunrise seems to inaugurate something new. Being here means slowing down enough to feel that Chapada beats at a different frequency.',
                $propertyId,
                'region',
            ]
        );

        $spots = [
            [
                'match' => 'Paralelo 14 Sul',
                'title_pt' => 'Paralelo 14 Sul',
                'title_en' => '14th Parallel South',
                'body_pt' => 'Alto Paraíso repousa sobre o Paralelo 14 Sul, a mesma linha geográfica que atravessa Machu Picchu, no Peru. Esoteristas e geógrafos a chamam de "linha de consciência": dois sítios sagrados ancestrais, separados por um oceano, alinhados no mesmo fio do planeta.',
                'body_en' => 'Alto Paraíso sits on the 14th Parallel South, the same geographic line that crosses Machu Picchu in Peru. Esoteric traditions and geographers call it a "line of consciousness": two ancient sacred sites, separated by an ocean, aligned on the same thread of the planet.',
                'sort_order' => 2,
            ],
            [
                'match' => 'Placa de Cristal de Quartzo',
                'title_pt' => 'Placa de Cristal de Quartzo',
                'title_en' => 'Quartz Crystal Plate',
                'body_pt' => 'Por baixo dos pés, a Chapada dos Veadeiros está assentada sobre uma das maiores formações de quartzo cristalino do mundo. A rocha amplifica vibrações, por isso tantos visitantes relatam sensações de cura, intuição aguçada e profunda presença neste território.',
                'body_en' => 'Beneath your feet, Chapada dos Veadeiros rests on one of the largest quartz crystal formations on Earth. The rock amplifies vibrations, which is why so many visitors report feelings of healing, sharpened intuition, and deep presence in this land.',
                'sort_order' => 3,
            ],
            [
                'match' => 'Avistamentos no céu',
                'title_pt' => 'Avistamentos no céu',
                'title_en' => 'Sky sightings',
                'body_pt' => 'Luzes que mudam de cor, objetos no céu em movimentos impossíveis, relatos que se repetem há décadas. Alto Paraíso ganhou o apelido carinhoso de "Vale dos E.T.s". Em noites de lua nova, o céu escuro da Chapada intensifica o espetáculo.',
                'body_en' => 'Lights that shift color, objects moving across the sky in impossible ways, stories repeated for decades. Alto Paraíso earned the affectionate nickname "E.T. Valley". On new moon nights, Chapada\'s dark sky makes the spectacle even more vivid.',
                'sort_order' => 4,
            ],
            [
                'match' => 'Portal natural',
                'title_pt' => 'Portal natural',
                'title_en' => 'Natural portal',
                'body_pt' => 'Há quem diga que Alto Paraíso é um portal, um lugar onde o véu entre o visível e o invisível se torna tênue. A combinação de natureza intocada, silêncio profundo e vibração elevada atrai buscadores, artistas e viajantes de todos os continentes, muitos dizendo que "algo mudou" depois da primeira visita.',
                'body_en' => 'Some say Alto Paraíso is a portal, a place where the veil between the visible and invisible grows thin. Untouched nature, deep silence, and elevated vibration draw seekers, artists, and travelers from every continent, many saying "something shifted" after their first visit.',
                'sort_order' => 5,
            ],
            [
                'match' => 'Parque Nacional Chapada dos Veadeiros',
                'title_pt' => 'Parque Nacional Chapada dos Veadeiros',
                'title_en' => 'Chapada dos Veadeiros National Park',
                'body_pt' => 'Patrimônio Mundial da UNESCO desde 2001, o parque guarda um dos cerrados mais antigos do planeta: cachoeiras, cânions de quartzito, campos de altitude e uma biodiversidade que surpreende até cientistas. É o pulmão verde ao qual o Domo se abre todos os dias.',
                'body_en' => 'A UNESCO World Heritage Site since 2001, the park holds one of the planet\'s oldest cerrado ecosystems: waterfalls, quartzite canyons, highland fields, and biodiversity that still surprises scientists. It is the green lung the Dome opens onto every day.',
                'sort_order' => 6,
                'insert' => true,
            ],
            [
                'match' => 'Berço das águas',
                'title_pt' => 'Berço das águas',
                'title_en' => 'Cradle of waters',
                'body_pt' => 'O cerrado da Chapada é conhecido como o "berço das águas" do Brasil: nascentes puras alimentam rios que atravessam o país. As águas que você encontra aqui vêm de lençóis subterrâneos protegidos por camadas de quartzo e vegetação nativa.',
                'body_en' => 'Chapada\'s cerrado is known as Brazil\'s "cradle of waters": pure springs feed rivers that cross the country. The water you find here comes from underground aquifers protected by layers of quartz and native vegetation.',
                'sort_order' => 7,
                'insert' => true,
            ],
        ];

        foreach ($spots as $spot) {
            if (!empty($spot['insert'])) {
                $exists = $this->connection->fetchOne(
                    'SELECT id FROM guide_spot WHERE property_id = ? AND title_pt = ?',
                    [$propertyId, $spot['title_pt']]
                );
                if ($exists) {
                    continue;
                }

                $this->connection->insert('guide_spot', [
                    'property_id' => $propertyId,
                    'type' => 'spot',
                    'title_pt' => $spot['title_pt'],
                    'title_en' => $spot['title_en'],
                    'body_pt' => $spot['body_pt'],
                    'body_en' => $spot['body_en'],
                    'image_filename' => null,
                    'sort_order' => $spot['sort_order'],
                    'active' => 1,
                ]);

                continue;
            }

            $this->connection->executeStatement(
                'UPDATE guide_spot SET title_pt = ?, title_en = ?, body_pt = ?, body_en = ?, sort_order = ? WHERE property_id = ? AND title_pt = ?',
                [
                    $spot['title_pt'],
                    $spot['title_en'],
                    $spot['body_pt'],
                    $spot['body_en'],
                    $spot['sort_order'],
                    $propertyId,
                    $spot['match'],
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Content migration; not reversible.
    }
}
