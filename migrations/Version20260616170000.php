<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Extras: guest-tier breakfast pricing, Rajaaram therapies, deactivate romantic kits';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE extra ADD min_guests INT NOT NULL DEFAULT 1, ADD max_guests INT DEFAULT NULL');

        $this->addSql("UPDATE extra SET active = 0 WHERE name_pt IN ('Kit Romântico', 'Kit Casa Comigo?')");

        $this->addSql(<<<'SQL'
UPDATE extra SET
  name_pt = 'Café da manhã gourmet (individual)',
  name_en = 'Gourmet breakfast (single)',
  description_pt = 'Servido às 8h no Kuya Deck. Pães artesanais, frutas, iogurtes, café ou chás. Para 1 hóspede. Reserve com 24h de antecedência.',
  description_en = 'Served at 8am at Kuya Deck. Artisan breads, fruit, yogurt, coffee or tea. For 1 guest. Book 24h in advance.',
  price = 180.00,
  min_guests = 1,
  max_guests = 1
WHERE name_pt = 'Café da manhã gourmet'
SQL);

        $this->addSql(<<<'SQL'
UPDATE extra SET
  name_pt = 'Café da manhã simples (individual)',
  name_en = 'Simple breakfast (single)',
  description_pt = 'Pão, suco, café ou chá por pessoa/dia. Para 1 hóspede. Reserve com 24h de antecedência.',
  description_en = 'Bread, juice, coffee or tea per person/day. For 1 guest. Book 24h in advance.',
  price = 50.00,
  min_guests = 1,
  max_guests = 1
WHERE name_pt = 'Café da manhã simples'
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO extra (name_pt, name_en, description_pt, description_en, price, currency, category, icon, active, min_guests, max_guests, created_at)
SELECT * FROM (SELECT
  'Café da manhã gourmet (casal)' AS name_pt,
  'Gourmet breakfast (couple)' AS name_en,
  'Servido às 8h no Kuya Deck. Pães artesanais, frutas, iogurtes, café ou chás. Para 2 ou mais hóspedes. Reserve com 24h de antecedência.' AS description_pt,
  'Served at 8am at Kuya Deck. Artisan breads, fruit, yogurt, coffee or tea. For 2 or more guests. Book 24h in advance.' AS description_en,
  250.00 AS price, 'BRL' AS currency, 'alimentação' AS category, 'coffee' AS icon, 1 AS active, 2 AS min_guests, NULL AS max_guests, NOW() AS created_at
) AS t WHERE NOT EXISTS (SELECT 1 FROM extra WHERE name_pt = 'Café da manhã gourmet (casal)')
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO extra (name_pt, name_en, description_pt, description_en, price, currency, category, icon, active, min_guests, max_guests, created_at)
SELECT * FROM (SELECT
  'Café da manhã simples (casal)' AS name_pt,
  'Simple breakfast (couple)' AS name_en,
  'Pão, suco, café ou chá por pessoa/dia. Para 2 ou mais hóspedes. Reserve com 24h de antecedência.' AS description_pt,
  'Bread, juice, coffee or tea per person/day. For 2 or more guests. Book 24h in advance.' AS description_en,
  90.00 AS price, 'BRL' AS currency, 'alimentação' AS category, 'coffee' AS icon, 1 AS active, 2 AS min_guests, NULL AS max_guests, NOW() AS created_at
) AS t WHERE NOT EXISTS (SELECT 1 FROM extra WHERE name_pt = 'Café da manhã simples (casal)')
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO extra (name_pt, name_en, description_pt, description_en, price, currency, category, icon, active, min_guests, max_guests, created_at)
SELECT * FROM (SELECT
  'Reset Express — Rajaaram' AS name_pt,
  'Reset Express — Rajaaram' AS name_en,
  'Terapia corporal profunda de 1h30 conduzida com presença, no coração do Domo.' AS description_pt,
  'Deep body therapy, 1h30, guided with presence at the heart of Domo.' AS description_en,
  585.00 AS price, 'BRL' AS currency, 'bem-estar' AS category, 'star' AS icon, 1 AS active, 1 AS min_guests, NULL AS max_guests, NOW() AS created_at
) AS t WHERE NOT EXISTS (SELECT 1 FROM extra WHERE name_pt = 'Reset Express — Rajaaram')
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO extra (name_pt, name_en, description_pt, description_en, price, currency, category, icon, active, min_guests, max_guests, created_at)
SELECT * FROM (SELECT
  'Cerimônia Reset — Rajaaram' AS name_pt,
  'Reset Ceremony — Rajaaram' AS name_en,
  'Cerimônia terapêutica de 3h para restauração profunda do corpo e da mente.' AS description_pt,
  '3-hour therapeutic ceremony for deep body and mind restoration.' AS description_en,
  830.00 AS price, 'BRL' AS currency, 'bem-estar' AS category, 'star' AS icon, 1 AS active, 1 AS min_guests, NULL AS max_guests, NOW() AS created_at
) AS t WHERE NOT EXISTS (SELECT 1 FROM extra WHERE name_pt = 'Cerimônia Reset — Rajaaram')
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO extra (name_pt, name_en, description_pt, description_en, price, currency, category, icon, active, min_guests, max_guests, created_at)
SELECT * FROM (SELECT
  'Mergulho Profundo — Rajaaram' AS name_pt,
  'Deep Dive — Rajaaram' AS name_en,
  'Experiência terapêutica intensiva de 3h para reconexão profunda.' AS description_pt,
  'Intensive 3-hour therapeutic experience for deep reconnection.' AS description_en,
  1130.00 AS price, 'BRL' AS currency, 'bem-estar' AS category, 'star' AS icon, 1 AS active, 1 AS min_guests, NULL AS max_guests, NOW() AS created_at
) AS t WHERE NOT EXISTS (SELECT 1 FROM extra WHERE name_pt = 'Mergulho Profundo — Rajaaram')
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM extra WHERE name_pt IN (
            'Café da manhã gourmet (casal)',
            'Café da manhã simples (casal)',
            'Reset Express — Rajaaram',
            'Cerimônia Reset — Rajaaram',
            'Mergulho Profundo — Rajaaram'
        )");
        $this->addSql("UPDATE extra SET active = 1 WHERE name_pt IN ('Kit Romântico', 'Kit Casa Comigo?')");
        $this->addSql(<<<'SQL'
UPDATE extra SET
  name_pt = 'Café da manhã gourmet',
  name_en = 'Gourmet breakfast',
  description_pt = 'Servido às 8h no Kuya Deck. Pães artesanais, frutas, iogurtes, café ou chás. Reserve com 24h de antecedência.',
  description_en = 'Served at 8am at Kuya Deck. Artisan breads, fruit, yogurt, coffee or tea. Book 24h in advance.',
  price = 180.00,
  min_guests = 1,
  max_guests = NULL
WHERE name_pt = 'Café da manhã gourmet (individual)'
SQL);
        $this->addSql(<<<'SQL'
UPDATE extra SET
  name_pt = 'Café da manhã simples',
  name_en = 'Simple breakfast',
  description_pt = 'Pão, suco, café ou chá por pessoa/dia. Reserve com 24h de antecedência.',
  description_en = 'Bread, juice, coffee or tea per person/day. Book 24h in advance.',
  price = 50.00,
  min_guests = 1,
  max_guests = NULL
WHERE name_pt = 'Café da manhã simples (individual)'
SQL);
        $this->addSql('ALTER TABLE extra DROP min_guests, DROP max_guests');
    }
}
