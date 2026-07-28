<?php

namespace App\Service;

use App\Entity\Booking;
use App\Repository\PropertyRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PlannedArrivalNotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private PropertyRepository $propertyRepository,
        private UrlGeneratorInterface $urlGenerator,
        private WebPushService $webPushService,
    ) {
    }

    public function notifyAdmin(Booking $booking, bool $updated = false): void
    {
        $property = $this->propertyRepository->getOrCreate();
        $bookingUrl = $this->urlGenerator->generate(
            'admin_booking_show',
            ['id' => $booking->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
        $bookingPath = $this->urlGenerator->generate(
            'admin_booking_show',
            ['id' => $booking->getId()],
            UrlGeneratorInterface::ABSOLUTE_PATH,
        );

        $this->sendEmail($property->getContactEmail(), $property->getNamePt(), $booking, $bookingUrl, $updated);
        $this->webPushService->send(
            $updated ? 'Horário de chegada actualizado' : 'Horário de chegada informado',
            sprintf(
                '%s · chegada %s · check-in %s',
                $booking->getGuestName(),
                $booking->getPlannedArrivalTime() ?? '-',
                $booking->getCheckIn()->format('d/m/Y'),
            ),
            $bookingPath,
            'planned-arrival-'.$booking->getId().'-'.($booking->getPlannedArrivalSubmittedAt()?->getTimestamp() ?? time()),
        );
    }

    private function sendEmail(string $adminEmail, string $propertyName, Booking $booking, string $bookingUrl, bool $updated): void
    {
        $adminEmail = trim($adminEmail);
        if ('' === $adminEmail) {
            return;
        }

        $subject = $updated
            ? sprintf('Horário de chegada actualizado — %s', $booking->getGuestName())
            : sprintf('Horário de chegada — %s', $booking->getGuestName());

        $email = (new TemplatedEmail())
            ->from(new Address($adminEmail, $propertyName))
            ->to($adminEmail)
            ->subject($subject)
            ->htmlTemplate('email/planned_arrival.html.twig')
            ->context([
                'booking' => $booking,
                'bookingUrl' => $bookingUrl,
                'updated' => $updated,
            ]);

        $this->mailer->send($email);
    }
}
