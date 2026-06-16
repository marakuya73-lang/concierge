<?php

namespace App\Service;

use App\Entity\Booking;
use App\Repository\PropertyRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class BookingWhatsAppService
{
    public function __construct(
        private PropertyRepository $propertyRepository,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function getWelcomeUrl(Booking $booking): ?string
    {
        $digits = $booking->getGuestWhatsappDigits();
        if (!$digits) {
            return null;
        }

        return 'https://wa.me/'.$digits.'?text='.rawurlencode($this->buildWelcomeMessage($booking));
    }

    public function buildWelcomeMessage(Booking $booking): string
    {
        $property = $this->propertyRepository->getOrCreate();
        $conciergeUrl = $this->urlGenerator->generate('guest_home', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $code = $booking->getAccessCode();
        $checkIn = $booking->getCheckIn()->format('d/m/Y');
        $checkOut = $booking->getCheckOut()->format('d/m/Y');
        $contactPhone = $property->getContactPhone();
        $plural = $booking->getGuests() > 1;

        $greeting = $this->buildGreeting($booking->getGuestName());
        $reservaLine = $plural ? 'Recebemos a reserva de vocês com muita alegria!' : 'Recebemos sua reserva com muita alegria!';
        $receiveLine = $plural
            ? 'Será um prazer recebê-los no Domo Xangô — garantimos que vai ser incrível. 🤗'
            : 'Será um prazer recebê-lo(a) no Domo Xangô — garantimos que vai ser incrível. 🤗';
        $experienceLine = $plural
            ? 'Para garantir que tenham a melhor experiência, criamos um Welcome Book digital com todas as informações importantes sobre o espaço, café da manhã, atrativos e muito mais:'
            : 'Para garantir que tenha a melhor experiência, criamos um Welcome Book digital com todas as informações importantes sobre o espaço, café da manhã, atrativos e muito mais:';
        $codeLine = $plural ? 'Código de acesso de vocês' : 'Seu código de acesso';
        $helpLine = $plural
            ? 'Caso precisem de algo ou tenham dúvidas ou perguntas sobre as dicas, estarei à disposição pelo WhatsApp'
            : 'Caso precise de algo ou tenha dúvidas ou perguntas sobre as dicas, estarei à disposição pelo WhatsApp';
        $closingLine = $plural
            ? 'Estamos preparando tudo com carinho para recebê-los. Até breve!'
            : 'Estamos preparando tudo com carinho para recebê-lo(a). Até breve!';

        return implode("\n\n", [
            $greeting,
            $reservaLine,
            $receiveLine,
            "Estadia: {$checkIn} a {$checkOut}",
            $experienceLine,
            $conciergeUrl,
            "{$codeLine}: {$code}",
            "{$helpLine}: {$contactPhone}.",
            $closingLine,
        ]);
    }

    private function buildGreeting(string $guestName): string
    {
        $firstName = $this->guestFirstName($guestName);

        return '' !== $firstName ? "Olá, {$firstName}!" : 'Olá!';
    }

    private function guestFirstName(string $guestName): string
    {
        $guestName = trim($guestName);
        if ('' === $guestName
            || Booking::GUEST_NAME_PENDING === $guestName
            || str_starts_with($guestName, 'Reserva direct')) {
            return '';
        }

        return explode(' ', $guestName)[0];
    }
}
