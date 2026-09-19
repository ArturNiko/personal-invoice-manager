import { t } from '@/i18n';

import {
    InvoiceStatuses,
    InvoiceTypes,
    InvoiceRecurrence,
} from '@/Types/Invoice';
import { Currency } from '@/Types/Currency';

const getRecurringOccurrenceCount = (
    recurrence: InvoiceRecurrence,
    startDate?: string,
    endDate?: string,
) => {
    if (!startDate || !endDate) {
        return 0;
    }

    const start = new Date(`${startDate}T00:00:00`);
    const end = new Date(`${endDate}T00:00:00`);

    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || start > end) {
        return 0;
    }

    const monthsBetween =
        (end.getFullYear() - start.getFullYear()) * 12 +
        (end.getMonth() - start.getMonth());

    switch (recurrence) {
        case InvoiceRecurrence.WEEKLY:
            return Math.floor((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24 * 7)) + 1;
        case InvoiceRecurrence.BIWEEKLY:
            return Math.floor((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24 * 14)) + 1;
        case InvoiceRecurrence.MONTHLY:
            return Math.max(monthsBetween + 1, 1);
        case InvoiceRecurrence.QUARTERLY:
            return Math.max(Math.floor(monthsBetween / 3) + 1, 1);
        case InvoiceRecurrence.SEMIANNUAL:
            return Math.max(Math.floor(monthsBetween / 6) + 1, 1);
        case InvoiceRecurrence.YEARLY:
            return Math.max(end.getFullYear() - start.getFullYear() + 1, 1);
        default:
            return 1;
    }
};

export const getRecurringLabel = (
    recurrence: InvoiceRecurrence,
    translate: (key: string) => string,
) => {
    switch (recurrence) {
        case InvoiceRecurrence.WEEKLY:
            return translate('invoices.weekly');
        case InvoiceRecurrence.BIWEEKLY:
            return translate('invoices.biweekly');
        case InvoiceRecurrence.MONTHLY:
            return translate('invoices.monthly');
        case InvoiceRecurrence.QUARTERLY:
            return translate('invoices.quarterly');
        case InvoiceRecurrence.SEMIANNUAL:
            return translate('invoices.semiannual');
        case InvoiceRecurrence.YEARLY:
            return translate('invoices.yearly');
        default:
            return translate('invoices.none');
    }
};

export const getRecurringPreviewLabel = (
    recurrence: InvoiceRecurrence,
    translate: (key: string) => string,
    startDate?: string,
    endDate?: string,
) => {
    const recurrenceText = getRecurringLabel(recurrence, translate);

    if (!startDate && !endDate) {
        return `${translate('invoices.previewSchedule')}: ${recurrenceText} (${translate('invoices.recurringScheduleUnset')})`;
    }

    if (startDate && !endDate) {
        return `${translate('invoices.previewSchedule')}: ${recurrenceText} (${translate('invoices.recurringScheduleEndless')})`;
    }

    const occurrenceCount = getRecurringOccurrenceCount(recurrence, startDate, endDate);
    const countLabel = occurrenceCount === 1 ? 'time' : 'times';

    return `${translate('invoices.previewSchedule')}: ${recurrenceText} (${occurrenceCount} ${countLabel})`;
};

export const getInvoiceTypeOptions = (translate: (key: string) => string) => [
    { label: translate('invoices.oneTime'), value: InvoiceTypes.ONE_TIME },
    { label: translate('invoices.recurring'), value: InvoiceTypes.RECURRING },
];

export const getInvoiceStatusOptions = (translate: (key: string) => string) => [
    { label: translate('invoices.pending'), value: InvoiceStatuses.PENDING },
    { label: translate('invoices.paid'), value: InvoiceStatuses.PAID },
    { label: translate('invoices.overdue'), value: InvoiceStatuses.OVERDUE },
];

export const getInvoiceRecurrenceOptions = (translate: (key: string) => string) => [
    { label: translate('invoices.none'), value: InvoiceRecurrence.NONE },
    { label: translate('invoices.weekly'), value: InvoiceRecurrence.WEEKLY },
    { label: translate('invoices.biweekly'), value: InvoiceRecurrence.BIWEEKLY },
    { label: translate('invoices.monthly'), value: InvoiceRecurrence.MONTHLY },
    { label: translate('invoices.quarterly'), value: InvoiceRecurrence.QUARTERLY },
    { label: translate('invoices.semiannual'), value: InvoiceRecurrence.SEMIANNUAL },
    { label: translate('invoices.yearly'), value: InvoiceRecurrence.YEARLY },
];

export const currencyOptions = [
    { label: Currency.USD, value: Currency.USD },
    { label: Currency.EUR, value: Currency.EUR },
    { label: Currency.GBP, value: Currency.GBP },
    { label: Currency.JPY, value: Currency.JPY },
    { label: Currency.AUD, value: Currency.AUD },
    { label: Currency.CAD, value: Currency.CAD },
    { label: Currency.CHF, value: Currency.CHF },
    { label: Currency.CNY, value: Currency.CNY },
    { label: Currency.SEK, value: Currency.SEK },
    { label: Currency.NZD, value: Currency.NZD },
    { label: Currency.RUB, value: Currency.RUB },
    { label: Currency.AMD, value: Currency.AMD },
];

export const getStatusLabel = (status: InvoiceStatuses) => {
    switch (status) {
        case InvoiceStatuses.PAID:
            return t('invoices.paid');
        case InvoiceStatuses.OVERDUE:
            return t('invoices.overdue');
        default:
            return t('invoices.pending');
    }
};