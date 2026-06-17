<?php

namespace App\Repository;

use App\Entity\GuestClientError;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GuestClientError>
 */
class GuestClientErrorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GuestClientError::class);
    }

    /**
     * @return GuestClientError[]
     */
    public function findSince(\DateTimeImmutable $since): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.createdAt > :since')
            ->setParameter('since', $since)
            ->orderBy('e.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return GuestClientError[]
     */
    public function findRecent(int $hours = 24, int $limit = 10): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.createdAt >= :since')
            ->setParameter('since', new \DateTimeImmutable(sprintf('-%d hours', $hours)))
            ->orderBy('e.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countRecent(int $hours = 24): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.createdAt >= :since')
            ->setParameter('since', new \DateTimeImmutable(sprintf('-%d hours', $hours)))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function hasDuplicateSince(string $fingerprint, \DateTimeImmutable $since): bool
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.fingerprint = :fingerprint')
            ->andWhere('e.createdAt > :since')
            ->setParameter('fingerprint', $fingerprint)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
