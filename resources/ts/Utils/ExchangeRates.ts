import { ref } from 'vue';

import { Currency } from '@/Types/Currency';

export const DEFAULT_CURRENCY = Currency.EUR;

/**
 * Approximate EUR-based rates used as a fallback while live rates load (or
 * whenever the remote provider is unreachable).
 */
const FALLBACK_EUR_BASE_RATES: Record<Currency, number> = {
    [Currency.USD]: 1.08,
    [Currency.EUR]: 1,
    [Currency.GBP]: 0.84,
    [Currency.JPY]: 165,
    [Currency.AUD]: 1.64,
    [Currency.CAD]: 1.47,
    [Currency.CHF]: 0.94,
    [Currency.CNY]: 7.7,
    [Currency.SEK]: 11.3,
    [Currency.NZD]: 1.77,
    [Currency.RUB]: 100,
    [Currency.AMD]: 430,
};

export const eurBaseRates = ref<Record<Currency, number>>({
    ...FALLBACK_EUR_BASE_RATES,
});

export const setEurBaseRates = (
    rates: Partial<Record<Currency, number>>,
) => {
    const normalized: Record<Currency, number> = {
        ...FALLBACK_EUR_BASE_RATES,
    };

    for (const [key, value] of Object.entries(rates)) {
        const currency = key as Currency;

        if (
            (Object.values(Currency) as string[]).includes(currency) &&
            typeof value === 'number' &&
            Number.isFinite(value)
        ) {
            normalized[currency] = value;
        }
    }

    eurBaseRates.value = normalized;
};

export const convertAmount = (
    amount: number,
    fromCurrency: Currency,
    toCurrency: Currency,
): number => {
    if (!amount) return 0;
    if (fromCurrency === toCurrency) return amount;

    const fromRate = eurBaseRates.value[fromCurrency];
    const toRate = eurBaseRates.value[toCurrency];

    if (!fromRate || !toRate) return amount;

    return (amount / fromRate) * toRate;
};

export const getAllCurrencies = (): Currency[] => {
    return Object.values(Currency);
};