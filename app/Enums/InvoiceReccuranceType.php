<?php

namespace App\Enums;


enum InvoiceReccuranceType: string
{
    case WEEKLY = 'weekly';
    case BIWEEKLY = 'biweekly';
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case SEMIANNUAL = 'semiannual';
    case YEARLY = 'yearly';

    public static function getAll(): array {
        return [
            self::WEEKLY->value,
            self::BIWEEKLY->value,
            self::MONTHLY->value,
            self::QUARTERLY->value,
            self::SEMIANNUAL->value,
            self::YEARLY->value,
        ];
    }

    public static function isValid(string $value): bool {
        return in_array($value, self::getAll());
    }

    public static function fromString(string $value): ?self {
        return match ($value) {
            'weekly' => self::WEEKLY,
            'biweekly' => self::BIWEEKLY,
            'monthly' => self::MONTHLY,
            'quarterly' => self::QUARTERLY,
            'semiannual' => self::SEMIANNUAL,
            'yearly' => self::YEARLY,
            default => null,
        };
    }
}
