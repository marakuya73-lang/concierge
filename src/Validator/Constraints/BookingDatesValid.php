<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class BookingDatesValid extends Constraint
{
    public string $invalidRangeMessage = 'O check-out deve ser posterior ao check-in.';
    public string $overlapMessage = 'Estas datas sobrepõem-se à reserva de {{ guest }} ({{ checkIn }} → {{ checkOut }}).';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
