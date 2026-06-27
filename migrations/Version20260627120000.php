<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260627120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Clarify no-smoking and bedding house rules with charge amounts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
UPDATE property SET
  smoking_policy = 'Proibido fumar — odor de fumo: R$ 1.000'
SQL);

        $this->addSql(
            'UPDATE house_rule SET title_pt = ?, title_en = ?, body_pt = ?, body_en = ? WHERE sort_order = ?',
            [
                'Fogo? Somente o da alma.',
                'Fire? Only the soul\'s.',
                "Estamos em uma RPPN (Reserva Particular do Patrimônio Natural), e qualquer chama aberta representa risco de incêndio. Por isso, não utilizamos velas, incensos em brasa ou quaisquer experimentos com fogo.\n\n— PROIBIDO FUMAR —\nÉ estritamente proibido fumar dentro do domo, no deck ou em qualquer área do espaço. Odor de fumo detectado na check-out será cobrado R$ 1.000 (sujeito ainda a multa do IBAMA).",
                "We are in an RPPN (Private Natural Heritage Reserve), and any open flame poses a fire risk. For this reason, we do not use candles, burning incense, or any experiments with fire.\n\n— NO SMOKING —\nSmoking is strictly forbidden inside the dome, on the deck, or anywhere on the property. Any smoke odor detected at check-out will incur a R$ 1,000 charge (in addition to possible IBAMA fines).",
                1,
            ],
        );
    }

    public function postUp(Schema $schema): void
    {
        $propertyId = $this->connection->fetchOne('SELECT id FROM property ORDER BY id ASC LIMIT 1');
        if (!$propertyId) {
            return;
        }

        $exists = $this->connection->fetchOne(
            'SELECT id FROM house_rule WHERE property_id = ? AND sort_order = ?',
            [$propertyId, 5],
        );
        if ($exists) {
            $this->connection->update(
                'house_rule',
                [
                    'title_pt' => 'Cobertores e travesseiros ficam dentro.',
                    'title_en' => 'Blankets and pillows stay inside.',
                    'body_pt' => 'É proibido levar cobertores, travesseiros ou qualquer enxoval do interior do domo para fora (deck, área externa, etc.). Qualquer dano constatado no material será cobrado R$ 250.',
                    'body_en' => 'It is forbidden to take blankets, pillows, or any bedding from inside the dome outside (deck, outdoor areas, etc.). Any damage to the materials will incur a R$ 250 charge.',
                ],
                ['property_id' => $propertyId, 'sort_order' => 5],
            );

            return;
        }

        $this->connection->insert('house_rule', [
            'property_id' => $propertyId,
            'title_pt' => 'Cobertores e travesseiros ficam dentro.',
            'title_en' => 'Blankets and pillows stay inside.',
            'body_pt' => 'É proibido levar cobertores, travesseiros ou qualquer enxoval do interior do domo para fora (deck, área externa, etc.). Qualquer dano constatado no material será cobrado R$ 250.',
            'body_en' => 'It is forbidden to take blankets, pillows, or any bedding from inside the dome outside (deck, outdoor areas, etc.). Any damage to the materials will incur a R$ 250 charge.',
            'sort_order' => 5,
            'active' => 1,
        ]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
UPDATE property SET
  smoking_policy = 'Proibido fumar (RPPN — multa IBAMA)'
SQL);

        $this->addSql(
            'UPDATE house_rule SET title_pt = ?, title_en = ?, body_pt = ?, body_en = ? WHERE sort_order = ?',
            [
                'Fogo? Somente o da alma.',
                'Fire? Only the soul\'s.',
                "Estamos em uma RPPN (Reserva Particular do Patrimônio Natural), e qualquer chama aberta representa risco de incêndio. Por isso, não utilizamos velas, incensos em brasa ou quaisquer experimentos com fogo.\n\n— PROIBIDO FUMAR —\n(Sob risco de multa do IBAMA)",
                "We are in an RPPN (Private Natural Heritage Reserve), and any open flame poses a fire risk. For this reason, we do not use candles, burning incense, or any experiments with fire.\n\n— NO SMOKING —\n(Subject to IBAMA fine)",
                1,
            ],
        );

        $propertyId = $this->connection->fetchOne('SELECT id FROM property ORDER BY id ASC LIMIT 1');
        if ($propertyId) {
            $this->connection->delete('house_rule', [
                'property_id' => $propertyId,
                'sort_order' => 5,
                'title_pt' => 'Cobertores e travesseiros ficam dentro.',
            ]);
        }
    }
}
