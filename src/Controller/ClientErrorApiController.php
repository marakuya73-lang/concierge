<?php

namespace App\Controller;

use App\Service\ClientErrorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/client-error')]
class ClientErrorApiController extends AbstractController
{
    public function __construct(private ClientErrorService $clientErrorService)
    {
    }

    #[Route('', name: 'api_client_error', methods: ['POST'])]
    public function report(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $message = trim((string) ($data['message'] ?? ''));
        $route = trim((string) ($data['route'] ?? ''));
        $code = isset($data['code']) ? strtoupper(trim((string) $data['code'])) : null;
        $httpStatus = isset($data['httpStatus']) ? (int) $data['httpStatus'] : null;
        $context = \is_array($data['context'] ?? null) ? $data['context'] : [];

        if ('' === $message || '' === $route) {
            return $this->json(['error' => 'message and route are required'], Response::HTTP_BAD_REQUEST);
        }

        if (strlen($message) > 500) {
            $message = substr($message, 0, 500);
        }

        if (strlen($route) > 120) {
            $route = substr($route, 0, 120);
        }

        $this->clientErrorService->reportClient($message, $route, $code, $httpStatus, $context);

        return $this->json(['ok' => true], Response::HTTP_ACCEPTED);
    }
}
