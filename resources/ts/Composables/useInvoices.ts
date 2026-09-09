import axios from 'axios';
import { computed, ref } from 'vue';

import type { InvoiceIndexResponse, InvoiceEvent } from '@/Types/Invoice';
import { getCurrencySymbol } from '@/Utils/Currency';
import { convertAmount } from '@/Utils/ExchangeRates';
import { appCurrency } from '@/Composables/useAppSettings';

export type InvoiceQueryParams = Record<string, string | undefined>;

const invoices = ref<InvoiceEvent[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);
const invoiceCount = computed(() => invoices.value.length);
const totalAmount = computed(() =>
    invoices.value.reduce(
        (sum, invoice) =>
            sum +
            convertAmount(
                Number(invoice.price || 0),
                invoice.currency,
                appCurrency.value,
            ),
        0,
    ),
);
const totalCurrency = computed(() => appCurrency.value);
const totalAmountDisplay = computed(() => {
    if (!invoices.value.length) {
        return '0';
    }

    return `${getCurrencySymbol(totalCurrency.value)}${totalAmount.value.toLocaleString(
        'en-US',
        {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        },
    )}`;
});

export const buildInvoiceQuery = (
    query: Record<string, unknown>,
): InvoiceQueryParams => ({
    q: typeof query.q === 'string' ? query.q : undefined,
    type: typeof query.type === 'string' ? query.type : undefined,
    sort: typeof query.sort === 'string' ? query.sort : undefined,
    direction:
        typeof query.direction === 'string' ? query.direction : undefined,
    status: typeof query.status === 'string' ? query.status : undefined,
    recurrence:
        typeof query.recurrence === 'string' ? query.recurrence : undefined,
    per_page: typeof query.per_page === 'string' ? query.per_page : undefined,
});

export const useInvoices = () => {
    const fetchInvoices = async (params: InvoiceQueryParams = {}) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get<InvoiceIndexResponse>(
                '/invoices',
                {
                    params,
                },
            );

            if (response.status !== 200) {
                error.value =
                    response.statusText || 'Failed to fetch invoices.';
                invoices.value = [];
                return;
            }

            invoices.value = Array.isArray(response.data?.data)
                ? response.data.data
                : [];
        } catch (requestError) {
            error.value = 'Error fetching invoices.';
            invoices.value = [];
            console.error('Error fetching invoices:', requestError);

            if (
                axios.isAxiosError(requestError) &&
                requestError.response?.status === 401
            ) {
                window.location.href = '/login';
            }
        } finally {
            loading.value = false;
        }
    };

    return {
        invoices,
        invoiceCount,
        totalAmount,
        totalAmountDisplay,
        loading,
        error,
        fetchInvoices,
    };
};
