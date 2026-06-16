<?php

namespace App\Repository;

use App\Entity\GuideSpot;
use App\Entity\Property;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<GuideSpot> */
class GuideSpotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GuideSpot::class);
    }

    /** @return GuideSpot[] */
    public function findByPropertyOrdered(Property $property): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.property = :property')
            ->setParameter('property', $property)
            ->orderBy('g.sortOrder', 'ASC')
            ->addOrderBy('g.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return GuideSpot[] */
    public function findActiveByPropertyOrdered(Property $property): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.property = :property')
            ->andWhere('g.active = true')
            ->setParameter('property', $property)
            ->orderBy('g.sortOrder', 'ASC')
            ->addOrderBy('g.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getNextSortOrder(Property $property): int
    {
        $max = $this->createQueryBuilder('g')
            ->select('MAX(g.sortOrder)')
            ->andWhere('g.property = :property')
            ->setParameter('property', $property)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $max + 1;
    }
}
