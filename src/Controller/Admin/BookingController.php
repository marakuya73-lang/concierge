<?php

namespace App\Controller\Admin;

use App\Entity\Booking;
use App\Entity\BookingDisabledExtra;
use App\Entity\BookingExtra;
use App\Entity\Extra;
use App\Entity\Property;
use App\Form\BookingType;
use App\Repository\BookingDisabledExtraRepository;
use App\Repository\BookingExtraRepository;
use App\Repository\BookingRepository;
use App\Repository\ExtraRepository;
use App\Repository\GuestActivityLogRepository;
use App\Repository\PropertyRepository;
use App\Service\AccessCodeGenerator;
use App\Service\BookingCalendarSyncDispatcher;
use App\Service\BookingLifecycleService;
use App\Service\BookingWhatsAppService;
use App\Service\FollowUpWhatsAppService;
use App\Service\RajaaramCalendarSuggestionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/bookings')]
class BookingController extends AbstractAdminController
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private ExtraRepository $extraRepository,
        private BookingExtraRepository $bookingExtraRepository,
        private BookingDisabledExtraRepository $bookingDisabledExtraRepository,
        private GuestActivityLogRepository $guestActivityLogRepository,
        private AccessCodeGenerator $accessCodeGenerator,
        private BookingLifecycleService $bookingLifecycleService,
        private BookingWhatsAppService $bookingWhatsAppService,
        private FollowUpWhatsAppService $followUpWhatsAppService,
        private BookingCalendarSyncDispatcher $bookingCalendarSyncDispatcher,
        private PropertyRepository $propertyRepository,
        private EntityManagerInterface $em,
        private RajaaramCalendarSuggestionService $rajaaramCalendarSuggestionService,
    ) {
    }

    #[Route('', name: 'admin_bookings')]
    public function index(): Response
    {
        $today = new \DateTimeImmutable('today');
        $this->bookingLifecycleService->markPastBookingsCompleted($today);

        return $this->render('admin/bookings/index.html.twig', [
            'currentBookings' => $this->bookingRepository->findCurrentStays($today),
            'upcomingBookings' => $this->bookingRepository->findUpcoming($today),
            'pendingSiteBookings' => $this->bookingRepository->findPendingSiteBookings($today),
            'pastBookings' => $this->bookingRepository->findPast($today),
            'lastIcalSyncAt' => $this->propertyRepository->getOrCreate()->getAirbnbIcalLastSyncAt(),
            'lastGoogleCalendarSyncAt' => $this->propertyRepository->getOrCreate()->getGoogleCalendarLastSyncAt(),
            'today' => $today,
        ]);
    }

    #[Route('/new', name: 'admin_booking_new')]
    public function new(Request $request): Response
    {
        $booking = new Booking();
        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $booking->setAccessCode($this->accessCodeGenerator->generateUnique());
            $this->bookingLifecycleService->refreshStatus($booking);
            $this->em->persist($booking);
            $this->em->flush();
            $this->bookingCalendarSyncDispatcher->afterBookingSaved($booking);
            $this->flashAfterBookingSaved($booking, 'Reserva criada. Código de acesso: '.$booking->getAccessCode());

            return $this->redirectToRoute('admin_booking_show', ['id' => $booking->getId()]);
        }

        return $this->render('admin/bookings/form.html.twig', [
            'form' => $form,
            'booking' => null,
        ]);
    }

    #[Route('/{id}', name: 'admin_booking_show', requirements: ['id' => '\d+'])]
    public function show(Booking $booking, Request $request): Response
    {
        $isEditing = $request->query->getBoolean('edit');
        $form = $this->createForm(BookingType::class, $booking, ['include_access_code' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->lockDatesIfManuallyChanged($booking);
            $this->bookingLifecycleService->refreshStatus($booking);
            $this->em->flush();
            $this->bookingCalendarSyncDispatcher->afterBookingSaved($booking);
            $this->flashAfterBookingSaved($booking, 'Reserva atualizada.');

            return $this->redirectToRoute('admin_booking_show', ['id' => $booking->getId()]);
        }

        if ($booking->needsGuestInfo() && !$isEditing) {
            $isEditing = true;
        }

        return $this->render('admin/bookings/show.html.twig', [
            'booking' => $booking,
            'form' => $form,
            'isEditing' => $isEditing || $form->isSubmitted(),
            'bookingExtras' => $this->bookingExtraRepository->findByBooking($booking),
            'availableExtras' => $this->extraRepository->findActive(),
            'disabledExtraIds' => $this->bookingDisabledExtraRepository->findDisabledExtraIds($booking),
            'guestWelcomeMessage' => $this->bookingWhatsAppService->buildWelcomeMessage($booking),
            'followUps' => $this->followUpWhatsAppService->panelForBooking($booking),
            'activityLogs' => $this->guestActivityLogRepository->findByBooking($booking),
            'property' => $this->propertyRepository->getOrCreate(),
            'rajaaramSuggestions' => $this->rajaaramCalendarSuggestionService->suggestionsFor($booking),
        ]);
    }

    #[Route('/{id}/rajaaram-calendar-force', name: 'admin_booking_force_rajaaram_calendar', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function forceRajaaramCalendar(Booking $booking, Request $request): Response
    {
        $this->validateAdminCsrf($request);
        $this->bookingCalendarSyncDispatcher->afterBookingSaved($booking, true);

        if ($booking->rajaaramCalendarConflictsOf('busy')) {
            $this->addFlash('error', 'O horário continua ocupado ou o calendário Rajaaram não aceitou o evento.');
        } elseif ($booking->rajaaramCalendarConflictsOf('drift')) {
            $this->addFlash('error', 'Ainda há diferenças com o calendário Rajaaram.');
        } else {
            $this->addFlash('success', 'O calendário Rajaaram foi actualizado com esta reserva.');
        }

        return $this->redirectToRoute('admin_booking_show', ['id' => $booking->getId()]);
    }

    #[Route('/{id}/rajaaram-calendar-pull', name: 'admin_booking_pull_rajaaram_calendar', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function pullRajaaramCalendar(Booking $booking, Request $request): Response
    {
        $this->validateAdminCsrf($request);
        $this->bookingCalendarSyncDispatcher->pullTherapiesFromRajaaram($booking);
        $this->addFlash('success', 'A reserva foi actualizada com o que está no calendário Rajaaram.');

        return $this->redirectToRoute('admin_booking_show', ['id' => $booking->getId()]);
    }

    #[Route('/{id}/rajaaram-suggestion', name: 'admin_booking_apply_rajaaram_suggestion', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function applyRajaaramSuggestion(Booking $booking, Request $request): Response
    {
        $this->validateAdminCsrf($request);

        $eventIds = $request->request->all('eventIds');
        $eventIds = \is_array($eventIds) ? array_values(array_map('strval', $eventIds)) : [];
        $applied = $this->rajaaramCalendarSuggestionService->apply($booking, $eventIds);

        if (0 === $applied) {
            $this->addFlash('error', 'Seleccione pelo menos uma terapia do calendário Rajaaram.');

            return $this->redirectToRoute('admin_booking_show', ['id' => $booking->getId()]);
        }

        $this->em->flush();
        $this->bookingCalendarSyncDispatcher->afterBookingSaved($booking);
        $this->flashAfterBookingSaved($booking, $applied > 1
            ? 'Reserva passou a Rajaaram e as terapias foram adicionadas. O hóspede já as vê no concierge.'
            : 'Reserva passou a Rajaaram e a terapia foi adicionada. O hóspede já a vê no concierge.');

        return $this->redirectToRoute('admin_booking_show', ['id' => $booking->getId()]);
    }

    #[Route('/{id}/rajaaram-suggestion/dismiss', name: 'admin_booking_dismiss_rajaaram_suggestion', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function dismissRajaaramSuggestion(Booking $booking, Request $request): Response
    {
        $this->validateAdminCsrf($request);

        $eventIds = $request->request->all('eventIds');
        $eventIds = \is_array($eventIds) ? array_values(array_map('strval', $eventIds)) : [];
        if ([] === $eventIds) {
            $eventIds = array_column($this->rajaaramCalendarSuggestionService->suggestionsFor($booking), 'eventId');
        }

        $this->rajaaramCalendarSuggestionService->dismiss($booking, $eventIds);
        $this->em->flush();
        $this->addFlash('success', 'Sugestão de terapia ignorada para esta reserva.');

        return $this->redirectToRoute('admin_booking_show', ['id' => $booking->getId()]);
    }

    #[Route('/{id}/regenerate-code', name: 'admin_booking_regenerate_code', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function regenerateCode(Booking $booking, Request $request): Response
    {
        $this->validateAdminCsrf($request);
        $booking->setAccessCode($this->accessCodeGenerator->generateUnique());
        $this->em->flush();
        $this->addFlash('success', 'Novo código: '.$booking->getAccessCode());

        return $this->redirectToRoute('admin_booking_show', ['id' => $booking->getId()]);
    }

    #[Route('/{id}/delete', name: 'admin_booking_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Booking $booking, Request $request): Response
    {
        $this->validateAdminCsrf($request);
        $this->bookingCalendarSyncDispatcher->afterBookingDeleted($booking);
        $this->em->remove($booking);
        $this->em->flush();
        $this->addFlash('success', 'Reserva removida.');

        return $this->redirectToRoute('admin_booking_show', ['id' => $booking->getId()]);
    }

    #[Route('/{id}/self-checkin', name: 'admin_booking_toggle_self_checkin', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleSelfCheckIn(Booking $booking, Request $request): Response
    {
        $this->validateAdminCsrf($request);

        if ($booking->isSelfCheckInRequested()) {
            $booking->setSelfCheckInRequested(false);
            $booking->setSelfCheckInRequestedAt(null);
            $this->em->flush();
            $this->addFlash('success', 'Self check-in desactivado para esta estadia.');
        } else {
            $booking->setSelfCheckInRequested(true);
            $booking->setSelfCheckInRequestedAt(new \DateTimeImmutable('now', new \DateTimeZone('America/Sao_Paulo')));
            $this->em->flush();
            $this->addFlash('success', 'Self check-in activado. O hóspede verá as instruções de entrada autónoma na concierge.');
        }

        return $this->redirectToRoute('admin_booking_show', ['id' => $booking->getId()]);
    }

    #[Route('/{id}/planned-arrival', name: 'admin_booking_planned_arrival', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function updatePlannedArrival(Booking $booking, Request $request): Response
    {
        $this->validateAdminCsrf($request);

        if ('clear' === $request->request->get('action')) {
            $booking->setPlannedArrivalTime(null);
            $booking->setPlannedArrivalSubmittedAt(null);
            $this->em->flush();
            $this->addFlash('success', 'Horário de chegada removido.');

            return $this->redirectToRoute('admin_booking_show', ['id' => $booking->getId()]);
        }

        $normalized = Property::normalizeClockTime(trim((string) $request->request->get('time', '')));
        if (null === $normalized) {
            $this->addFlash('error', 'Indique um horário válido (HH:MM).');

            return $this->redirectToRoute('admin_booking_show', ['id' => $booking->getId()]);
        }

        $property = $this->propertyRepository->getOrCreate();
        if (!$property->allowsArrivalAt($normalized)) {
            $this->addFlash('error', sprintf(
                'A chegada não pode ser antes do check-in. A janela é das %s às %s.',
                $property->getCheckInTime(),
                $property->getCheckInTimeEnd(),
            ));

            return $this->redirectToRoute('admin_booking_show', ['id' => $booking->getId()]);
        }

        $booking->setPlannedArrivalTime($normalized);
        $booking->setPlannedArrivalSubmittedAt(new \DateTimeImmutable('now', new \DateTimeZone('America/Sao_Paulo')));
        $this->em->flush();
        $this->bookingCalendarSyncDispatcher->afterBookingSaved($booking);
        $this->addFlash('success', 'Horário de chegada actualizado: '.$normalized.'.');

        return $this->redirectToRoute('admin_booking_show', ['id' => $booking->getId()]);
    }

    #[Route('/{id}/extras', name: 'admin_booking_add_extra', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addExtra(Booking $booking, Request $request): Response
    {
        $this->validateAdminCsrf($request);
        $extraId = (int) $request->request->get('extraId');
        $extra = $extraId > 0 ? $this->extraRepository->find($extraId) : null;

        if ($extra) {
            $be = new BookingExtra();
            $be->setBooking($booking);
            $be->setExtra($extra);
            $be->setQuantity(max(1, (int) $request->request->get('quantity', 1)));
            $be->setRequestedBy(BookingExtra::REQUESTED_BY_HOST);
            $be->setStatus(BookingExtra::STATUS_CONFIRMED);
            $be->setPriceAtBooking($extra->getPrice());
            $notes = trim((string) $request->request->get('notes', ''));
            $be->setNotes('' !== $notes ? $notes : null);
            $this->em->persist($be);
            $this->em->flush();
            $this->addFlash('success', 'Extra adicionado: '.$extra->getNamePt().'.');
        } else {
            $this->addFlash('error', 'Seleccione um extra válido.');
        }

        return $this->redirectToRoute('admin_booking_show', ['id' => $booking->getId()]);
    }

    #[Route('/{id}/extras/{extraId}/status', name: 'admin_booking_extra_status', methods: ['POST'], requirements: ['id' => '\d+', 'extraId' => '\d+'])]
    public function updateExtraStatus(Booking $booking, int $extraId, Request $request): Response
    {
        $this->validateAdminCsrf($request);
        $be = $this->bookingExtraRepository->find($extraId);
        if ($be && $be->getBooking()?->getId() === $booking->getId()) {
            $be->setStatus((string) $request->request->get('status', BookingExtra::STATUS_CONFIRMED));
            $this->em->flush();
            $this->addFlash('success', 'Status atualizado.');
        }

        return $this->redirectToRoute('admin_booking_show', ['id' => $booking->getId()]);
    }

    #[Route('/{id}/extras/{extraId}/delete', name: 'admin_booking_extra_delete', methods: ['POST'], requirements: ['id' => '\d+', 'extraId' => '\d+'])]
    public function deleteExtra(Booking $booking, int $extraId, Request $request): Response
    {
        $this->validateAdminCsrf($request);
        $be = $this->bookingExtraRepository->find($extraId);
        if ($be && $be->getBooking()?->getId() === $booking->getId()) {
            $this->em->remove($be);
            $this->em->flush();
            $this->addFlash('success', 'Extra removido.');
        }

        return $this->redirectToRoute('admin_booking_show', ['id' => $booking->getId()]);
    }

    #[Route('/{id}/extras/toggle', name: 'admin_booking_toggle_extra', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleExtraAvailability(Booking $booking, Request $request): Response
    {
        $this->validateAdminCsrf($request);
        $extraId = (int) $request->request->get('extraId');
        $extra = $extraId > 0 ? $this->extraRepository->find($extraId) : null;

        if (!$extra) {
            $this->addFlash('error', 'Extra inválido.');

            return $this->redirectToRoute('admin_booking_show', ['id' => $booking->getId()]);
        }

        $existing = $this->bookingDisabledExtraRepository->findOneForBookingAndExtra($booking, $extra);
        if ($existing) {
            $this->em->remove($existing);
            $this->em->flush();
            $this->addFlash('success', 'Extra activado para o hóspede: '.$extra->getNamePt().'.');
        } else {
            $disabled = new BookingDisabledExtra();
            $disabled->setBooking($booking);
            $disabled->setExtra($extra);
            $this->em->persist($disabled);
            $this->em->flush();
            $this->addFlash('success', 'Extra desactivado para o hóspede: '.$extra->getNamePt().'.');
        }

        return $this->redirectToRoute('admin_booking_show', ['id' => $booking->getId()]);
    }

    #[Route('/{id}/extras/custom', name: 'admin_booking_add_custom_extra', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addCustomExtra(Booking $booking, Request $request): Response
    {
        $this->validateAdminCsrf($request);
        $namePt = trim((string) $request->request->get('customNamePt', ''));
        $nameEn = trim((string) $request->request->get('customNameEn', ''));
        $price = (float) $request->request->get('price', 0);
        $quantity = max(1, (int) $request->request->get('quantity', 1));
        $notes = trim((string) $request->request->get('notes', ''));

        if ('' === $namePt && '' === $nameEn) {
            $this->addFlash('error', 'Indique um nome para o extra personalizado.');

            return $this->redirectToRoute('admin_booking_show', ['id' => $booking->getId()]);
        }

        $be = new BookingExtra();
        $be->setBooking($booking);
        $be->setCustomNamePt('' !== $namePt ? $namePt : $nameEn);
        $be->setCustomNameEn('' !== $nameEn ? $nameEn : $namePt);
        $be->setQuantity($quantity);
        $be->setRequestedBy(BookingExtra::REQUESTED_BY_HOST);
        $be->setStatus(BookingExtra::STATUS_CONFIRMED);
        $be->setPriceAtBooking($price);
        $be->setNotes('' !== $notes ? $notes : null);
        $this->em->persist($be);
        $this->em->flush();
        $this->addFlash('success', 'Extra personalizado adicionado: '.$be->getDisplayName().'.');

        return $this->redirectToRoute('admin_booking_show', ['id' => $booking->getId()]);
    }

    private function flashAfterBookingSaved(Booking $booking, string $successMessage): void
    {
        if ($booking->rajaaramCalendarConflictsOf('busy')) {
            $this->addFlash('error', 'Este horário parece ocupado no calendário Rajaaram. A reserva foi guardada. Pode escolher outro horário ou criar o evento mesmo assim.');
        }

        if ($booking->rajaaramCalendarConflictsOf('drift')) {
            $this->addFlash('error', 'A reserva foi guardada. O calendário Rajaaram tem conteúdo diferente — use o aviso para actualizar a reserva ou o calendário.');
        }

        if (!$booking->getGoogleCalendarTherapyConflicts()) {
            $this->addFlash('success', $successMessage);
        }
    }

    private function lockDatesIfManuallyChanged(Booking $booking): void
    {
        $original = $this->em->getUnitOfWork()->getOriginalEntityData($booking);
        if (!$original) {
            return;
        }

        $origCheckIn = $original['checkIn'] ?? null;
        $origCheckOut = $original['checkOut'] ?? null;

        if (!$origCheckIn instanceof \DateTimeImmutable || !$origCheckOut instanceof \DateTimeImmutable) {
            return;
        }

        if ($booking->getCheckIn()->format('Y-m-d') !== $origCheckIn->format('Y-m-d')
            || $booking->getCheckOut()->format('Y-m-d') !== $origCheckOut->format('Y-m-d')) {
            $booking->setManualDates(true);
        }
    }
}
