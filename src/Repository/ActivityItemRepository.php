<?php

namespace App\Repository;

use App\Entity\ActivityItem;
use App\Entity\Property;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ActivityItem> */
class ActivityItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityItem::class);
    }

    /** @return ActivityItem[] */
    public function findByPropertyOrdered(Property $property): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.property = :property')
            ->setParameter('property', $property)
            ->orderBy('a.sortOrder', 'ASC')
            ->addOrderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return ActivityItem[] */
    public function findActiveByPropertyOrdered(Property $property): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.property = :property')
            ->andWhere('a.active = true')
            ->setParameter('property', $property)
            ->orderBy('a.sortOrder', 'ASC')
            ->addOrderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
