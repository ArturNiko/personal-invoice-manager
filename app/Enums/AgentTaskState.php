<?php

namespace App\Enums;

enum AgentTaskState: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public static function getAll(): array {
        return [
            self::PENDING->value,
            self::PROCESSING->value,
            self::COMPLETED->value,
            self::FAILED->value,
        ];
    }

    public static function isValid(string $value): bool {
        return in_array($value, self::getAll());
    }

    public static function fromString(string $value): ?self {
        return match ($value) {
            'pending' => self::PENDING,
            'processing' => self::PROCESSING,
            'completed' => self::COMPLETED,
            'failed' => self::FAILED,
            default => null,
        };
    }
}