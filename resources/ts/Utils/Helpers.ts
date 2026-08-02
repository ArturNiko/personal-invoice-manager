import { getCurrencySymbol } from "./Currency";


export const formatMoney = (amount: number, currency: string): string => {
    const symbol = getCurrencySymbol(currency);
    return `${amount.toFixed(2)}${symbol}`;
}

export const normalizeDateValue = (value?: string) => {
    if (!value) return "";

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return "";

    return date.toISOString().slice(0, 10);
};
