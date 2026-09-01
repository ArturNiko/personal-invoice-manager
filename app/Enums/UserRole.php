<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case DEVELOPER = 'developer';
    case USER = 'user';

    public static function getAll(): array {
        return [
            self::ADMIN->value,
            self::DEVELOPER->value,
            self::USER->value,
        ];
    }

    public static function isValid(string $value): bool {
        return in_array($value, self::getAll());
    }

    public static function fromString(string $value): ?self {
        return match ($value) {
            'admin' => self::ADMIN,
            'developer' => self::DEVELOPER,
            'user' => self::USER,
            default => null,
        };
    }
}
