<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616163000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update property name and hero tagline to match landing design';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
UPDATE property SET
  name_pt = 'Domo Xangô | Vista Sagrada da Selva',
  name_en = 'Domo Xangô | Sacred Jungle View',
  tagline_pt = 'Bem-vindo à sua retiro na selva. Que sua estadia seja de descanso, reconexão e memórias inesquecíveis.',
  tagline_en = 'Welcome to your jungle retreat. May your stay bring rest, reconnection, and unforgettable memories.'
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
UPDATE property SET
  name_pt = 'Domo Xangô | Sacred Jungle Dome Views',
  name_en = 'Domo Xangô | Sacred Jungle Dome Views',
  tagline_pt = 'Natureza e sofisticação se entrelaçam para uma vivência sensorial e memorável. Permita-se desacelerar e habitar o silêncio deste refúgio sagrado.',
  tagline_en = 'Nature and sophistication intertwine for a sensory, memorable experience. Slow down and inhabit the silence of this sacred refuge.'
SQL);
    }
}
