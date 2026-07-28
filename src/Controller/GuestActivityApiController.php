<?php

namespace App\Controller;

use App\Service\ConciergeService;
use App\Service\GuestActivityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/guest-activity')]
class GuestActivityApiController extends AbstractController
{
    public function __construct(
        private ConciergeService $conciergeService,
        private GuestActivityService $guestActivityService,
    ) {
    }

    #[Route('', name: 'api_guest_activity', methods: ['POST'])]
    public function record(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $code = strtoupper(trim((string) ($data['code'] ?? '')));
        $section = trim((string) ($data['section'] ?? ''));
        $label = isset($data['label']) ? trim((string) $data['label']) : null;

        if (!$code || '' === $section) {
            return $this->json(['error' => 'code and section are required'], 400);
        }

        try {
            $booking = $this->conciergeService->getBookingForActivity($code, $request->getLocale());
            $this->guestActivityService->recordPageView($booking, $section, $label ?: null);

            return $this->json(['ok' => true]);
        } catch (AccessDeniedHttpException $e) {
            return $this->json(['error' => $e->getMessage()], 401);
        }
    }
}
