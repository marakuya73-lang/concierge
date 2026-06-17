<?php

namespace App\Service;

use App\Entity\Booking;
use App\Repository\PropertyRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class BookingUpcomingReminderNotificationService
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
            'Check-in amanhã',
            sprintf('%s · %s · %d hóspede(s) · código %s', $booking->getGuestName(), $booking->getCheckIn()->format('d/m/Y'), $booking->getGuests(), $booking->getAccessCode()),
            $bookingPath,
            'booking-upcoming-'.$booking->getId(),
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
            ->subject(sprintf('Check-in amanhã — %s', $booking->getGuestName()))
            ->htmlTemplate('email/booking_upcoming_reminder.html.twig')
            ->context([
                'booking' => $booking,
                'bookingUrl' => $bookingUrl,
            ]);

        $this->mailer->send($email);
    }
}
