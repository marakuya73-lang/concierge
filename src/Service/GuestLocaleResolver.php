<?php

namespace App\Service;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use Symfony\Component\HttpFoundation\Request;

class GuestLocaleResolver
{
    public function __construct(
        private BookingRepository $bookingRepository,
    ) {
    }

    public function resolveFromRequest(Request $request): ?string
    {
        $code = $this->extractAccessCode($request);
        if (!$code) {
            return null;
        }

        $booking = $this->bookingRepository->findOneBy(['accessCode' => $code]);

        return $booking instanceof Booking ? $booking->getGuestLocale() : null;
    }

    private function extractAccessCode(Request $request): ?string
    {
        $routeCode = $request->attributes->get('code');
        if (\is_string($routeCode) && '' !== $routeCode) {
            return strtoupper($routeCode);
        }

        $queryCode = $request->query->get('code');
        if (\is_string($queryCode) && '' !== trim($queryCode)) {
            return strtoupper(trim($queryCode));
        }

        if (!$request->isMethod('POST')) {
            return null;
        }

        $path = $request->getPathInfo();
        if (!str_starts_with($path, '/verify-code') && !str_starts_with($path, '/api/')) {
            return null;
        }

        $content = $request->getContent();
        if ('' === $content) {
            return null;
        }

        $data = json_decode($content, true);
        if (!\is_array($data)) {
            return null;
        }

        $code = $data['code'] ?? null;
        if (!\is_string($code) || '' === trim($code)) {
            return null;
        }

        return strtoupper(trim($code));
    }
}
