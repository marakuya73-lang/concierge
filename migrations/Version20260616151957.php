<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616151957 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Welcome book data: property contact/wifi/checkout fields and default extras';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE property ADD check_out_instructions_pt LONGTEXT DEFAULT NULL, ADD check_out_instructions_en LONGTEXT DEFAULT NULL, ADD wifi_secondary_name VARCHAR(100) DEFAULT NULL, ADD wifi_secondary_password VARCHAR(100) DEFAULT NULL, ADD contact_phone VARCHAR(30) DEFAULT NULL, ADD contact_email VARCHAR(255) DEFAULT NULL, ADD instagram_handle VARCHAR(100) DEFAULT NULL');

        $this->addSql(<<<'SQL'
UPDATE property SET
  tagline_pt = 'Natureza e sofisticação se entrelaçam para uma vivência sensorial e memorável. Permita-se desacelerar e habitar o silêncio deste refúgio sagrado.',
  description_pt = 'Aqui, natureza e sofisticação se entrelaçam para oferecer uma vivência sensorial e memorável. Permita-se desacelerar, contemplar o céu estrelado e habitar o silêncio deste refúgio sagrado. Cada detalhe foi pensado para que você simplesmente... esteja.',
  check_in_instructions_pt = 'Das 14h às 18h.\n\nPedimos gentilmente que nos avise por WhatsApp assim que estiver saindo de Alto Paraíso. Assim, poderemos preparar sua chegada com todo cuidado e atenção.\n\nAo chegar, buzine e aguarde. O anfitrião irá ao seu encontro para recebê-lo pessoalmente.\n\nNossos cães são mansos e costumam vir dar as boas-vindas com entusiasmo.',
  check_out_instructions_pt = 'Até as 11:00.\n\nAntes de partir, pedimos gentilmente que nos envie uma mensagem para que possamos encerrar sua estadia com cuidado e gratidão.\n\nPor gentileza, leve consigo seus pertences e resíduos — sua atenção contribui para mantermos este refúgio sempre acolhedor.\n\nCaso precise sair sem nos encontrar, por favor, nos envie uma mensagem avisando que deixou o domo.',
  location_details_pt = 'Moinho, Alto Paraíso de Goiás — 14 km do centro, em meio à natureza intocada da Chapada dos Veadeiros. A 10 minutos a pé das cachoeiras Anjos e Arcanjos.',
  arrival_instructions_pt = 'O Domo Xangô está no Moinho, cerca de 14 km do centro de Alto Paraíso, por estrada de terra bem conservada. Recomendamos Google Maps (não Waze) e baixar o mapa offline.\n\n1. Ao sair de Alto Paraíso, abra o GPS no Google Maps antes de iniciar a rota.\n2. Siga pela estrada de terra em direção ao Moinho.\n3. Na bifurcação, mantenha-se à esquerda, seguindo as placas indicativas.\n4. Passe pela placa "Bem-vindos ao Moinho" e atravesse uma ponte.\n5. Continue em direção ao Solarion e às Cachoeiras Anjos e Arcanjos: vire na primeira rua à esquerda, depois na primeira à direita.\n6. Ao final da rua, vire à esquerda na estrada de terra (rumo às cachoeiras).\n7. Após ~200 m, verá dois postes laranjas marcando a subida final à direita — engate a primeira marcha e suba devagar.\n\nChegando: à esquerda, entrada com placas "ATER TUMTI". Toque o sino no portal e aguarde o anfitrião.',
  tagline_en = 'Nature and sophistication intertwine for a sensory, memorable experience. Slow down and inhabit the silence of this sacred refuge.',
  description_en = 'Here, nature and sophistication intertwine for a sensory, memorable stay. Slow down, gaze at the starry sky, and inhabit the silence of this sacred refuge. Every detail was designed so you can simply... be.',
  check_in_instructions_en = 'From 2pm to 6pm.\n\nPlease message us on WhatsApp when leaving Alto Paraíso so we can prepare your arrival with care.\n\nWhen you arrive, honk and wait. Your host will come to greet you personally.\n\nOur dogs are gentle and may welcome you enthusiastically.',
  check_out_instructions_en = 'By 11:00am.\n\nBefore leaving, please send us a message so we can close your stay with care and gratitude.\n\nPlease take your belongings and waste with you.\n\nIf you must leave without meeting us, please message us to let us know you have departed safely.',
  location_details_en = 'Moinho, Alto Paraíso de Goiás — 14 km from town center, in untouched Chapada dos Veadeiros nature. 10-minute walk to Anjos and Arcanjos waterfalls.',
  arrival_instructions_en = 'Domo Xangô is in Moinho, about 14 km from Alto Paraíso center via a well-maintained dirt road. Use Google Maps (not Waze) and download offline maps.\n\n1. Open Google Maps before leaving Alto Paraíso.\n2. Follow the dirt road toward Moinho.\n3. At the fork, stay left following signs.\n4. Pass the "Bem-vindos ao Moinho" sign and cross a bridge.\n5. Head toward Solarion and Anjos/Arcanjos waterfalls: first left, then first right.\n6. At the end of the street, turn left on the dirt road toward the waterfalls.\n7. After ~200 m, two orange posts mark the final uphill turn on the right — use first gear and go slowly.\n\nArrival: on your left, entrance with "ATER TUMTI" signs. Ring the bell at the gate and wait for your host.',
  wifi_name = 'MARA DECK',
  wifi_password = 'UaiFai Cosmico',
  wifi_secondary_name = 'd.sucodealegria',
  wifi_secondary_password = 'estacionoudireito?',
  check_in_time = '14:00',
  check_out_time = '11:00',
  pets_policy = 'Nossos cães fazem parte da experiência — avise se preferir distância',
  smoking_policy = 'Proibido fumar (RPPN — multa IBAMA)',
  silence_policy = 'Descalço ou meias limpas dentro do domo',
  visits_policy = 'Sem velas, incensos ou fogo aberto (RPPN)',
  pix_key = 'domo.xango@gmail.com',
  contact_phone = '+55 (61) 99997-2991',
  contact_email = 'domo.xango@gmail.com',
  instagram_handle = '@DOMOXANGO'
SQL);

        $this->addSql('ALTER TABLE property MODIFY check_out_instructions_pt LONGTEXT NOT NULL, MODIFY check_out_instructions_en LONGTEXT NOT NULL, MODIFY wifi_secondary_name VARCHAR(100) NOT NULL, MODIFY wifi_secondary_password VARCHAR(100) NOT NULL, MODIFY contact_phone VARCHAR(30) NOT NULL, MODIFY contact_email VARCHAR(255) NOT NULL, MODIFY instagram_handle VARCHAR(100) NOT NULL');

        $this->addSql(<<<'SQL'
INSERT INTO extra (name_pt, name_en, description_pt, description_en, price, currency, category, icon, active, created_at)
SELECT * FROM (SELECT
  'Transfer ida e volta' AS name_pt,
  'Round-trip transfer' AS name_en,
  'Serviço entre Alto Paraíso e o Domo Xangô. Sujeito à disponibilidade e confirmação prévia.' AS description_pt,
  'Service between Alto Paraíso and Domo Xangô. Subject to availability and prior confirmation.' AS description_en,
  150.00 AS price, 'BRL' AS currency, 'transfer' AS category, 'car' AS icon, 1 AS active, NOW() AS created_at
) AS t WHERE NOT EXISTS (SELECT 1 FROM extra WHERE name_pt = 'Transfer ida e volta')
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO extra (name_pt, name_en, description_pt, description_en, price, currency, category, icon, active, created_at)
SELECT * FROM (SELECT
  'Café da manhã gourmet' AS name_pt,
  'Gourmet breakfast' AS name_en,
  'Servido às 8h no Kuya Deck. Pães artesanais, frutas, iogurtes, café ou chás. Reserve com 24h de antecedência.' AS description_pt,
  'Served at 8am at Kuya Deck. Artisan breads, fruit, yogurt, coffee or tea. Book 24h in advance.' AS description_en,
  180.00 AS price, 'BRL' AS currency, 'alimentação' AS category, 'coffee' AS icon, 1 AS active, NOW() AS created_at
) AS t WHERE NOT EXISTS (SELECT 1 FROM extra WHERE name_pt = 'Café da manhã gourmet')
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO extra (name_pt, name_en, description_pt, description_en, price, currency, category, icon, active, created_at)
SELECT * FROM (SELECT
  'Café da manhã simples' AS name_pt,
  'Simple breakfast' AS name_en,
  'Pão, suco, café ou chá por pessoa/dia. Reserve com 24h de antecedência.' AS description_pt,
  'Bread, juice, coffee or tea per person/day. Book 24h in advance.' AS description_en,
  50.00 AS price, 'BRL' AS currency, 'alimentação' AS category, 'coffee' AS icon, 1 AS active, NOW() AS created_at
) AS t WHERE NOT EXISTS (SELECT 1 FROM extra WHERE name_pt = 'Café da manhã simples')
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO extra (name_pt, name_en, description_pt, description_en, price, currency, category, icon, active, created_at)
SELECT * FROM (SELECT
  'Kit Romântico' AS name_pt,
  'Romantic Kit' AS name_en,
  'Decoração com pétalas e velas, letreiro "EU TE AMO", chocolates. A partir de R$ 280.' AS description_pt,
  'Petals and candles, "I LOVE YOU" sign, chocolates. From R$ 280.' AS description_en,
  280.00 AS price, 'BRL' AS currency, 'experiências' AS category, 'heart' AS icon, 1 AS active, NOW() AS created_at
) AS t WHERE NOT EXISTS (SELECT 1 FROM extra WHERE name_pt = 'Kit Romântico')
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO extra (name_pt, name_en, description_pt, description_en, price, currency, category, icon, active, created_at)
SELECT * FROM (SELECT
  'Kit Casa Comigo?' AS name_pt,
  'Proposal Kit' AS name_en,
  'Decoração, letreiro "CASA COMIGO", pizza artesanal e bebida especial. A partir de R$ 390.' AS description_pt,
  'Decor, "MARRY ME" sign, artisan pizza and special drink. From R$ 390.' AS description_en,
  390.00 AS price, 'BRL' AS currency, 'experiências' AS category, 'heart' AS icon, 1 AS active, NOW() AS created_at
) AS t WHERE NOT EXISTS (SELECT 1 FROM extra WHERE name_pt = 'Kit Casa Comigo?')
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO extra (name_pt, name_en, description_pt, description_en, price, currency, category, icon, active, created_at)
SELECT * FROM (SELECT
  'Personal Chef no Domo' AS name_pt,
  'Personal Chef at the Dome' AS name_en,
  'Chef exclusivo no conforto do seu domo, menu personalizado com ingredientes frescos.' AS description_pt,
  'Exclusive chef at your dome, personalized menu with fresh ingredients.' AS description_en,
  2200.00 AS price, 'BRL' AS currency, 'alimentação' AS category, 'star' AS icon, 1 AS active, NOW() AS created_at
) AS t WHERE NOT EXISTS (SELECT 1 FROM extra WHERE name_pt = 'Personal Chef no Domo')
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE property DROP check_out_instructions_pt, DROP check_out_instructions_en, DROP wifi_secondary_name, DROP wifi_secondary_password, DROP contact_phone, DROP contact_email, DROP instagram_handle');
    }
}
