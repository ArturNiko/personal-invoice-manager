import { getCurrencySymbol } from "./Currency";


export const formatMoney = (amount: number, currency: string): string => {
    const symbol = getCurrencySymbol(currency);
    return `${amount.toFixed(2)}${symbol}`;
}