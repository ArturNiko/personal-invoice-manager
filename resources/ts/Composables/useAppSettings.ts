import axios from 'axios';
import { computed, ref } from 'vue';

import { Currency } from '@/Types/Currency';
import { getCurrencySymbol } from '@/Utils/Currency';
import {
    convertAmount,
    DEFAULT_CURRENCY,
    setEurBaseRates,
} from '@/Utils/ExchangeRates';

export const appCurrency = ref<Currency>(DEFAULT_CURRENCY);
export const appSettingsLoading = ref(false);

let loadPromise: Promise<void> | null = null;

export const appCurrencySymbol = computed(() =>
    getCurrencySymbol(appCurrency.value),
);

interface ExchangeRatesResponse {
    base?: string;
    rates?: Partial<Record<Currency, number>>;
    updated_at?: string | null;
}

export const loadAppSettings = () => {
    if (loadPromise) return loadPromise;

    loadPromise = (async () => {
        appSettingsLoading.value = true;

        try {
            const [profileResponse, ratesResponse] = await Promise.allSettled([
                axios.get<{ user?: { currency?: string } }>('/profile'),
                axios.get<ExchangeRatesResponse>('/exchange-rates'),
            ]);

            if (profileResponse.status === 'fulfilled') {
                const value = profileResponse.value.data?.user?.currency;

                if (
                    value &&
                    (Object.values(Currency) as string[]).includes(value)
                ) {
                    appCurrency.value = value as Currency;
                }
            }

            if (ratesResponse.status === 'fulfilled') {
                const rates = ratesResponse.value.data?.rates;

                if (rates && typeof rates === 'object') {
                    setEurBaseRates(rates);
                }
            }
        } finally {
            appSettingsLoading.value = false;
        }
    })();

    return loadPromise;
};

export const convertToAppCurrency = (
    amount: number,
    fromCurrency: Currency,
) => convertAmount(amount, fromCurrency, appCurrency.value);

export const formatAppCurrencyAmount = (
    amount: number,
    fromCurrency: Currency | null,
    maximumFractionDigits = 0,
) => {
    if (fromCurrency === null) {
        return amount.toLocaleString('en-US', { maximumFractionDigits });
    }

    const converted = convertToAppCurrency(amount, fromCurrency);

    return `${appCurrencySymbol.value}${converted.toLocaleString('en-US', {
        maximumFractionDigits,
    })}`;
};

export const updateAppCurrency = (currency: string): void => {
    if ((Object.values(Currency) as string[]).includes(currency)) {
        appCurrency.value = currency as Currency;
    }
};

export const useAppSettings = () => ({
    appCurrency,
    appCurrencySymbol,
    appSettingsLoading,
    loadAppSettings,
    updateAppCurrency,
    convertToAppCurrency,
    formatAppCurrencyAmount,
});