<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\BookingExtra;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<BookingExtra> */
class BookingExtraRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BookingExtra::class);
    }

    /** @return BookingExtra[] */
    public function findByBooking(Booking $booking): array
    {
        return $this->createQueryBuilder('be')
            ->andWhere('be.booking = :booking')
            ->setParameter('booking', $booking)
            ->orderBy('be.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function guestAlreadyRequested(Booking $booking, int $extraId): bool
    {
        return (bool) $this->createQueryBuilder('be')
            ->select('COUNT(be.id)')
            ->andWhere('be.booking = :booking')
            ->andWhere('be.extra = :extraId')
            ->setParameter('booking', $booking)
            ->setParameter('extraId', $extraId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return BookingExtra[] */
    public function findGuestRequestsSince(\DateTimeImmutable $since): array
    {
        return $this->createQueryBuilder('be')
            ->andWhere('be.createdAt > :since')
            ->andWhere('be.requestedBy = :guest')
            ->andWhere('be.status = :status')
            ->setParameter('since', $since)
            ->setParameter('guest', BookingExtra::REQUESTED_BY_GUEST)
            ->setParameter('status', BookingExtra::STATUS_REQUESTED)
            ->orderBy('be.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
