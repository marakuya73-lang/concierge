<?php

namespace App\Service;

use App\Entity\AdminPushSubscription;
use App\Repository\AdminPushSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Psr\Log\LoggerInterface;

class WebPushService
{
    public function __construct(
        private AdminPushSubscriptionRepository $subscriptionRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private ?string $vapidPublicKey,
        private ?string $vapidPrivateKey,
        private ?string $vapidSubject,
    ) {
    }

    public function isConfigured(): bool
    {
        return '' !== trim((string) $this->vapidPublicKey)
            && '' !== trim((string) $this->vapidPrivateKey)
            && '' !== trim((string) $this->vapidSubject);
    }

    public function getPublicKey(): ?string
    {
        return $this->isConfigured() ? $this->vapidPublicKey : null;
    }

    public function getSubscriptionCount(): int
    {
        return count($this->subscriptionRepository->findAll());
    }

    public function saveSubscription(string $endpoint, string $publicKey, string $authToken, string $contentEncoding = 'aesgcm'): void
    {
        $subscription = $this->subscriptionRepository->findOneByEndpoint($endpoint)
            ?? new AdminPushSubscription();

        $subscription
            ->setEndpoint($endpoint)
            ->setPublicKey($publicKey)
            ->setAuthToken($authToken)
            ->setContentEncoding($contentEncoding);

        $this->entityManager->persist($subscription);
        $this->entityManager->flush();
    }

    public function removeSubscription(string $endpoint): void
    {
        $this->subscriptionRepository->deleteByEndpoint($endpoint);
    }

    /** @return array{sent: int, failed: int} */
    public function send(string $title, string $body, string $url, string $tag): array
    {
        if (!$this->isConfigured()) {
            $this->logger->warning('Web push skipped: VAPID keys not configured');

            return ['sent' => 0, 'failed' => 0];
        }

        /** @var AdminPushSubscription[] $subscriptions */
        $subscriptions = $this->subscriptionRepository->findAll();
        if ([] === $subscriptions) {
            $this->logger->info('Web push skipped: no admin subscriptions registered');

            return ['sent' => 0, 'failed' => 0];
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => $this->vapidSubject,
                'publicKey' => $this->vapidPublicKey,
                'privateKey' => $this->vapidPrivateKey,
            ],
        ]);

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'tag' => $tag,
        ], JSON_THROW_ON_ERROR);

        foreach ($subscriptions as $stored) {
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $stored->getEndpoint(),
                    'keys' => [
                        'p256dh' => $stored->getPublicKey(),
                        'auth' => $stored->getAuthToken(),
                    ],
                    'contentEncoding' => $stored->getContentEncoding(),
                ]),
                $payload,
            );
        }

        $sent = 0;
        $failed = 0;

        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                ++$sent;
                continue;
            }

            ++$failed;
            $this->logger->error('Web push delivery failed', [
                'reason' => $report->getReason(),
                'expired' => $report->isSubscriptionExpired(),
                'endpoint' => $report->getEndpoint(),
            ]);

            if ($report->isSubscriptionExpired()) {
                $this->subscriptionRepository->deleteByEndpoint($report->getEndpoint());
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }
}
