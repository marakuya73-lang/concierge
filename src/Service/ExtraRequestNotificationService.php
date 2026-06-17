<?php

namespace App\Service;

use App\Entity\BookingExtra;
use App\Repository\PropertyRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ExtraRequestNotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private PropertyRepository $propertyRepository,
        private UrlGeneratorInterface $urlGenerator,
        private WebPushService $webPushService,
    ) {
    }

    public function notifyAdmin(BookingExtra $bookingExtra): void
    {
        $property = $this->propertyRepository->getOrCreate();
        $booking = $bookingExtra->getBooking();
        $extra = $bookingExtra->getExtra();
        if (!$booking || !$extra) {
            return;
        }

        $total = ($bookingExtra->getPriceAtBooking() ?? 0) * $bookingExtra->getQuantity();
        $totalFormatted = 'R$ '.number_format($total, 2, ',', '.');
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

        $this->sendEmail($property->getContactEmail(), $property->getNamePt(), $bookingExtra, $booking, $extra, $totalFormatted, $bookingUrl);
        $this->webPushService->send(
            sprintf('Nova solicitação — %s', $extra->getNamePt()),
            sprintf('%s · %s ×%d · %s', $booking->getGuestName(), $extra->getNamePt(), $bookingExtra->getQuantity(), $totalFormatted),
            $bookingPath,
            'extra-request-'.$bookingExtra->getId(),
        );
    }

    private function sendEmail(
        string $adminEmail,
        string $propertyName,
        BookingExtra $bookingExtra,
        $booking,
        $extra,
        string $totalFormatted,
        string $bookingUrl,
    ): void {
        $adminEmail = trim($adminEmail);
        if ('' === $adminEmail) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address($adminEmail, $propertyName))
            ->to($adminEmail)
            ->subject(sprintf('Nova solicitação de extra — %s', $extra->getNamePt()))
            ->htmlTemplate('email/extra_request.html.twig')
            ->context([
                'bookingExtra' => $bookingExtra,
                'booking' => $booking,
                'extra' => $extra,
                'totalFormatted' => $totalFormatted,
                'bookingUrl' => $bookingUrl,
            ]);

        $this->mailer->send($email);
    }
}
