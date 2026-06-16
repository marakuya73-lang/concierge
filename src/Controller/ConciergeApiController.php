<?php

namespace App\Controller;

use App\Service\ConciergeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/concierge')]
class ConciergeApiController extends AbstractController
{
    public function __construct(private ConciergeService $conciergeService)
    {
    }

    #[Route('/extras', name: 'api_concierge_extras', methods: ['GET'])]
    public function extras(Request $request): JsonResponse
    {
        $code = strtoupper(trim((string) $request->query->get('code', '')));
        if (!$code) {
            return $this->json(['error' => 'Code required'], 400);
        }

        try {
            return $this->json($this->conciergeService->getExtrasForGuest($code, $request->getLocale()));
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 401);
        }
    }

    #[Route('/extras/request', name: 'api_concierge_request_extra', methods: ['POST'])]
    public function requestExtra(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $code = strtoupper(trim((string) ($data['code'] ?? '')));
        $extraId = (int) ($data['extraId'] ?? 0);
        $quantity = (int) ($data['quantity'] ?? 1);
        $notes = isset($data['notes']) ? (string) $data['notes'] : null;

        if (!$code || !$extraId) {
            return $this->json(['error' => 'code and extraId are required'], 400);
        }

        try {
            $result = $this->conciergeService->requestExtra($code, $extraId, $quantity, $notes, $request->getLocale());

            return $this->json($result, 201);
        } catch (\Throwable $e) {
            $status = str_contains($e->getMessage(), 'já solicitou') ? 403 : ($e->getCode() ?: 400);

            return $this->json(['error' => $e->getMessage()], $status >= 100 ? $status : 400);
        }
    }
}
