<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616181000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert Airbnb iCal blockages into direct bookings with access codes';
    }

    public function up(Schema $schema): void
    {
        $blocks = $this->connection->fetchAllAssociative(
            "SELECT id, external_uid, start_date, end_date, label, notes, last_synced_at FROM blocked_period WHERE external_uid NOT LIKE 'manual-%'"
        );

        foreach ($blocks as $block) {
            $existing = $this->connection->fetchOne(
                'SELECT id FROM booking WHERE external_uid = ?',
                [$block['external_uid']]
            );

            if ($existing) {
                $this->connection->delete('blocked_period', ['id' => $block['id']]);
                continue;
            }

            $this->connection->insert('booking', [
                'guest_name' => 'Reserva direta',
                'guest_email' => 'pendente@domo.local',
                'check_in' => $block['start_date'],
                'check_out' => $block['end_date'],
                'guests' => 1,
                'access_code' => $this->generateUniqueCode(),
                'source' => 'Direct (Airbnb)',
                'status' => 'confirmed',
                'notes' => $block['notes'],
                'stay_price' => null,
                'external_uid' => $block['external_uid'],
                'ical_summary' => $block['label'],
                'last_synced_at' => $block['last_synced_at'],
                'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);

            $this->connection->delete('blocked_period', ['id' => $block['id']]);
        }
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
    }
}
