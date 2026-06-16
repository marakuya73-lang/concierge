<?php

namespace App\Repository;

use App\Entity\Property;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Property> */
class PropertyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Property::class);
    }

    public function getOrCreate(): Property
    {
        $property = $this->findOneBy([]);
        if ($property) {
            return $property;
        }

        $property = new Property();
        $this->getEntityManager()->persist($property);
        $this->getEntityManager()->flush();

        return $property;
    }
}
