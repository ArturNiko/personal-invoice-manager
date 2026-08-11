import { Currency } from '@/Types/Currency';
import { getCurrencySymbol } from '@/Utils/Currency';



export const formatMoney = (amount: number, currency: Currency): string => {
    const symbol = getCurrencySymbol(currency);
    return `${amount.toFixed(2)} ${symbol}`;
}

export const normalizeDateValue = (value?: string) => {
    if (!value) return '';

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return '';

    return date.toISOString().slice(0, 10);
};
