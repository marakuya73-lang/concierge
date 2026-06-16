<?php

namespace App\Service;

use App\Repository\BookingRepository;

class AccessCodeGenerator
{
    private const CHARSET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function __construct(private BookingRepository $bookingRepository)
    {
    }

    public function generateUnique(): string
    {
        for ($attempt = 0; $attempt < 10; ++$attempt) {
            $code = $this->generate();
            if (!$this->bookingRepository->accessCodeExists($code)) {
                return $code;
            }
        }

        throw new \RuntimeException('Unable to generate unique access code.');
    }

    private function generate(): string
    {
        $code = '';
        $length = strlen(self::CHARSET);
        for ($i = 0; $i < 5; ++$i) {
            $code .= self::CHARSET[random_int(0, $length - 1)];
        }

        return $code;
    }
}
