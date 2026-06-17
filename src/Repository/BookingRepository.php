<?php

namespace App\Repository;

use App\Entity\Booking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Booking> */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function findByAccessCode(string $code): ?Booking
    {
        return $this->findOneBy(['accessCode' => strtoupper($code)]);
    }

    public function findByExternalUid(string $uid): ?Booking
    {
        return $this->findOneBy(['externalUid' => $uid]);
    }

    /** @return Booking[] */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('b')
            ->orderBy('b.checkIn', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findCurrent(\DateTimeImmutable $today): ?Booking
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.checkIn <= :today')
            ->andWhere('b.checkOut > :today')
            ->andWhere('b.status = :status')
            ->setParameter('today', $today)
            ->setParameter('status', Booking::STATUS_CONFIRMED)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return Booking[] */
    public function findUpcoming(\DateTimeImmutable $today): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.checkIn > :today')
            ->andWhere('b.status = :status')
            ->setParameter('today', $today)
            ->setParameter('status', Booking::STATUS_CONFIRMED)
            ->orderBy('b.checkIn', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return Booking[] */
    public function findPast(\DateTimeImmutable $today): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.checkOut <= :today')
            ->setParameter('today', $today)
            ->orderBy('b.checkIn', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return Booking[] */
    public function findCurrentStays(\DateTimeImmutable $today): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.checkIn <= :today')
            ->andWhere('b.checkOut > :today')
            ->andWhere('b.status = :status')
            ->setParameter('today', $today)
            ->setParameter('status', Booking::STATUS_CONFIRMED)
            ->orderBy('b.checkOut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return Booking[] */
    public function findConfirmedPast(\DateTimeImmutable $today): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.checkOut <= :today')
            ->andWhere('b.status = :status')
            ->setParameter('today', $today)
            ->setParameter('status', Booking::STATUS_CONFIRMED)
            ->getQuery()
            ->getResult();
    }

    /** @return Booking[] */
    public function findImportedFromAirbnb(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.source = :source')
            ->andWhere('b.externalUid IS NOT NULL')
            ->setParameter('source', Booking::SOURCE_AIRBNB)
            ->getQuery()
            ->getResult();
    }

    /** @return Booking[] */
    public function findIcalSynced(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.externalUid IS NOT NULL')
            ->getQuery()
            ->getResult();
    }

    /** @return Booking[] */
    public function findPendingSiteBookings(\DateTimeImmutable $today): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.source = :source')
            ->andWhere('b.status = :status')
            ->andWhere('b.checkOut > :today')
            ->andWhere('b.externalUid IS NOT NULL')
            ->andWhere('b.guestName = :pendingName')
            ->setParameter('source', Booking::SOURCE_SITE)
            ->setParameter('status', Booking::STATUS_CONFIRMED)
            ->setParameter('today', $today)
            ->setParameter('pendingName', Booking::GUEST_NAME_PENDING)
            ->orderBy('b.checkIn', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @deprecated use findPendingSiteBookings() */
    public function findPendingDirectFromAirbnb(\DateTimeImmutable $today): array
    {
        return $this->findPendingSiteBookings($today);
    }

    public function accessCodeExists(string $code): bool
    {
        return null !== $this->findOneBy(['accessCode' => strtoupper($code)]);
    }

    public function findConflicting(
        \DateTimeImmutable $checkIn,
        \DateTimeImmutable $checkOut,
        ?int $excludeId = null,
    ): ?Booking {
        $qb = $this->createQueryBuilder('b')
            ->andWhere('b.status = :status')
            ->andWhere('b.checkIn < :checkOut')
            ->andWhere('b.checkOut > :checkIn')
            ->setParameter('status', Booking::STATUS_CONFIRMED)
            ->setParameter('checkIn', $checkIn)
            ->setParameter('checkOut', $checkOut)
            ->setMaxResults(1);

        if (null !== $excludeId) {
            $qb->andWhere('b.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /** @return Booking[] */
    public function findSelfCheckInRequestsSince(\DateTimeImmutable $since): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.selfCheckInRequested = :requested')
            ->andWhere('b.selfCheckInRequestedAt >= :since')
            ->setParameter('requested', true)
            ->setParameter('since', $since)
            ->orderBy('b.selfCheckInRequestedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return Booking[] */
    public function findNeedingUpcomingReminder(\DateTimeImmutable $checkInDate): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.status = :status')
            ->andWhere('b.upcomingReminderSentAt IS NULL')
            ->andWhere('b.checkIn = :checkInDate')
            ->setParameter('status', Booking::STATUS_CONFIRMED)
            ->setParameter('checkInDate', $checkInDate)
            ->orderBy('b.checkIn', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
