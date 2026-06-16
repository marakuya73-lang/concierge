<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616195000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove em dashes from seeded and stored guest-facing text';
    }

    public function up(Schema $schema): void
    {
        $textColumns = [
            'property' => [
                'name_pt', 'name_en', 'tagline_pt', 'tagline_en', 'description_pt', 'description_en',
                'check_in_instructions_pt', 'check_in_instructions_en', 'check_out_instructions_pt', 'check_out_instructions_en',
                'location_details_pt', 'location_details_en', 'address_pt', 'address_en',
                'rules_intro_pt', 'rules_intro_en', 'activities_intro_pt', 'activities_intro_en',
                'arrival_instructions_pt', 'arrival_instructions_en',
                'pets_policy', 'smoking_policy', 'silence_policy', 'visits_policy',
            ],
            'guide_spot' => ['title_pt', 'title_en', 'body_pt', 'body_en'],
            'faq_item' => ['question_pt', 'question_en', 'answer_pt', 'answer_en'],
            'house_rule' => ['title_pt', 'title_en', 'body_pt', 'body_en'],
            'activity_item' => ['title_pt', 'title_en', 'body_pt', 'body_en'],
            'extra' => ['name_pt', 'name_en', 'description_pt', 'description_en'],
        ];

        foreach ($textColumns as $table => $columns) {
            foreach ($columns as $column) {
                $this->addSql(sprintf(
                    "UPDATE %s SET %s = REPLACE(%s, '—', ', ') WHERE %s LIKE '%%—%%'",
                    $table,
                    $column,
                    $column,
                    $column,
                ));
            }
        }

        $this->addSql("UPDATE guide_spot SET body_pt = REPLACE(body_pt, 'véu sutil, e ', 'véu sutil e ') WHERE body_pt LIKE '%véu sutil, e %'");
    }

    public function down(Schema $schema): void
    {
        // Irreversible text normalization.
    }
}
