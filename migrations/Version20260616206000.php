<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616206000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove em dashes from all guest-facing stored text';
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
                'kitchen_intro_pt', 'kitchen_intro_en',
                'pets_policy', 'smoking_policy', 'silence_policy', 'visits_policy',
            ],
            'guide_spot' => ['title_pt', 'title_en', 'body_pt', 'body_en'],
            'faq_item' => ['question_pt', 'question_en', 'answer_pt', 'answer_en'],
            'house_rule' => ['title_pt', 'title_en', 'body_pt', 'body_en'],
            'activity_item' => ['title_pt', 'title_en', 'body_pt', 'body_en'],
            'extra' => ['name_pt', 'name_en', 'description_pt', 'description_en'],
            'kitchen_utensil' => ['name_pt', 'name_en', 'category_pt', 'category_en'],
            'kitchen_photo' => ['caption_pt', 'caption_en'],
            'property_photo' => ['caption_pt', 'caption_en'],
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

        // Polish common patterns after blind replace
        $this->addSql("UPDATE guide_spot SET body_pt = REPLACE(body_pt, 'véu sutil, e ', 'véu sutil e ') WHERE body_pt LIKE '%véu sutil, e %'");
        $this->addSql("UPDATE guide_spot SET body_en = REPLACE(body_en, 'subtle veil, letting', 'subtle veil. Letting') WHERE body_en LIKE '%subtle veil, letting%'");

        $this->addSql("UPDATE activity_item SET body_pt = 'A poucos minutos a pé, águas turquesa caem entre paredões de quartzito, uma das cachoeiras mais encantadas da Chapada, quase no quintal do Domo.' WHERE title_pt = 'Cachoeiras Anjos e Arcanjos'");
        $this->addSql("UPDATE activity_item SET body_en = 'A short walk away, turquoise water spills over quartzite cliffs, one of the Chapada''s most enchanting falls, almost in Domo''s backyard.' WHERE title_en = 'Anjos & Arcanjos Waterfalls'");

        $this->addSql("UPDATE activity_item SET body_pt = 'Alto Paraíso é o \"Vale dos E.T.s\". Em noites de lua nova, o céu se abre sem poluição luminosa. Contemple do deck ou visite pontos da região.' WHERE title_pt = 'Observação de Fenômenos Celestes'");
        $this->addSql("UPDATE activity_item SET body_en = 'Alto Paraíso is the \"E.T. Valley\". On new moon nights, the sky opens without light pollution. Contemplate from the deck or visit spots in the region.' WHERE title_en = 'Celestial Phenomena Watching'");

        $this->addSql("UPDATE activity_item SET body_pt = 'Temos recomendações de guias locais se precisarem: trilhas, cachoeiras e a Chapada com quem conhece cada canto.' WHERE title_pt = 'Guias locais'");
        $this->addSql("UPDATE activity_item SET body_en = 'We have recommendations for local guides if you need them: trails, waterfalls, and Chapada with those who know every corner.' WHERE title_en = 'Local guides'");

        $this->addSql("UPDATE activity_item SET body_pt = 'Produtos frescos direto do produtor. Feira aos sábados, terças e quintas; aos domingos, a Feira Popular da Agricultura Familiar enche a cidade de cores e sabores.' WHERE title_pt = 'Feiras locais'");
        $this->addSql("UPDATE activity_item SET body_en = 'Fresh produce straight from growers. Market on Sat, Tue, Thu; on Sundays, the Popular Family Agriculture Fair fills the town with color and flavor.' WHERE title_en = 'Local markets'");
    }

    public function down(Schema $schema): void
    {
        // Irreversible text normalization.
    }
}
