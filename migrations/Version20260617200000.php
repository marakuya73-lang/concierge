<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Correct Wi-Fi network names and passwords';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE property SET wifi_password = 'd.sucodealegria', wifi_secondary_name = 'UaiFai Cosmico'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE property SET wifi_password = 'UaiFai Cosmico', wifi_secondary_name = 'd.sucodealegria'");
    }
}
