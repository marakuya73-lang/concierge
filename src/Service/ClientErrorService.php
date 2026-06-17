<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\GuestClientError;
use App\Repository\BookingRepository;
use App\Repository\GuestClientErrorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ClientErrorService
{
    private const DEDUP_MINUTES = 15;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private GuestClientErrorRepository $errorRepository,
        private BookingRepository $bookingRepository,
        private ClientErrorNotificationService $notificationService,
        private LoggerInterface $logger,
    ) {
    }

    public function reportClient(
        string $message,
        string $route,
        ?string $accessCode = null,
        ?int $httpStatus = null,
        array $context = [],
    ): ?GuestClientError {
        return $this->record(
            message: $message,
            route: $route,
            source: GuestClientError::SOURCE_CLIENT,
            accessCode: $accessCode,
            httpStatus: $httpStatus,
            context: $context,
        );
    }

    public function reportUnexpected(
        \Throwable $exception,
        string $route,
        ?string $accessCode = null,
        ?int $httpStatus = null,
        array $context = [],
    ): ?GuestClientError {
        if ($this->isExpectedGuestError($exception, $httpStatus)) {
            return null;
        }

        $message = trim($exception->getMessage()) ?: $exception::class;
        $context['exception'] = $exception::class;

        return $this->record(
            message: $message,
            route: $route,
            source: GuestClientError::SOURCE_SERVER,
            accessCode: $accessCode,
            httpStatus: $httpStatus,
            context: $context,
        );
    }

    public function reportFromRequest(\Throwable $exception, Request $request, ?string $accessCode = null): ?GuestClientError
    {
        $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

        return $this->reportUnexpected(
            $exception,
            $request->attributes->get('_route') ?? $request->getPathInfo(),
            $accessCode,
            $status,
            [
                'path' => $request->getPathInfo(),
                'method' => $request->getMethod(),
            ],
        );
    }

    private function record(
        string $message,
        string $route,
        string $source,
        ?string $accessCode = null,
        ?int $httpStatus = null,
        array $context = [],
    ): ?GuestClientError {
        $message = $this->truncate($message, 500);
        $route = $this->truncate($route, 120);
        $accessCode = $accessCode ? strtoupper(trim($accessCode)) : null;
        if ($accessCode === '') {
            $accessCode = null;
        }

        $fingerprint = hash('sha256', $route.'|'.$message.'|'.$source);
        $since = new \DateTimeImmutable(sprintf('-%d minutes', self::DEDUP_MINUTES));

        if ($this->errorRepository->hasDuplicateSince($fingerprint, $since)) {
            return null;
        }

        $error = (new GuestClientError())
            ->setMessage($message)
            ->setRoute($route)
            ->setSource($source)
            ->setAccessCode($accessCode)
            ->setHttpStatus($httpStatus)
            ->setFingerprint($fingerprint)
            ->setContext($context !== [] ? json_encode($context, JSON_UNESCAPED_UNICODE) : null);

        if ($accessCode) {
            $error->setBooking($this->bookingRepository->findOneBy(['accessCode' => $accessCode]));
        }

        $this->entityManager->persist($error);
        $this->entityManager->flush();

        $this->logger->error('Guest client error reported', [
            'id' => $error->getId(),
            'route' => $route,
            'source' => $source,
            'message' => $message,
            'accessCode' => $accessCode,
            'httpStatus' => $httpStatus,
        ]);

        $this->notificationService->notifyAdmin($error);

        return $error;
    }

    private function isExpectedGuestError(\Throwable $exception, ?int $httpStatus): bool
    {
        return $exception instanceof HttpExceptionInterface && $exception->getStatusCode() < 500;
    }

    private function truncate(string $value, int $maxLength): string
    {
        if (strlen($value) <= $maxLength) {
            return $value;
        }

        return substr($value, 0, $maxLength - 1).'…';
    }
}
