<?php

namespace App\Controller\Admin;

use App\Entity\BookingExtra;
use App\Entity\GuestClientError;
use App\Repository\BookingExtraRepository;
use App\Repository\BookingRepository;
use App\Repository\GuestClientErrorRepository;
use App\Service\WebPushService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/admin/api/notifications')]
class NotificationApiController extends AbstractController
{
    public function __construct(
        private WebPushService $webPushService,
        private BookingExtraRepository $bookingExtraRepository,
        private BookingRepository $bookingRepository,
        private GuestClientErrorRepository $guestClientErrorRepository,
    ) {
    }

    #[Route('/vapid-key', name: 'admin_api_vapid_key', methods: ['GET'])]
    public function vapidKey(): JsonResponse
    {
        return $this->json([
            'configured' => $this->webPushService->isConfigured(),
            'publicKey' => $this->webPushService->getPublicKey(),
        ]);
    }

    #[Route('/subscribe', name: 'admin_api_push_subscribe', methods: ['POST'])]
    public function subscribe(Request $request): JsonResponse
    {
        if (!$this->webPushService->isConfigured()) {
            return $this->json(['error' => 'Push notifications not configured on server.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $endpoint = trim((string) ($data['endpoint'] ?? ''));
        $publicKey = trim((string) ($data['keys']['p256dh'] ?? ''));
        $authToken = trim((string) ($data['keys']['auth'] ?? ''));

        if ('' === $endpoint || '' === $publicKey || '' === $authToken) {
            return $this->json(['error' => 'Invalid subscription payload.'], Response::HTTP_BAD_REQUEST);
        }

        $this->webPushService->saveSubscription(
            $endpoint,
            $publicKey,
            $authToken,
            (string) ($data['contentEncoding'] ?? 'aesgcm'),
        );

        return $this->json(['ok' => true]);
    }

    #[Route('/unsubscribe', name: 'admin_api_push_unsubscribe', methods: ['POST'])]
    public function unsubscribe(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $endpoint = trim((string) ($data['endpoint'] ?? ''));

        if ('' === $endpoint) {
            return $this->json(['error' => 'Endpoint required.'], Response::HTTP_BAD_REQUEST);
        }

        $this->webPushService->removeSubscription($endpoint);

        return $this->json(['ok' => true]);
    }

    #[Route('/status', name: 'admin_api_notifications_status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        return $this->json([
            'pushConfigured' => $this->webPushService->isConfigured(),
            'subscriptionCount' => $this->webPushService->getSubscriptionCount(),
        ]);
    }

    #[Route('/recent', name: 'admin_api_notifications_recent', methods: ['GET'])]
    public function recent(Request $request): JsonResponse
    {
        $since = (int) $request->query->get('since', 0);
        $sinceDate = $since > 0
            ? (new \DateTimeImmutable())->setTimestamp($since)
            : new \DateTimeImmutable('-30 seconds');

        $requests = $this->bookingExtraRepository->findGuestRequestsSince($sinceDate);
        $selfCheckIns = $this->bookingRepository->findSelfCheckInRequestsSince($sinceDate);
        $plannedArrivals = $this->bookingRepository->findPlannedArrivalSubmissionsSince($sinceDate);
        $clientErrors = $this->guestClientErrorRepository->findSince($sinceDate);

        return $this->json([
            'serverTime' => time(),
            'requests' => array_map(function (BookingExtra $bookingExtra) {
                $booking = $bookingExtra->getBooking();
                $extra = $bookingExtra->getExtra();
                $total = ($bookingExtra->getPriceAtBooking() ?? 0) * $bookingExtra->getQuantity();

                return [
                    'id' => $bookingExtra->getId(),
                    'guestName' => $booking?->getGuestName() ?? '',
                    'extraName' => $extra?->getNamePt() ?? '',
                    'quantity' => $bookingExtra->getQuantity(),
                    'totalFormatted' => 'R$ '.number_format($total, 2, ',', '.'),
                    'createdAt' => $bookingExtra->getCreatedAt()->getTimestamp(),
                    'bookingUrl' => $this->generateUrl(
                        'admin_booking_show',
                        ['id' => $booking?->getId()],
                        UrlGeneratorInterface::ABSOLUTE_PATH,
                    ),
                ];
            }, $requests),
            'selfCheckIns' => array_map(function ($booking) {
                return [
                    'bookingId' => $booking->getId(),
                    'guestName' => $booking->getGuestName(),
                    'checkIn' => $booking->getCheckIn()->format('d/m/Y'),
                    'createdAt' => $booking->getSelfCheckInRequestedAt()?->getTimestamp() ?? time(),
                    'bookingUrl' => $this->generateUrl(
                        'admin_booking_show',
                        ['id' => $booking->getId()],
                        UrlGeneratorInterface::ABSOLUTE_PATH,
                    ),
                ];
            }, $selfCheckIns),
            'plannedArrivals' => array_map(function ($booking) {
                $submittedAt = $booking->getPlannedArrivalSubmittedAt()?->getTimestamp() ?? time();

                return [
                    'id' => $booking->getId().'-'.$submittedAt,
                    'bookingId' => $booking->getId(),
                    'guestName' => $booking->getGuestName(),
                    'plannedArrivalTime' => $booking->getPlannedArrivalTime(),
                    'checkIn' => $booking->getCheckIn()->format('d/m/Y'),
                    'createdAt' => $submittedAt,
                    'bookingUrl' => $this->generateUrl(
                        'admin_booking_show',
                        ['id' => $booking->getId()],
                        UrlGeneratorInterface::ABSOLUTE_PATH,
                    ),
                ];
            }, $plannedArrivals),
            'clientErrors' => array_map(function (GuestClientError $error) {
                $booking = $error->getBooking();

                return [
                    'id' => $error->getId(),
                    'message' => $error->getMessage(),
                    'route' => $error->getRoute(),
                    'source' => $error->getSource(),
                    'accessCode' => $error->getAccessCode(),
                    'guestName' => $booking?->getGuestName(),
                    'httpStatus' => $error->getHttpStatus(),
                    'createdAt' => $error->getCreatedAt()->getTimestamp(),
                    'bookingUrl' => $booking
                        ? $this->generateUrl('admin_booking_show', ['id' => $booking->getId()], UrlGeneratorInterface::ABSOLUTE_PATH)
                        : $this->generateUrl('admin_dashboard', [], UrlGeneratorInterface::ABSOLUTE_PATH),
                ];
            }, $clientErrors),
        ]);
    }
}
