import { Currency } from "@/Types/Currency";

export const getCurrencySymbol = (currency: Currency): string => {
    switch (currency) {
        case Currency.USD:
            return '$';
        case Currency.EUR:
            return '€';
        case Currency.GBP:
            return '£';
        case Currency.JPY:
            return '¥';
        case Currency.AUD:
            return 'A$';
        case Currency.CAD:
            return 'C$';
        case Currency.CHF:
            return 'Fr.';
        case Currency.CNY:
            return '¥';
        case Currency.SEK:
            return 'kr';
        case Currency.NZD:
            return 'NZ$';
        case Currency.RUB:
            return '₽';
        case Currency.AMD:
            return '֏';
        default:
            return currency;
    }
};