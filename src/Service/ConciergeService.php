<?php

namespace App\Service;

use App\Exception\StayEndedException;
use App\Entity\Booking;
use App\Entity\BookingExtra;
use App\Entity\Extra;
use App\Entity\Property;
use App\Repository\BookingDisabledExtraRepository;
use App\Repository\BookingExtraRepository;
use App\Repository\BookingRepository;
use App\Repository\ExtraRepository;
use App\Repository\PropertyRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ConciergeService
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private PropertyRepository $propertyRepository,
        private ExtraRepository $extraRepository,
        private BookingExtraRepository $bookingExtraRepository,
        private BookingDisabledExtraRepository $bookingDisabledExtraRepository,
        private ExtraRequestNotificationService $extraRequestNotificationService,
        private SelfCheckInNotificationService $selfCheckInNotificationService,
        private PlannedArrivalNotificationService $plannedArrivalNotificationService,
        private BookingCalendarSyncDispatcher $bookingCalendarSyncDispatcher,
    ) {
    }

    private const CHECKIN_TIMEZONE = 'America/Sao_Paulo';
    private const SELF_CHECKIN_DEADLINE_HOUR = 9;

    public function getBookingForActivity(string $code, string $locale = 'pt'): Booking
    {
        return $this->getValidBooking($code, $locale);
    }

    public function verifyAccessCode(string $code, string $locale = 'pt'): array
    {
        $booking = $this->getValidBooking($code, $locale);
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
            'mapUrl' => $property->getGoogleMapsUrl(),
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
            'selfCheckInRequested' => $booking->isSelfCheckInRequested(),
            'selfCheckInAvailable' => $this->canRequestSelfCheckIn($booking),
            'plannedArrivalTime' => $booking->getPlannedArrivalTime(),
        ];

        if ($booking->hasRajaaramSession()) {
            $result['rajaaram'] = [
                'isDuo' => $booking->isRajaaramDuo(),
                'sessions' => $booking->getRajaaramSessions($locale),
                'breakfastIncluded' => $booking->isRajaaramBreakfastIncluded(),
            ];
        }

        return $result;
    }

    public function requestSelfCheckIn(string $code, string $locale = 'pt'): array
    {
        $booking = $this->getValidBooking($code, $locale);

        if ($booking->hasRajaaramSession()) {
            throw new AccessDeniedHttpException('en' === $locale
                ? 'Self check-in is not available for Rajaaram stays.'
                : 'Self check-in não está disponível para estadias Rajaaram.');
        }

        if ($booking->isSelfCheckInRequested()) {
            throw new AccessDeniedHttpException('en' === $locale
                ? 'Self check-in has already been requested for this stay.'
                : 'Self check-in já foi solicitado para esta estadia.');
        }

        if (!$this->canRequestSelfCheckIn($booking)) {
            throw new AccessDeniedHttpException('en' === $locale
                ? 'Self check-in can only be requested until 9:00 AM on your check-in date.'
                : 'Self check-in só pode ser solicitado até às 9h do dia do check-in.');
        }

        $booking->setSelfCheckInRequested(true);
        $booking->setSelfCheckInRequestedAt(new \DateTimeImmutable('now', new \DateTimeZone(self::CHECKIN_TIMEZONE)));
        $this->bookingRepository->getEntityManager()->flush();

        $this->selfCheckInNotificationService->notifyAdmin($booking);

        return [
            'selfCheckInRequested' => true,
            'message' => 'en' === $locale
                ? 'Self check-in confirmed. Use the dome door code when you arrive.'
                : 'Self check-in confirmado. Use o código da porta do domo ao chegar.',
        ];
    }

    public function canRequestSelfCheckIn(Booking $booking, ?\DateTimeImmutable $now = null): bool
    {
        if ($booking->hasRajaaramSession()) {
            return false;
        }

        if ($booking->isSelfCheckInRequested()) {
            return false;
        }

        $timezone = new \DateTimeZone(self::CHECKIN_TIMEZONE);
        $now ??= new \DateTimeImmutable('now', $timezone);
        $deadline = new \DateTimeImmutable(
            $booking->getCheckIn()->format('Y-m-d').sprintf(' %02d:00:00', self::SELF_CHECKIN_DEADLINE_HOUR),
            $timezone,
        );

        return $now < $deadline;
    }

    public function submitPlannedArrival(string $code, string $time, string $locale = 'pt'): array
    {
        $booking = $this->getValidBooking($code, $locale);

        if ($booking->hasRajaaramSession()) {
            throw new AccessDeniedHttpException('en' === $locale
                ? 'Planned arrival time is not needed for Rajaaram stays.'
                : 'Horário de chegada não é necessário para estadias Rajaaram.');
        }

        if ($booking->isSelfCheckInRequested()) {
            throw new AccessDeniedHttpException('en' === $locale
                ? 'Planned arrival time is not needed for self check-in stays.'
                : 'Horário de chegada não é necessário para estadias com self check-in.');
        }

        $normalized = Property::normalizeClockTime($time);
        if (null === $normalized) {
            throw new AccessDeniedHttpException('en' === $locale
                ? 'Please enter a valid arrival time.'
                : 'Indique um horário de chegada válido.');
        }

        $property = $this->propertyRepository->getOrCreate();
        if (!$property->allowsArrivalAt($normalized)) {
            $from = $property->getCheckInTime();
            $to = $property->getCheckInTimeEnd();
            throw new UnprocessableEntityHttpException('en' === $locale
                ? sprintf('Check-in is from %s to %s.', $from, $to)
                : sprintf('O check-in é das %s às %s.', $from, $to));
        }

        $previous = $booking->getPlannedArrivalTime();
        $isUpdate = null !== $previous && $previous !== $normalized;

        $booking->setPlannedArrivalTime($normalized);
        $booking->setPlannedArrivalSubmittedAt(new \DateTimeImmutable('now', new \DateTimeZone(self::CHECKIN_TIMEZONE)));
        $this->bookingRepository->getEntityManager()->flush();
        $this->bookingCalendarSyncDispatcher->afterBookingSaved($booking);

        if (!$previous || $isUpdate) {
            $this->plannedArrivalNotificationService->notifyAdmin($booking, $isUpdate);
        }

        return [
            'plannedArrivalTime' => $normalized,
            'updated' => $isUpdate,
            'message' => 'en' === $locale
                ? ($isUpdate ? 'Arrival time updated. Thank you!' : 'Arrival time saved. Thank you!')
                : ($isUpdate ? 'Horário de chegada actualizado. Obrigado!' : 'Horário de chegada registado. Obrigado!'),
        ];
    }

    public function getExtrasForGuest(string $code, string $locale = 'pt'): array
    {
        $booking = $this->getValidBooking($code, $locale);
        $available = $this->extraRepository->findActiveForGuestCount($booking->getGuests());

        if ($booking->hasRajaaramSession()) {
            $available = array_values(array_filter(
                $available,
                static fn (Extra $extra): bool => !$extra->isRajaaramExtra(),
            ));
        }

        $disabledIds = $this->bookingDisabledExtraRepository->findDisabledExtraIds($booking);
        if ($disabledIds) {
            $available = array_values(array_filter(
                $available,
                static fn (Extra $extra): bool => !\in_array($extra->getId(), $disabledIds, true),
            ));
        }

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
        $booking = $this->getValidBooking($code, $locale);
        $extra = $this->extraRepository->find($extraId);

        if (!$extra || !$extra->isActive()) {
            throw new NotFoundHttpException('Extra não disponível');
        }

        if (!$extra->isAvailableForGuestCount($booking->getGuests())) {
            throw new AccessDeniedHttpException('Este extra não está disponível para o número de hóspedes da sua reserva.');
        }

        if ($booking->hasRajaaramSession() && $extra->isRajaaramExtra()) {
            throw new AccessDeniedHttpException('en' === $locale
                ? 'Rajaaram therapies are already included in your Rajaaram stay.'
                : 'As terapias Rajaaram já fazem parte da sua estadia Rajaaram.');
        }

        $disabledIds = $this->bookingDisabledExtraRepository->findDisabledExtraIds($booking);
        if (\in_array($extraId, $disabledIds, true)) {
            throw new AccessDeniedHttpException('en' === $locale
                ? 'This extra is not available for your stay.'
                : 'Este extra não está disponível para a sua estadia.');
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

    public function cancelExtraRequest(string $code, int $bookingExtraId, string $locale = 'pt'): array
    {
        $booking = $this->getValidBooking($code, $locale);
        $bookingExtra = $this->bookingExtraRepository->findOneForBooking($booking, $bookingExtraId);

        if (!$bookingExtra) {
            throw new NotFoundHttpException('en' === $locale
                ? 'Request not found'
                : 'Solicitação não encontrada');
        }

        if (!$bookingExtra->canBeCancelledByGuest()) {
            throw new AccessDeniedHttpException('en' === $locale
                ? 'This request has already been confirmed and can no longer be cancelled.'
                : 'Esta solicitação já foi confirmada e não pode mais ser cancelada.');
        }

        $bookingExtra->setStatus(BookingExtra::STATUS_CANCELLED);
        $this->bookingExtraRepository->getEntityManager()->flush();

        return $this->serializeBookingExtra($bookingExtra, $locale);
    }

    private function getValidBooking(string $code, string $locale = 'pt'): Booking
    {
        $today = new \DateTimeImmutable('today', new \DateTimeZone(self::CHECKIN_TIMEZONE));
        $booking = $this->bookingRepository->findByAccessCode($code);

        if (!$booking) {
            throw new AccessDeniedHttpException('en' === $locale
                ? 'Invalid code. Please check your access code.'
                : 'Código inválido. Verifique seu código de acesso.');
        }

        if ($booking->isExpired($today)) {
            throw new StayEndedException($this->getStayEndedMessage($locale));
        }

        return $booking;
    }

    private function getStayEndedMessage(string $locale): string
    {
        $property = $this->propertyRepository->getOrCreate();
        $phone = $property->getContactPhone();
        $email = $property->getContactEmail();

        if ('en' === $locale) {
            return sprintf(
                'Your stay has ended. Thank you for staying with us at Domo Xangô! If you need any information, please contact us on WhatsApp %s or by email at %s.',
                $phone,
                $email,
            );
        }

        return sprintf(
            'Sua estadia já encerrou. Obrigado por estar conosco no Domo Xangô! Se precisar de qualquer informação, entre em contato pelo WhatsApp %s ou pelo e-mail %s.',
            $phone,
            $email,
        );
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
            'isRajaaram' => $extra->isRajaaramExtra(),
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
            'name' => $be->getDisplayName($locale),
            'quantity' => $be->getQuantity(),
            'status' => $be->getStatus(),
            'notes' => $be->getNotes(),
            'priceAtBooking' => $be->getPriceAtBooking(),
            'requestedBy' => $be->getRequestedBy(),
            'category' => $extra?->getCategory(),
            'isBreakfast' => $extra ? $this->isBreakfastExtra($extra) : false,
            'isCustom' => $be->isCustom(),
            'createdAt' => $be->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'canCancel' => $be->canBeCancelledByGuest(),
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
        $name = $bookingExtra->getDisplayName($locale);
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
