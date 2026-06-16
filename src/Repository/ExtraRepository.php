<?php

namespace App\Repository;

use App\Entity\Extra;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Extra> */
class ExtraRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Extra::class);
    }

    /** @return Extra[] */
    public function findActive(): array
    {
        return $this->findBy(['active' => true], ['namePt' => 'ASC']);
    }

    /** @return Extra[] */
    public function findActiveForGuestCount(int $guests): array
    {
        return array_values(array_filter(
            $this->findActive(),
            static fn (Extra $extra): bool => $extra->isAvailableForGuestCount($guests),
        ));
    }

    /** @param string[] $categories @return Extra[] */
    public function findActiveByCategories(array $categories): array
    {
        if ([] === $categories) {
            return [];
        }

        return $this->createQueryBuilder('e')
            ->where('e.active = :active')
            ->andWhere('e.category IN (:categories)')
            ->setParameter('active', true)
            ->setParameter('categories', $categories)
            ->orderBy('e.namePt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return int[] */
    public function findActiveIdsByCategories(array $categories): array
    {
        return array_map(
            static fn (Extra $extra): int => (int) $extra->getId(),
            $this->findActiveByCategories($categories),
        );
    }
}
