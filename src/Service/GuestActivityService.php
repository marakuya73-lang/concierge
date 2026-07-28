<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\GuestActivityLog;
use App\Repository\GuestActivityLogRepository;
use Doctrine\ORM\EntityManagerInterface;

class GuestActivityService
{
    private const DEDUP_MINUTES = 5;

    /** @var array<string, string> */
    private const SECTION_LABELS = [
        'home' => 'Início',
        'welcome' => 'Boas-vindas',
        'checkin' => 'Check-in',
        'location' => 'Localização',
        'wifi' => 'Wi-Fi',
        'rules' => 'Regras da casa',
        'faq' => 'Perguntas frequentes',
        'facilities' => 'Comodidades',
        'food' => 'Cozinha e alimentação',
        'activities' => 'Atividades',
        'markets' => 'Mercados',
        'extras' => 'Extras',
        'contact' => 'Contacto',
        'social' => 'Redes sociais',
        'stay' => 'Acesso ao concierge',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private GuestActivityLogRepository $activityLogRepository,
    ) {
    }

    public function recordLogin(Booking $booking): void
    {
        $now = new \DateTimeImmutable();
        $booking->setLoginCount($booking->getLoginCount() + 1);
        $booking->setLastLoginAt($now);

        $this->entityManager->flush();

        $this->record(
            booking: $booking,
            type: GuestActivityLog::TYPE_LOGIN,
            section: 'login',
            label: 'Entrada com código',
        );
    }

    public function recordPageView(Booking $booking, string $section, ?string $label = null): void
    {
        $section = $this->normalizeSection($section);
        if ('login' === $section) {
            return;
        }

        $this->record(
            booking: $booking,
            type: GuestActivityLog::TYPE_PAGE_VIEW,
            section: $section,
            label: $label ?? self::SECTION_LABELS[$section] ?? $section,
        );
    }

    public function getSectionLabel(string $section): string
    {
        $section = $this->normalizeSection($section);

        return self::SECTION_LABELS[$section] ?? $section;
    }

    private function record(
        Booking $booking,
        string $type,
        string $section,
        ?string $label = null,
    ): void {
        $section = $this->normalizeSection($section);
        $label = $this->truncate($label ?? self::SECTION_LABELS[$section] ?? $section, 120);
        $accessCode = $booking->getAccessCode();
        $fingerprint = hash('sha256', $booking->getId().'|'.$type.'|'.$section);
        $since = new \DateTimeImmutable(sprintf('-%d minutes', self::DEDUP_MINUTES));

        if ($this->activityLogRepository->hasDuplicateSince($fingerprint, $since)) {
            return;
        }

        $log = (new GuestActivityLog())
            ->setBooking($booking)
            ->setAccessCode($accessCode)
            ->setType($type)
            ->setSection($section)
            ->setLabel($label)
            ->setFingerprint($fingerprint);

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    private function normalizeSection(string $section): string
    {
        return strtolower(trim($section));
    }

    private function truncate(string $value, int $maxLength): string
    {
        if (strlen($value) <= $maxLength) {
            return $value;
        }

        return substr($value, 0, $maxLength - 1).'…';
    }
}
