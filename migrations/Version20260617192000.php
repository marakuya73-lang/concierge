<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617192000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set property map pin to Domo Xangô coordinates';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE property SET latitude = '-14.058523129412333', longitude = '-47.466154023211494'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE property SET latitude = '-14.058554351871225', longitude = '-47.46630422690646'");
    }
}
