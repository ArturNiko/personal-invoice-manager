export const getCurrencySymbol = (currency: string): string => {
    switch (currency) {
        case 'USD':
            return '$';
        case 'EUR':
            return '€';
        case 'GBP':
            return '£';
        case 'JPY':
            return '¥';
        case 'AUD':
            return 'A$';
        case 'CAD':
            return 'C$';
        case 'CHF':
            return 'Fr.';
        case 'CNY':
            return '¥';
        case 'SEK':
            return 'kr';
        case 'NZD':
            return 'NZ$';
        case 'RUB':
            return '₽';
        case 'AMD':
            return '֏';
        default:
            return currency;
    }
}