<?php

namespace App\Repository;

use App\Entity\AdminPushSubscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<AdminPushSubscription> */
class AdminPushSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdminPushSubscription::class);
    }

    public function findOneByEndpoint(string $endpoint): ?AdminPushSubscription
    {
        return $this->findOneBy(['endpoint' => $endpoint]);
    }

    public function deleteByEndpoint(string $endpoint): void
    {
        $subscription = $this->findOneByEndpoint($endpoint);
        if (!$subscription) {
            return;
        }

        $this->getEntityManager()->remove($subscription);
        $this->getEntityManager()->flush();
    }
}
