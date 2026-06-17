<?php

namespace App\Controller\Admin;

use App\Entity\BookingExtra;
use App\Repository\BookingExtraRepository;
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

    #[Route('/test', name: 'admin_api_notifications_test', methods: ['POST'])]
    public function test(): JsonResponse
    {
        if (!$this->webPushService->isConfigured()) {
            return $this->json(['error' => 'VAPID keys not configured in .env'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        if (0 === $this->webPushService->getSubscriptionCount()) {
            return $this->json(['error' => 'No push subscription registered. Tap "Ativar notificações" first.'], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->webPushService->send(
            'Teste — Domo Xangô',
            'Notificações estão a funcionar.',
            '/admin',
            'extra-request-test',
        );

        return $this->json([
            'ok' => $result['sent'] > 0,
            'sent' => $result['sent'],
            'failed' => $result['failed'],
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
        ]);
    }
}
