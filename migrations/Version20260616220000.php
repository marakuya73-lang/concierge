<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Expand house rules with complete welcome book text';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
UPDATE property SET
  rules_intro_pt = 'Nosso desejo é que sua estadia aqui seja memorável. Para isso, pedimos atenção a algumas orientações essenciais que preservam o conforto, a segurança e a harmonia deste espaço sagrado:',
  rules_intro_en = 'We want your stay here to be memorable. To help with that, we ask you to follow a few essential guidelines that preserve the comfort, safety, and harmony of this sacred space:'
SQL);

        $rules = [
            [
                'sort_order' => 0,
                'title_pt' => 'Calçados? Só do lado de fora.',
                'title_en' => 'Shoes? Outside only.',
                'body_pt' => "Dentro do domo, pedimos que permaneça descalço ou de meias limpas. Essa prática ajuda a manter o espaço aconchegante, puro e acolhedor para todos que aqui chegam.",
                'body_en' => "Inside the dome, please remain barefoot or in clean socks. This practice helps keep the space cozy, pure, and welcoming for everyone who arrives.",
            ],
            [
                'sort_order' => 1,
                'title_pt' => 'Fogo? Somente o da alma.',
                'title_en' => 'Fire? Only the soul\'s.',
                'body_pt' => "Estamos em uma RPPN (Reserva Particular do Patrimônio Natural), e qualquer chama aberta representa risco de incêndio. Por isso, não utilizamos velas, incensos em brasa ou quaisquer experimentos com fogo.\n\n— PROIBIDO FUMAR —\n(Sob risco de multa do IBAMA)",
                'body_en' => "We are in an RPPN (Private Natural Heritage Reserve), and any open flame poses a fire risk. For this reason, we do not use candles, burning incense, or any experiments with fire.\n\n— NO SMOKING —\n(Subject to IBAMA fine)",
            ],
            [
                'sort_order' => 2,
                'title_pt' => 'Na beira do deck, com presença.',
                'title_en' => 'On the deck edge, stay present.',
                'body_pt' => 'A vista é realmente de tirar o fôlego, mas por segurança, pedimos atenção e presença ao caminhar próximo à borda. Queremos que você contemple — com os pés no chão.',
                'body_en' => 'The view is truly breathtaking, but for your safety we ask for attention and presence when walking near the edge. We want you to take it in — with your feet on the ground.',
            ],
            [
                'sort_order' => 3,
                'title_pt' => 'Nossos cães são parte da experiência.',
                'title_en' => 'Our dogs are part of the experience.',
                'body_pt' => 'Eles adoram carinho e estarão por perto para dar boas-vindas. Se preferir manter distância, basta nos avisar — faremos o possível para que você se sinta totalmente à vontade.',
                'body_en' => 'They love affection and will be nearby to welcome you. If you prefer to keep your distance, just let us know — we will do our best to ensure you feel completely at ease.',
            ],
            [
                'sort_order' => 4,
                'title_pt' => 'Frigobar & Itens à parte',
                'title_en' => 'Minibar & à la carte items',
                'body_pt' => 'Os produtos disponíveis estão listados no menu. Sinta-se à vontade para consumir e, caso tenha dúvidas sobre valores ou formas de pagamento, fale conosco — estamos por aqui para ajudar.',
                'body_en' => 'Available products are listed in the menu. Feel free to enjoy them, and if you have questions about prices or payment methods, talk to us — we are here to help.',
            ],
        ];

        foreach ($rules as $rule) {
            $this->addSql(
                'UPDATE house_rule SET title_pt = ?, title_en = ?, body_pt = ?, body_en = ? WHERE sort_order = ?',
                [
                    $rule['title_pt'],
                    $rule['title_en'],
                    $rule['body_pt'],
                    $rule['body_en'],
                    $rule['sort_order'],
                ],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
UPDATE property SET
  rules_intro_pt = 'Nosso desejo é que sua estadia seja memorável. Para preservar conforto, segurança e harmonia:',
  rules_intro_en = 'We want your stay to be memorable. To preserve comfort, safety, and harmony:'
SQL);
    }
}
