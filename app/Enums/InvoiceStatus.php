<?php

namespace App\Enums;


enum InvoiceStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case OVERDUE = 'overdue';

    public static function getAll(): array {
        return [
            self::PENDING->value,
            self::PAID->value,
            self::OVERDUE->value,
        ];
    }

    public static function isValid(string $value): bool {
        return in_array($value, self::getAll());
    }
}
