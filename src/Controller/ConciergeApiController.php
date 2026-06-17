<?php

namespace App\Controller;

use App\Service\ClientErrorService;
use App\Service\ConciergeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/concierge')]
class ConciergeApiController extends AbstractController
{
    public function __construct(
        private ConciergeService $conciergeService,
        private ClientErrorService $clientErrorService,
    ) {
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
            $this->reportIfUnexpected($e, 'api_concierge_extras', $code, 401);

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
            $status = $status >= 100 ? $status : 400;
            $this->reportIfUnexpected($e, 'api_concierge_request_extra', $code, $status);

            return $this->json(['error' => $e->getMessage()], $status);
        }
    }

    #[Route('/extras/cancel', name: 'api_concierge_cancel_extra', methods: ['POST'])]
    public function cancelExtra(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $code = strtoupper(trim((string) ($data['code'] ?? '')));
        $requestId = (int) ($data['requestId'] ?? 0);

        if (!$code || !$requestId) {
            return $this->json(['error' => 'code and requestId are required'], 400);
        }

        try {
            $result = $this->conciergeService->cancelExtraRequest($code, $requestId, $request->getLocale());

            return $this->json($result);
        } catch (\Throwable $e) {
            $status = match (true) {
                str_contains($e->getMessage(), 'confirmada') || str_contains($e->getMessage(), 'confirmed') => 403,
                str_contains($e->getMessage(), 'não encontrada') || str_contains($e->getMessage(), 'not found') => 404,
                default => $e->getCode() ?: 400,
            };
            $status = $status >= 100 ? $status : 400;
            $this->reportIfUnexpected($e, 'api_concierge_cancel_extra', $code, $status);

            return $this->json(['error' => $e->getMessage()], $status);
        }
    }

    #[Route('/self-checkin', name: 'api_concierge_self_checkin', methods: ['POST'])]
    public function selfCheckIn(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $code = strtoupper(trim((string) ($data['code'] ?? '')));

        if (!$code) {
            return $this->json(['error' => 'code is required'], 400);
        }

        try {
            $result = $this->conciergeService->requestSelfCheckIn($code, $request->getLocale());

            return $this->json($result, 201);
        } catch (\Throwable $e) {
            $status = str_contains($e->getMessage(), 'já foi') || str_contains($e->getMessage(), 'already')
                || str_contains($e->getMessage(), '9h') || str_contains($e->getMessage(), '9:00')
                ? 403
                : ($e->getCode() ?: 400);
            $status = $status >= 100 ? $status : 400;
            $this->reportIfUnexpected($e, 'api_concierge_self_checkin', $code, $status);

            return $this->json(['error' => $e->getMessage()], $status);
        }
    }

    private function reportIfUnexpected(\Throwable $exception, string $route, ?string $code, int $status): void
    {
        if ($exception instanceof HttpExceptionInterface && $status < 500) {
            return;
        }

        $this->clientErrorService->reportUnexpected($exception, $route, $code, $status);
    }
}
