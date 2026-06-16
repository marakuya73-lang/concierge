<?php

namespace App\Repository;

use App\Entity\KitchenPhoto;
use App\Entity\Property;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<KitchenPhoto> */
class KitchenPhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KitchenPhoto::class);
    }

    /** @return KitchenPhoto[] */
    public function findByPropertyOrdered(Property $property): array
    {
        return $this->createQueryBuilder('k')
            ->andWhere('k.property = :property')
            ->setParameter('property', $property)
            ->orderBy('k.sortOrder', 'ASC')
            ->addOrderBy('k.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return KitchenPhoto[] */
    public function findActiveByPropertyOrdered(Property $property): array
    {
        return $this->createQueryBuilder('k')
            ->andWhere('k.property = :property')
            ->andWhere('k.active = true')
            ->setParameter('property', $property)
            ->orderBy('k.sortOrder', 'ASC')
            ->addOrderBy('k.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getNextSortOrder(Property $property): int
    {
        $max = $this->createQueryBuilder('k')
            ->select('MAX(k.sortOrder)')
            ->andWhere('k.property = :property')
            ->setParameter('property', $property)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $max + 1;
    }
}
