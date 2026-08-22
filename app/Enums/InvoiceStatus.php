<?php

namespace App\Enums;


enum InvoiceStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case PROCESSING = 'processing';

    public static function getAll(): array {
        return [
            self::PROCESSING->value,
            self::PENDING->value,
            self::PAID->value,
            self::OVERDUE->value,
        ];
    }

    public static function isValid(string $value): bool {
        return in_array($value, self::getAll());
    }

    public static function fromString(string $value): ?self {
        return match ($value) {
            'pending' => self::PENDING,
            'paid' => self::PAID,
            'overdue' => self::OVERDUE,
            'processing' => self::PROCESSING,
            default => null,
        };
    }
}
