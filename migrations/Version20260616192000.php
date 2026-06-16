<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616192000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add faq_item table with welcome book FAQ content';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE faq_item (id INT AUTO_INCREMENT NOT NULL, question_pt VARCHAR(255) NOT NULL, question_en VARCHAR(255) NOT NULL, answer_pt LONGTEXT NOT NULL, answer_en LONGTEXT NOT NULL, sort_order INT NOT NULL, active TINYINT(1) NOT NULL, property_id INT NOT NULL, INDEX IDX_FAQ_ITEM_PROPERTY (property_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE faq_item ADD CONSTRAINT FK_FAQ_ITEM_PROPERTY FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE CASCADE');
    }

    public function postUp(Schema $schema): void
    {
        $propertyId = $this->connection->fetchOne('SELECT id FROM property ORDER BY id ASC LIMIT 1');
        if (!$propertyId) {
            return;
        }

        $items = [
            [
                'question_pt' => 'Qual a voltagem das tomadas?',
                'question_en' => 'What is the outlet voltage?',
                'answer_pt' => 'Todas as tomadas são 220V. Sugerimos verificar a compatibilidade dos seus aparelhos antes de conectar, para garantir segurança e tranquilidade durante sua estadia.',
                'answer_en' => 'All outlets are 220V. We suggest checking your devices for compatibility before plugging in, for a safe and peaceful stay.',
                'sort_order' => 0,
            ],
            [
                'question_pt' => 'Como ligo as luzes e tomadas do domo?',
                'question_en' => 'How do I turn on the lights and outlets?',
                'answer_pt' => 'O interruptor principal está localizado acima da pia. As tomadas funcionam apenas quando as luzes estão ligadas — esse sistema ajuda a preservar a energia e a simplicidade do ambiente.',
                'answer_en' => 'The main switch is located above the sink. Outlets work only when the lights are on — this helps preserve energy and the simplicity of the space.',
                'sort_order' => 1,
            ],
            [
                'question_pt' => 'Os cães são mansos?',
                'question_en' => 'Are the dogs friendly?',
                'answer_pt' => 'Sim, nossos cães são dóceis, afetuosos e fazem parte da alma do lugar. Eles podem se aproximar buscando carinho — se preferir manter distância ou caso estejam te incomodando, é só nos avisar que cuidamos disso com carinho.',
                'answer_en' => 'Yes, our dogs are gentle, affectionate, and part of the soul of this place. They may come close for affection — if you prefer distance or feel bothered, just let us know and we will take care of it kindly.',
                'sort_order' => 2,
            ],
            [
                'question_pt' => 'Tem ar-condicionado?',
                'question_en' => 'Is there air conditioning?',
                'answer_pt' => 'Não utilizamos ar-condicionado, pois buscamos promover uma experiência mais integrada à natureza. O domo é bem ventilado, e o deck oferece sombras agradáveis para relaxar com conforto e frescor, mesmo nos dias mais quentes.',
                'answer_en' => 'We do not use air conditioning, as we seek an experience more integrated with nature. The dome is well ventilated, and the deck offers pleasant shade to relax comfortably, even on hotter days.',
                'sort_order' => 3,
            ],
            [
                'question_pt' => 'Tenho acesso ao deck?',
                'question_en' => 'Do I have access to the deck?',
                'answer_pt' => 'Sim, o deck é de uso exclusivo dos nossos hóspedes. É um espaço criado para contemplação — ideal para assistir ao nascer do sol ou da lua e se deixar envolver pela beleza do vale.',
                'answer_en' => 'Yes, the deck is for exclusive guest use. It is a space for contemplation — ideal for watching the sunrise or moonrise and being enveloped by the beauty of the valley.',
                'sort_order' => 4,
            ],
            [
                'question_pt' => 'Meu telefone vai funcionar?',
                'question_en' => 'Will my phone work?',
                'answer_pt' => 'Provavelmente não. Estamos em uma área de silêncio profundo, sem cobertura de celular — o que faz parte da magia deste lugar. Mas não se preocupe: o Wi-Fi por fibra ótica está disponível e funciona muito bem na maior parte do tempo.',
                'answer_en' => 'Probably not. We are in an area of deep silence, without mobile coverage — part of the magic of this place. But do not worry: fiber Wi-Fi is available and works very well most of the time.',
                'sort_order' => 5,
            ],
            [
                'question_pt' => 'O Wi-Fi é confiável?',
                'question_en' => 'Is Wi-Fi reliable?',
                'answer_pt' => 'Sim! Contamos com conexão via fibra ótica de alta qualidade. Em raras ocasiões de queda de energia, o sinal pode ser interrompido — nesses momentos, convidamos você a respirar fundo, observar o céu e mergulhar na paz do Domo Xangô.',
                'answer_en' => 'Yes! We have high-quality fiber internet. During rare power outages, the signal may drop — in those moments, we invite you to breathe deeply, gaze at the sky, and immerse yourself in the peace of Domo Xangô.',
                'sort_order' => 6,
            ],
            [
                'question_pt' => 'Meu carro é baixo. Consigo chegar?',
                'question_en' => 'My car is low. Can I get there?',
                'answer_pt' => 'Estamos a 14 km do centro da cidade, por estrada de terra. Recomendamos veículos com boa altura, especialmente em épocas de chuva. Mas fique tranquilo: muitos hóspedes chegam com carros comuns, sem qualquer dificuldade. Se você não tem familiaridade com esse tipo de trajeto, basta dirigir com calma e, se precisar, estamos disponíveis no WhatsApp para orientar em tempo real.',
                'answer_en' => 'We are 14 km from town center on a dirt road. We recommend vehicles with good clearance, especially in rainy season. Many guests arrive in regular cars without difficulty. If you are unfamiliar with this type of route, drive calmly and message us on WhatsApp for real-time guidance.',
                'sort_order' => 7,
            ],
            [
                'question_pt' => 'Sobre a água',
                'question_en' => 'About the water',
                'answer_pt' => 'Utilizamos água pura de uma caixa d\'água abastecida regularmente. Se em algum momento você sentir que o fornecimento está baixo, nos avise e reabasteceremos com prazer.',
                'answer_en' => 'We use pure water from a regularly refilled tank. If at any point you feel the supply is low, let us know and we will refill it gladly.',
                'sort_order' => 8,
            ],
        ];

        foreach ($items as $item) {
            $this->connection->insert('faq_item', [
                'property_id' => $propertyId,
                'question_pt' => $item['question_pt'],
                'question_en' => $item['question_en'],
                'answer_pt' => $item['answer_pt'],
                'answer_en' => $item['answer_en'],
                'sort_order' => $item['sort_order'],
                'active' => 1,
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE faq_item DROP FOREIGN KEY FK_FAQ_ITEM_PROPERTY');
        $this->addSql('DROP TABLE faq_item');
    }
}
