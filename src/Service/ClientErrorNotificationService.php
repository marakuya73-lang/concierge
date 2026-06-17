<?php

namespace App\Service;

use App\Entity\GuestClientError;
use App\Repository\PropertyRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ClientErrorNotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private PropertyRepository $propertyRepository,
        private UrlGeneratorInterface $urlGenerator,
        private WebPushService $webPushService,
    ) {
    }

    public function notifyAdmin(GuestClientError $error): void
    {
        $property = $this->propertyRepository->getOrCreate();
        $dashboardPath = $this->urlGenerator->generate('admin_dashboard', [], UrlGeneratorInterface::ABSOLUTE_PATH);
        $dashboardUrl = $this->urlGenerator->generate('admin_dashboard', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $booking = $error->getBooking();

        $summary = $this->buildSummary($error);
        $title = 'Erro no site do hóspede';

        $this->sendEmail($property->getContactEmail(), $property->getNamePt(), $error, $summary, $dashboardUrl, $booking);
        $this->webPushService->send(
            $title,
            $summary,
            $booking
                ? $this->urlGenerator->generate('admin_booking_show', ['id' => $booking->getId()], UrlGeneratorInterface::ABSOLUTE_PATH)
                : $dashboardPath,
            'client-error-'.$error->getId(),
        );
    }

    private function buildSummary(GuestClientError $error): string
    {
        $parts = [$error->getMessage()];
        if ($error->getAccessCode()) {
            $parts[] = 'código '.$error->getAccessCode();
        }
        if ($error->getBooking()) {
            $parts[] = $error->getBooking()->getGuestName();
        }

        return implode(' · ', $parts);
    }

    private function sendEmail(
        string $adminEmail,
        string $propertyName,
        GuestClientError $error,
        string $summary,
        string $dashboardUrl,
        $booking,
    ): void {
        $adminEmail = trim($adminEmail);
        if ('' === $adminEmail) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address($adminEmail, $propertyName))
            ->to($adminEmail)
            ->subject('Erro no site do hóspede — Domo Xangô')
            ->htmlTemplate('email/client_error.html.twig')
            ->context([
                'error' => $error,
                'summary' => $summary,
                'dashboardUrl' => $dashboardUrl,
                'booking' => $booking,
            ]);

        $this->mailer->send($email);
    }
}
