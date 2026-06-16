<?php

namespace App\Validator\Constraints;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class BookingDatesValidValidator extends ConstraintValidator
{
    public function __construct(
        private BookingRepository $bookingRepository,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof BookingDatesValid) {
            throw new UnexpectedTypeException($constraint, BookingDatesValid::class);
        }

        if (!$value instanceof Booking) {
            return;
        }

        if (Booking::STATUS_CANCELLED === $value->getStatus()) {
            return;
        }

        if ($value->getCheckOut() <= $value->getCheckIn()) {
            $this->context->buildViolation($constraint->invalidRangeMessage)
                ->atPath('checkOut')
                ->addViolation();

            return;
        }

        $conflict = $this->bookingRepository->findConflicting(
            $value->getCheckIn(),
            $value->getCheckOut(),
            $value->getId(),
        );

        if ($conflict) {
            $this->context->buildViolation($constraint->overlapMessage)
                ->setParameter('{{ guest }}', $conflict->getGuestName())
                ->setParameter('{{ checkIn }}', $conflict->getCheckIn()->format('d/m/Y'))
                ->setParameter('{{ checkOut }}', $conflict->getCheckOut()->format('d/m/Y'))
                ->atPath('checkOut')
                ->addViolation();
        }
    }
}
