<?php

namespace App\Enums;

enum EmailProvider: string
{
    case GMAIL = 'gmail';
    case OUTLOOK = 'outlook';
    case CUSTOM = 'custom';

    public static function getAll(): array {
        return [
            self::GMAIL->value,
            self::OUTLOOK->value,
            self::CUSTOM->value,
        ];
    }
    
    public static function isValid(string $value): bool {
        return in_array($value, self::getAll());
    }
    
    public static function fromString(string $value): ?self {
        return match ($value) {
            'gmail' => self::GMAIL,
            'outlook' => self::OUTLOOK,
            'custom' => self::CUSTOM,
            default => null,
        };
    }
}
