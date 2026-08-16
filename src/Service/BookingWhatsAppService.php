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

    public function buildWelcomeMessage(Booking $booking): string
    {
        return Booking::LOCALE_EN === $booking->getGuestLocale()
            ? $this->buildEnglishWelcomeMessage($booking)
            : $this->buildPortugueseWelcomeMessage($booking);
    }

    private function buildPortugueseWelcomeMessage(Booking $booking): string
    {
        $property = $this->propertyRepository->getOrCreate();
        $conciergeUrl = $this->urlGenerator->generate('guest_home', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $code = $booking->getAccessCode();
        $checkIn = $booking->getCheckIn()->format('d/m/Y');
        $checkOut = $booking->getCheckOut()->format('d/m/Y');
        $contactPhone = $property->getContactPhone();
        $plural = $booking->getGuests() > 1;

        $greeting = $this->buildGreeting($booking->getGuestName(), 'pt');
        $reservaLine = $plural ? 'Recebemos a reserva de vocês com muita alegria!' : 'Recebemos sua reserva com muita alegria!';
        $receiveLine = $plural
            ? 'Será um prazer recebê-los no Domo Xangô. Garantimos que vai ser incrível. 🤗'
            : 'Será um prazer recebê-lo(a) no Domo Xangô. Garantimos que vai ser incrível. 🤗';
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

    private function buildEnglishWelcomeMessage(Booking $booking): string
    {
        $property = $this->propertyRepository->getOrCreate();
        $conciergeUrl = $this->urlGenerator->generate('guest_home', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $code = $booking->getAccessCode();
        $checkIn = $booking->getCheckIn()->format('d/m/Y');
        $checkOut = $booking->getCheckOut()->format('d/m/Y');
        $contactPhone = $property->getContactPhone();
        $plural = $booking->getGuests() > 1;

        $greeting = $this->buildGreeting($booking->getGuestName(), 'en');
        $reservaLine = $plural
            ? 'We were delighted to receive your reservation!'
            : 'We were delighted to receive your reservation!';
        $receiveLine = $plural
            ? 'It will be our pleasure to welcome you to Domo Xangô. We guarantee it will be incredible. 🤗'
            : 'It will be our pleasure to welcome you to Domo Xangô. We guarantee it will be incredible. 🤗';
        $experienceLine = $plural
            ? 'To ensure you have the best experience, we created a digital Welcome Book with all the important information about the space, breakfast, attractions and much more:'
            : 'To ensure you have the best experience, we created a digital Welcome Book with all the important information about the space, breakfast, attractions and much more:';
        $codeLine = 'Your access code';
        $helpLine = $plural
            ? 'If you need anything or have questions about the tips, I will be available on WhatsApp'
            : 'If you need anything or have questions about the tips, I will be available on WhatsApp';
        $closingLine = $plural
            ? 'We are preparing everything with care to welcome you. See you soon!'
            : 'We are preparing everything with care to welcome you. See you soon!';

        return implode("\n\n", [
            $greeting,
            $reservaLine,
            $receiveLine,
            "Stay: {$checkIn} to {$checkOut}",
            $experienceLine,
            $conciergeUrl,
            "{$codeLine}: {$code}",
            "{$helpLine}: {$contactPhone}.",
            $closingLine,
        ]);
    }

    private function buildGreeting(string $guestName, string $locale): string
    {
        $firstName = $this->guestFirstName($guestName);

        if ('en' === $locale) {
            return '' !== $firstName ? "Hello, {$firstName}!" : 'Hello!';
        }

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
