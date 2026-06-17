<?php

namespace App\Service;

use App\Entity\AdminPushSubscription;
use App\Repository\AdminPushSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    public function __construct(
        private AdminPushSubscriptionRepository $subscriptionRepository,
        private EntityManagerInterface $entityManager,
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

    public function send(string $title, string $body, string $url, string $tag): void
    {
        if (!$this->isConfigured()) {
            return;
        }

        /** @var AdminPushSubscription[] $subscriptions */
        $subscriptions = $this->subscriptionRepository->findAll();
        if ([] === $subscriptions) {
            return;
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

        foreach ($webPush->flush() as $report) {
            if (!$report->isSuccess() && $report->isSubscriptionExpired()) {
                $this->subscriptionRepository->deleteByEndpoint($report->getEndpoint());
            }
        }
    }
}
