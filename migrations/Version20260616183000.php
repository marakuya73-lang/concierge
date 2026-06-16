<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616183000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert remaining blocked periods to bookings and drop blocked_period table';
    }

    public function up(Schema $schema): void
    {
        $blocks = $this->connection->fetchAllAssociative(
            'SELECT id, external_uid, start_date, end_date, label, notes, last_synced_at FROM blocked_period'
        );

        foreach ($blocks as $block) {
            $existing = $this->connection->fetchOne(
                'SELECT id FROM booking WHERE external_uid = ?',
                [$block['external_uid']]
            );

            if (!$existing) {
                $this->connection->insert('booking', [
                    'guest_name' => 'Reserva directa',
                    'guest_email' => 'pendente@domo.local',
                    'check_in' => $block['start_date'],
                    'check_out' => $block['end_date'],
                    'guests' => 1,
                    'access_code' => $this->generateUniqueCode(),
                    'source' => 'Site',
                    'status' => 'confirmed',
                    'notes' => $block['notes'],
                    'stay_price' => null,
                    'external_uid' => str_starts_with($block['external_uid'], 'manual-')
                        ? null
                        : $block['external_uid'],
                    'ical_summary' => $block['label'],
                    'last_synced_at' => $block['last_synced_at'],
                    'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                ]);
            }
        }

        $this->addSql('DROP TABLE blocked_period');
    }

    private function generateUniqueCode(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = '';
            for ($i = 0; $i < 5; ++$i) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $exists = $this->connection->fetchOne('SELECT id FROM booking WHERE access_code = ?', [$code]);
        } while ($exists);

        return $code;
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE blocked_period (id INT AUTO_INCREMENT NOT NULL, external_uid VARCHAR(255) NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, label VARCHAR(255) NOT NULL, last_synced_at DATETIME DEFAULT NULL, notes LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_7104E1B8F3B1A8CE (external_uid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
    }
}
