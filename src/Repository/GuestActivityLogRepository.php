<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\GuestActivityLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GuestActivityLog>
 */
class GuestActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GuestActivityLog::class);
    }

    /**
     * @return GuestActivityLog[]
     */
    public function findByBooking(Booking $booking, int $limit = 50): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.booking = :booking')
            ->setParameter('booking', $booking)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function hasDuplicateSince(string $fingerprint, \DateTimeImmutable $since): bool
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.fingerprint = :fingerprint')
            ->andWhere('a.createdAt > :since')
            ->setParameter('fingerprint', $fingerprint)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
