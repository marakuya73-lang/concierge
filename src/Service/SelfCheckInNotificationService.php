<?php

namespace App\Service;

use App\Entity\Booking;
use App\Repository\PropertyRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SelfCheckInNotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private PropertyRepository $propertyRepository,
        private UrlGeneratorInterface $urlGenerator,
        private WebPushService $webPushService,
    ) {
    }

    public function notifyAdmin(Booking $booking): void
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

        $this->sendEmail($property->getContactEmail(), $property->getNamePt(), $booking, $bookingUrl);
        $this->webPushService->send(
            'Self check-in solicitado',
            sprintf('%s · check-in %s · código %s', $booking->getGuestName(), $booking->getCheckIn()->format('d/m/Y'), $booking->getAccessCode()),
            $bookingPath,
            'self-checkin-'.$booking->getId(),
        );
    }

    private function sendEmail(string $adminEmail, string $propertyName, Booking $booking, string $bookingUrl): void
    {
        $adminEmail = trim($adminEmail);
        if ('' === $adminEmail) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address($adminEmail, $propertyName))
            ->to($adminEmail)
            ->subject(sprintf('Self check-in — %s', $booking->getGuestName()))
            ->htmlTemplate('email/self_checkin.html.twig')
            ->context([
                'booking' => $booking,
                'bookingUrl' => $bookingUrl,
            ]);

        $this->mailer->send($email);
    }
}
