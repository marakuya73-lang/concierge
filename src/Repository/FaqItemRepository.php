<?php

namespace App\Repository;

use App\Entity\FaqItem;
use App\Entity\Property;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<FaqItem> */
class FaqItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FaqItem::class);
    }

    /** @return FaqItem[] */
    public function findByPropertyOrdered(Property $property): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.property = :property')
            ->setParameter('property', $property)
            ->orderBy('f.sortOrder', 'ASC')
            ->addOrderBy('f.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return FaqItem[] */
    public function findActiveByPropertyOrdered(Property $property): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.property = :property')
            ->andWhere('f.active = true')
            ->setParameter('property', $property)
            ->orderBy('f.sortOrder', 'ASC')
            ->addOrderBy('f.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
