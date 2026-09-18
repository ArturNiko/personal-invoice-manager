<?php

namespace App\Enums;

enum InvoiceOccurrenceStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case OVERDUE = 'overdue';

    public static function getAll(): array
    {
        return [
            self::PENDING->value,
            self::PAID->value,
            self::OVERDUE->value,
        ];
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::getAll(), true);
    }

    public static function fromString(string $value): ?self
    {
        return match ($value) {
            'pending' => self::PENDING,
            'paid' => self::PAID,
            'overdue' => self::OVERDUE,
            default => null,
        };
    }
}
