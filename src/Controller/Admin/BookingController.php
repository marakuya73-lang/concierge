<?php

namespace App\Controller\Admin;

use App\Entity\Booking;
use App\Entity\BookingExtra;
use App\Entity\Extra;
use App\Form\BookingType;
use App\Repository\BookingExtraRepository;
use App\Repository\BookingRepository;
use App\Repository\ExtraRepository;
use App\Repository\PropertyRepository;
use App\Service\AccessCodeGenerator;
use App\Service\BookingLifecycleService;
use App\Service\BookingWhatsAppService;
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
        private AccessCodeGenerator $accessCodeGenerator,
        private BookingLifecycleService $bookingLifecycleService,
        private BookingWhatsAppService $bookingWhatsAppService,
        private PropertyRepository $propertyRepository,
        private EntityManagerInterface $em,
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

            $this->addFlash('success', 'Reserva criada. Código de acesso: '.$booking->getAccessCode());

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
            $this->addFlash('success', 'Reserva atualizada.');

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
            'guestWhatsappWelcomeUrl' => $this->bookingWhatsAppService->getWelcomeUrl($booking),
        ]);
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
        $this->em->remove($booking);
        $this->em->flush();
        $this->addFlash('success', 'Reserva removida.');

        return $this->redirectToRoute('admin_bookings');
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
