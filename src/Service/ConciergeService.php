<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\BookingExtra;
use App\Entity\Extra;
use App\Repository\BookingExtraRepository;
use App\Repository\BookingRepository;
use App\Repository\ExtraRepository;
use App\Repository\PropertyRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ConciergeService
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private PropertyRepository $propertyRepository,
        private ExtraRepository $extraRepository,
        private BookingExtraRepository $bookingExtraRepository,
        private ExtraRequestNotificationService $extraRequestNotificationService,
    ) {
    }

    public function verifyAccessCode(string $code, string $locale = 'pt'): array
    {
        $booking = $this->getValidBooking($code);
        $property = $this->propertyRepository->getOrCreate();

        $result = [
            'bookingId' => $booking->getId(),
            'guestName' => $booking->getGuestName(),
            'checkIn' => $booking->getCheckIn()->format('Y-m-d'),
            'checkOut' => $booking->getCheckOut()->format('Y-m-d'),
            'guests' => $booking->getGuests(),
            'accessCode' => $booking->getAccessCode(),
            'domeEntranceCode' => $property->getDomeEntranceCode(),
            'wifiName' => $property->getWifiName(),
            'wifiPassword' => $property->getWifiPassword(),
            'accessInstructions' => $property->getCheckInInstructions($locale),
            'checkOutInstructions' => $property->getCheckOutInstructions($locale),
            'locationDetails' => $property->getLocationDetails($locale),
            'address' => $property->getAddress($locale),
            'arrivalInstructions' => $property->getArrivalInstructions($locale),
            'specialNotes' => $booking->getNotes(),
            'mapUrl' => $property->getMapUrl(),
            'latitude' => $property->getLatitude(),
            'longitude' => $property->getLongitude(),
            'pixKey' => $property->getPixKey(),
            'contactPhone' => $property->getContactPhone(),
            'contactEmail' => $property->getContactEmail(),
            'instagramHandle' => $property->getInstagramHandle(),
            'wifiSecondaryName' => $property->getWifiSecondaryName(),
            'wifiSecondaryPassword' => $property->getWifiSecondaryPassword(),
            'checkInTime' => $property->getCheckInTime(),
            'checkInTimeEnd' => $property->getCheckInTimeEnd(),
            'checkOutTime' => $property->getCheckOutTime(),
            'petsPolicy' => $property->getPetsPolicy(),
            'smokingPolicy' => $property->getSmokingPolicy(),
            'silencePolicy' => $property->getSilencePolicy(),
            'visitsPolicy' => $property->getVisitsPolicy(),
        ];

        if ($booking->hasRajaaramSession()) {
            $result['rajaaram'] = [
                'therapy' => $booking->getRajaaramTherapyLabel($locale),
                'therapyTime' => $booking->getRajaaramTherapyTime(),
                'breakfastIncluded' => $booking->isRajaaramBreakfastIncluded(),
            ];
        }

        return $result;
    }

    public function getExtrasForGuest(string $code, string $locale = 'pt'): array
    {
        $booking = $this->getValidBooking($code);
        $available = $this->extraRepository->findActiveForGuestCount($booking->getGuests());
        $requests = $this->bookingExtraRepository->findByBooking($booking);

        return [
            'available' => array_map(fn (Extra $e) => $this->serializeExtra($e, $locale, $booking), $available),
            'myRequests' => array_map(fn (BookingExtra $be) => $this->serializeBookingExtra($be, $locale), $requests),
        ];
    }

    /** @return array{available: list<array<string, mixed>>, requested: list<array<string, mixed>>} */
    public function getFoodExtrasForGuest(string $code, string $locale = 'pt'): array
    {
        $all = $this->getExtrasForGuest($code, $locale);
        $foodIds = $this->extraRepository->findActiveIdsByCategories(['alimentação', 'alimentacao']);
        $isFood = static fn (int $id): bool => \in_array($id, $foodIds, true);

        $requested = array_values(array_filter(
            $all['myRequests'],
            static fn (array $request): bool => isset($request['extraId'])
                && $isFood((int) $request['extraId'])
                && BookingExtra::STATUS_CANCELLED !== ($request['status'] ?? ''),
        ));

        $requestedExtraIds = array_map(
            static fn (array $request): int => (int) $request['extraId'],
            $requested,
        );

        $hasBreakfastBooked = (bool) array_filter(
            $requested,
            static fn (array $request): bool => (bool) ($request['isBreakfast'] ?? false),
        );

        $available = array_values(array_filter(
            $all['available'],
            function (array $extra) use ($isFood, $requestedExtraIds, $hasBreakfastBooked): bool {
                if (!$isFood((int) $extra['id'])) {
                    return false;
                }

                if (\in_array((int) $extra['id'], $requestedExtraIds, true)) {
                    return false;
                }

                if ($hasBreakfastBooked && ($extra['isBreakfast'] ?? false)) {
                    return false;
                }

                return true;
            },
        ));

        return [
            'available' => $available,
            'requested' => $requested,
        ];
    }

    public function requestExtra(string $code, int $extraId, int $quantity, ?string $notes, string $locale = 'pt'): array
    {
        $booking = $this->getValidBooking($code);
        $extra = $this->extraRepository->find($extraId);

        if (!$extra || !$extra->isActive()) {
            throw new NotFoundHttpException('Extra não disponível');
        }

        if (!$extra->isAvailableForGuestCount($booking->getGuests())) {
            throw new AccessDeniedHttpException('Este extra não está disponível para o número de hóspedes da sua reserva.');
        }

        if (!$extra->canBeBookedBefore($this->getCheckInDateTime($booking))) {
            $hours = $extra->getLeadTimeHours();
            $message = 'en' === $locale
                ? sprintf('This extra requires booking at least %dh before check-in.', $hours)
                : sprintf('Este extra requer reserva com pelo menos %dh de antecedência ao check-in.', $hours);
            throw new AccessDeniedHttpException($message);
        }

        if ($this->bookingExtraRepository->guestAlreadyRequested($booking, $extraId)) {
            throw new AccessDeniedHttpException('Você já solicitou este extra.');
        }

        $bookingExtra = new BookingExtra();
        $bookingExtra->setBooking($booking);
        $bookingExtra->setExtra($extra);
        $bookingExtra->setQuantity(max(1, $quantity));
        $bookingExtra->setNotes($notes);
        $bookingExtra->setRequestedBy(BookingExtra::REQUESTED_BY_GUEST);
        $bookingExtra->setPriceAtBooking($extra->getPrice());
        $bookingExtra->setStatus(BookingExtra::STATUS_REQUESTED);

        $this->bookingExtraRepository->getEntityManager()->persist($bookingExtra);
        $this->bookingExtraRepository->getEntityManager()->flush();

        $this->extraRequestNotificationService->notifyAdmin($bookingExtra);

        $property = $this->propertyRepository->getOrCreate();
        $result = $this->serializeBookingExtra($bookingExtra, $locale);
        $total = ($bookingExtra->getPriceAtBooking() ?? 0) * $bookingExtra->getQuantity();
        $result['total'] = $total;
        $result['totalFormatted'] = 'R$ ' . number_format($total, 2, ',', '.');
        $result['pixKey'] = $property->getPixKey();
        $result['whatsappUrl'] = $this->buildExtraConfirmationWhatsAppUrl($property, $booking, $bookingExtra, $locale);

        return $result;
    }

    private function getValidBooking(string $code): Booking
    {
        $today = new \DateTimeImmutable('today');
        $booking = $this->bookingRepository->findByAccessCode($code);

        if (!$booking) {
            throw new AccessDeniedHttpException('Código inválido. Verifique seu código de acesso.');
        }

        if ($booking->isExpired($today)) {
            throw new AccessDeniedHttpException('Sua estadia já encerrou. Entre em contato com o anfitrião.');
        }

        return $booking;
    }

    private function serializeExtra(Extra $extra, string $locale, Booking $booking): array
    {
        $checkInAt = $this->getCheckInDateTime($booking);

        return [
            'id' => $extra->getId(),
            'name' => $extra->getName($locale),
            'description' => $extra->getDescription($locale),
            'price' => $extra->getPrice(),
            'currency' => $extra->getCurrency(),
            'category' => $extra->getCategory(),
            'icon' => $extra->getIcon(),
            'isBreakfast' => $this->isBreakfastExtra($extra),
            'leadTimeHours' => $extra->getLeadTimeHours(),
            'bookable' => $extra->canBeBookedBefore($checkInAt),
        ];
    }

    private function getCheckInDateTime(Booking $booking): \DateTimeImmutable
    {
        $property = $this->propertyRepository->getOrCreate();
        $parts = explode(':', $property->getCheckInTime());
        $hours = (int) ($parts[0] ?? 14);
        $minutes = (int) ($parts[1] ?? 0);

        return $booking->getCheckIn()->setTime($hours, $minutes);
    }

    private function serializeBookingExtra(BookingExtra $be, string $locale): array
    {
        $extra = $be->getExtra();

        return [
            'id' => $be->getId(),
            'extraId' => $extra?->getId(),
            'name' => $extra?->getName($locale),
            'quantity' => $be->getQuantity(),
            'status' => $be->getStatus(),
            'notes' => $be->getNotes(),
            'priceAtBooking' => $be->getPriceAtBooking(),
            'requestedBy' => $be->getRequestedBy(),
            'isBreakfast' => $extra ? $this->isBreakfastExtra($extra) : false,
            'createdAt' => $be->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    private function isBreakfastExtra(Extra $extra): bool
    {
        if (preg_match('/chef/i', $extra->getNamePt()) || preg_match('/chef/i', $extra->getNameEn())) {
            return false;
        }

        return 'coffee' === $extra->getIcon();
    }

    private function buildExtraConfirmationWhatsAppUrl(
        \App\Entity\Property $property,
        Booking $booking,
        BookingExtra $bookingExtra,
        string $locale,
    ): string {
        $phone = preg_replace('/\D/', '', $property->getContactPhone());
        $extra = $bookingExtra->getExtra();
        $name = $extra?->getName($locale) ?? '';
        $qty = $bookingExtra->getQuantity();
        $total = number_format(($bookingExtra->getPriceAtBooking() ?? 0) * $qty, 2, ',', '.');
        $code = $booking->getAccessCode();
        $notes = $bookingExtra->getNotes();

        if ('en' === $locale) {
            $text = "Hello! I'd like to confirm my extra request:\n\n• {$name} ×{$qty}\n• Total: R$ {$total}\n• Stay code: {$code}";
            if ($notes) {
                $text .= "\n• Notes: {$notes}";
            }
            $text .= "\n\nI've sent payment via Pix. Thank you!";
        } else {
            $text = "Olá! Gostaria de confirmar minha solicitação de extra:\n\n• {$name} ×{$qty}\n• Total: R$ {$total}\n• Código da estadia: {$code}";
            if ($notes) {
                $text .= "\n• Observações: {$notes}";
            }
            $text .= "\n\nRealizei o pagamento via Pix. Obrigado!";
        }

        return 'https://wa.me/' . $phone . '?text=' . rawurlencode($text);
    }
}
