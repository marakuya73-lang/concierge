<?php

namespace App\Repository;

use App\Entity\HouseRule;
use App\Entity\Property;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<HouseRule> */
class HouseRuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HouseRule::class);
    }

    /** @return HouseRule[] */
    public function findByPropertyOrdered(Property $property): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.property = :property')
            ->setParameter('property', $property)
            ->orderBy('r.sortOrder', 'ASC')
            ->addOrderBy('r.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return HouseRule[] */
    public function findActiveByPropertyOrdered(Property $property): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.property = :property')
            ->andWhere('r.active = true')
            ->setParameter('property', $property)
            ->orderBy('r.sortOrder', 'ASC')
            ->addOrderBy('r.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
