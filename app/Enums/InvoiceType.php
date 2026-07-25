<?php

namespace App\Enums;


enum InvoiceType: string 
{
    case ONE_TIME = 'one-time';
    case RECURRING = 'recurring';

    public static function getAll(): array {
        return [
            self::ONE_TIME->value,
            self::RECURRING->value,
        ];
    }

    public static function isValid(string $value): bool {
        return in_array($value, self::getAll());
    }
}