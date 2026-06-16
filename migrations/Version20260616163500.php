<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616163500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed Airbnb blocked periods from current iCal export';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
INSERT INTO blocked_period (external_uid, start_date, end_date, label) VALUES
('7f662ec65913-5d6cfa4b3363bcc40833b77fff31999d@airbnb.com', '2026-06-26', '2026-06-28', 'Airbnb (Not available)'),
('7f662ec65913-a62a523cc487878e60d46d74888f630e@airbnb.com', '2026-07-10', '2026-07-11', 'Airbnb (Not available)'),
('7f662ec65913-69693b0531183735b1874afd8c804c71@airbnb.com', '2026-08-02', '2026-08-03', 'Airbnb (Not available)'),
('7f662ec65913-6912fdaf4e7e40d60ceb6126de21fafd@airbnb.com', '2026-12-13', '2027-06-17', 'Airbnb (Not available)')
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM blocked_period');
    }
}
