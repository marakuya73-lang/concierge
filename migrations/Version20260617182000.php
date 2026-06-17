<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617182000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Correct property map coordinates to Domo Xangô location';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE property SET latitude = '-14.058554351871225', longitude = '-47.46630422690646'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE property SET latitude = '-14.058769', longitude = '-47.466251'");
    }
}
