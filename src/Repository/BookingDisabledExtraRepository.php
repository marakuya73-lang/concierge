<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\BookingDisabledExtra;
use App\Entity\Extra;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<BookingDisabledExtra> */
class BookingDisabledExtraRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BookingDisabledExtra::class);
    }

    /** @return int[] */
    public function findDisabledExtraIds(Booking $booking): array
    {
        $rows = $this->createQueryBuilder('bde')
            ->select('IDENTITY(bde.extra) AS extraId')
            ->andWhere('bde.booking = :booking')
            ->setParameter('booking', $booking)
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn (array $row): int => (int) $row['extraId'], $rows);
    }

    public function findOneForBookingAndExtra(Booking $booking, Extra $extra): ?BookingDisabledExtra
    {
        return $this->createQueryBuilder('bde')
            ->andWhere('bde.booking = :booking')
            ->andWhere('bde.extra = :extra')
            ->setParameter('booking', $booking)
            ->setParameter('extra', $extra)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
