<?php

namespace App\Repository;

use App\Entity\Property;
use App\Entity\PropertyPhoto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<PropertyPhoto> */
class PropertyPhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyPhoto::class);
    }

    /** @return PropertyPhoto[] */
    public function findByPropertyOrdered(Property $property): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.property = :property')
            ->setParameter('property', $property)
            ->orderBy('p.sortOrder', 'ASC')
            ->addOrderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getNextSortOrder(Property $property): int
    {
        $max = $this->createQueryBuilder('p')
            ->select('MAX(p.sortOrder)')
            ->andWhere('p.property = :property')
            ->setParameter('property', $property)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $max + 1;
    }
}
