<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616203000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Kitchen content: intro on property, photos and utensils tables with seed data';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE property ADD kitchen_intro_pt LONGTEXT NOT NULL DEFAULT (''), ADD kitchen_intro_en LONGTEXT NOT NULL DEFAULT ('')");

        $this->addSql('CREATE TABLE kitchen_photo (id INT AUTO_INCREMENT NOT NULL, filename VARCHAR(255) NOT NULL, caption_pt VARCHAR(255) DEFAULT NULL, caption_en VARCHAR(255) DEFAULT NULL, sort_order INT NOT NULL, active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, property_id INT NOT NULL, INDEX IDX_KITCHEN_PHOTO_PROPERTY (property_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE kitchen_utensil (id INT AUTO_INCREMENT NOT NULL, name_pt VARCHAR(255) NOT NULL, name_en VARCHAR(255) NOT NULL, category_pt VARCHAR(100) NOT NULL, category_en VARCHAR(100) NOT NULL, sort_order INT NOT NULL, active TINYINT(1) NOT NULL, property_id INT NOT NULL, INDEX IDX_KITCHEN_UTENSIL_PROPERTY (property_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE kitchen_photo ADD CONSTRAINT FK_KITCHEN_PHOTO_PROPERTY FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE kitchen_utensil ADD CONSTRAINT FK_KITCHEN_UTENSIL_PROPERTY FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE CASCADE');
    }

    public function postUp(Schema $schema): void
    {
        $propertyId = $this->connection->fetchOne('SELECT id FROM property ORDER BY id ASC LIMIT 1');
        if (!$propertyId) {
            return;
        }

        $this->connection->executeStatement(
            "UPDATE property SET kitchen_intro_pt = ?, kitchen_intro_en = ? WHERE id = ?",
            [
                'Sua cozinha ao ar livre está totalmente equipada para que você prepare refeições com tranquilidade, cercado pela natureza da Chapada.',
                'Your open-air kitchen is fully equipped so you can cook at ease, surrounded by the nature of Chapada dos Veadeiros.',
                $propertyId,
            ]
        );

        $photos = [
            ['kitchen-01.png', 'Bancada com louça, utensílios e cafeteira italiana', 'Counter with tableware, utensils and moka pot', 0],
            ['kitchen-02.png', 'Pia dupla, panelas e eletrodomésticos', 'Double sink, pots and appliances', 1],
            ['kitchen-03.png', 'Fogão a gás Venax com forno', 'Venax gas stove with oven', 2],
        ];

        foreach ($photos as [$filename, $captionPt, $captionEn, $sortOrder]) {
            $this->connection->insert('kitchen_photo', [
                'property_id' => $propertyId,
                'filename' => $filename,
                'caption_pt' => $captionPt,
                'caption_en' => $captionEn,
                'sort_order' => $sortOrder,
                'active' => 1,
                'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);
        }

        $utensils = [
            ['Fogão a gás Venax (3 bocas + forno)', 'Venax gas stove (3 burners + oven)', 'Eletrodomésticos', 'Appliances', 0],
            ['Chaleira elétrica', 'Electric kettle', 'Eletrodomésticos', 'Appliances', 1],
            ['Liquidificador Mondial', 'Mondial blender', 'Eletrodomésticos', 'Appliances', 2],
            ['Cafeteira italiana (moka)', 'Italian moka coffee maker', 'Preparo', 'Preparation', 3],
            ['Panelas inox com tampa de vidro', 'Stainless steel pots with glass lids', 'Panelas', 'Cookware', 4],
            ['Frigideira antiaderente', 'Non-stick frying pan', 'Panelas', 'Cookware', 5],
            ['Travessa com tampa', 'Covered serving dish', 'Panelas', 'Cookware', 6],
            ['Tábua de corte em madeira', 'Wooden cutting board', 'Preparo', 'Preparation', 7],
            ['Pratos, bowls e xícaras', 'Plates, bowls and mugs', 'Louça', 'Tableware', 8],
            ['Copos e taças', 'Glasses and wine glasses', 'Louça', 'Tableware', 9],
            ['Talheres e utensílios de cozinha', 'Cutlery and kitchen tools', 'Louça', 'Tableware', 10],
            ['Pia dupla inox', 'Double stainless steel sink', 'Área de lavagem', 'Sink area', 11],
            ['Torneira com filtro de água', 'Faucet with water filter', 'Área de lavagem', 'Sink area', 12],
            ['Escorredor de louça', 'Dish drying rack', 'Área de lavagem', 'Sink area', 13],
        ];

        foreach ($utensils as [$namePt, $nameEn, $categoryPt, $categoryEn, $sortOrder]) {
            $this->connection->insert('kitchen_utensil', [
                'property_id' => $propertyId,
                'name_pt' => $namePt,
                'name_en' => $nameEn,
                'category_pt' => $categoryPt,
                'category_en' => $categoryEn,
                'sort_order' => $sortOrder,
                'active' => 1,
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE kitchen_photo DROP FOREIGN KEY FK_KITCHEN_PHOTO_PROPERTY');
        $this->addSql('ALTER TABLE kitchen_utensil DROP FOREIGN KEY FK_KITCHEN_UTENSIL_PROPERTY');
        $this->addSql('DROP TABLE kitchen_photo');
        $this->addSql('DROP TABLE kitchen_utensil');
        $this->addSql('ALTER TABLE property DROP kitchen_intro_pt, DROP kitchen_intro_en');
    }
}
