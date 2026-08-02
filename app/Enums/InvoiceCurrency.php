<?php

namespace App\Enums;


enum InvoiceCurrency: string
{
    case EUR = 'EUR';
    case USD = 'USD';
    case GBP = 'GBP';
    case JPY = 'JPY';
    case AUD = 'AUD';
    case CAD = 'CAD';
    case CHF = 'CHF';
    case CNY = 'CNY';
    case SEK = 'SEK';
    case NZD = 'NZD';
    case RUB = 'RUB';
    case AMD = 'AMD';

    public static function getAll(): array {
        return [
            self::EUR->value,
            self::USD->value,
            self::GBP->value,
            self::JPY->value,
            self::AUD->value,
            self::CAD->value,
            self::CHF->value,
            self::CNY->value,
            self::SEK->value,
            self::NZD->value,
            self::RUB->value,
            self::AMD->value,
        ];
    }

    public static function isValid(string $value): bool {
        return in_array($value, self::getAll());
    }

    public static function fromString(string $value): ?self {
        return match ($value) {
            'EUR' => self::EUR,
            'USD' => self::USD,
            'GBP' => self::GBP,
            'JPY' => self::JPY,
            'AUD' => self::AUD,
            'CAD' => self::CAD,
            'CHF' => self::CHF,
            'CNY' => self::CNY,
            'SEK' => self::SEK,
            'NZD' => self::NZD,
            'RUB' => self::RUB,
            'AMD' => self::AMD,
            default => null,
        };
    }
}
