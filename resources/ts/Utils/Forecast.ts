import {
    InvoiceRecurrence,
    InvoiceStatuses,
    InvoiceTypes,
    type InvoiceEvent,
} from '@/Types/Invoice';
import { Currency } from '@/Types/Currency';
import { getCurrencySymbol } from '@/Utils/Currency';
import { convertAmount } from '@/Utils/ExchangeRates';

export interface MonthlyForecast {
    key: string;
    label: string;
    recurringTotal: number;
    oneTimeTotal: number;
    total: number;
}

export interface UpcomingItem {
    id: number;
    title: string;
    dateKey: string;
    amount: number;
    currency: Currency;
    type: InvoiceTypes;
    recurrence: string;
}

export interface DashboardStats {
    dueThisMonth: number;
    overdueTotal: number;
    recurringCommitment: number;
    currency: Currency;
}

const toDateOnly = (value: string) => {
    const datePart = value.split('T')[0].split(' ')[0];
    const [year, month, day] = datePart.split('-').map(Number);

    return new Date(year, month - 1, day);
};

const toDateKey = (date: Date) => {
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${date.getFullYear()}-${month}-${day}`;
};

const toMonthKey = (date: Date) => {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
};

const addMonthsClamped = (date: Date, months: number) => {
    const nextDate = new Date(date);
    const dayOfMonth = nextDate.getDate();

    nextDate.setDate(1);
    nextDate.setMonth(nextDate.getMonth() + months);

    const lastDayOfMonth = new Date(
        nextDate.getFullYear(),
        nextDate.getMonth() + 1,
        0,
    ).getDate();

    nextDate.setDate(Math.min(dayOfMonth, lastDayOfMonth));

    return nextDate;
};

const advanceRecurrenceDate = (date: Date, recurrence: string): Date | null => {
    const nextDate = new Date(date);

    switch (recurrence) {
        case InvoiceRecurrence.WEEKLY:
            nextDate.setDate(nextDate.getDate() + 7);
            return nextDate;
        case InvoiceRecurrence.BIWEEKLY:
            nextDate.setDate(nextDate.getDate() + 14);
            return nextDate;
        case InvoiceRecurrence.MONTHLY:
            return addMonthsClamped(nextDate, 1);
        case InvoiceRecurrence.QUARTERLY:
            return addMonthsClamped(nextDate, 3);
        case InvoiceRecurrence.SEMIANNUAL:
            return addMonthsClamped(nextDate, 6);
        case InvoiceRecurrence.YEARLY:
            return addMonthsClamped(nextDate, 12);
        default:
            return null;
    }
};

const startOfDay = (date: Date) => {
    return new Date(date.getFullYear(), date.getMonth(), date.getDate());
};

export const buildMonthlyForecast = (
    invoices: InvoiceEvent[],
    months: number,
    appCurrency: Currency = Currency.EUR,
): MonthlyForecast[] => {
    const now = new Date();
    const windowStart = new Date(now.getFullYear(), now.getMonth(), 1);
    const count = Math.max(1, Math.min(12, Math.round(months)));
    const buckets: MonthlyForecast[] = [];
    const byKey = new Map<string, MonthlyForecast>();

    for (let i = 0; i < count; i++) {
        const monthStart = new Date(
            windowStart.getFullYear(),
            windowStart.getMonth() + i,
            1,
        );
        const key = toMonthKey(monthStart);
        const bucket: MonthlyForecast = {
            key,
            label: monthStart.toLocaleString('en-US', { month: 'short' }),
            recurringTotal: 0,
            oneTimeTotal: 0,
            total: 0,
        };

        buckets.push(bucket);
        byKey.set(key, bucket);
    }

    const windowEnd = new Date(
        windowStart.getFullYear(),
        windowStart.getMonth() + count,
        1,
    );

    for (const invoice of invoices) {
        if (invoice.status === InvoiceStatuses.PAID) continue;

        const price = convertAmount(
            Number(invoice.price || 0),
            invoice.currency,
            appCurrency,
        );
        if (!price) continue;

        if (invoice.type === InvoiceTypes.ONE_TIME) {
            const bucket = byKey.get(toMonthKey(toDateOnly(invoice.start_date)));

            if (bucket) bucket.oneTimeTotal += price;

            continue;
        }

        const startDate = toDateOnly(invoice.start_date);
        const rawEnd = invoice.end_date ? toDateOnly(invoice.end_date) : null;
        let current = new Date(startDate);
        let guard = 0;

        while (current < windowEnd && guard++ < 5000) {
            if (current >= windowStart) {
                const bucket = byKey.get(toMonthKey(current));

                if (bucket) bucket.recurringTotal += price;
            }

            const next = advanceRecurrenceDate(
                current,
                invoice.recurrence ?? InvoiceRecurrence.MONTHLY,
            );

            if (!next) break;
            if (rawEnd && next > rawEnd) break;

            current = next;
        }
    }

    for (const bucket of buckets) {
        bucket.total = bucket.recurringTotal + bucket.oneTimeTotal;
    }

    return buckets;
};

export const computeDashboardStats = (
    invoices: InvoiceEvent[],
    appCurrency: Currency = Currency.EUR,
): DashboardStats => {
    const now = new Date();
    const monthStart = new Date(now.getFullYear(), now.getMonth(), 1);
    const nextMonth = new Date(now.getFullYear(), now.getMonth() + 1, 1);

    let dueThisMonth = 0;
    let overdueTotal = 0;
    let recurringCommitment = 0;

    for (const invoice of invoices) {
        if (invoice.status === InvoiceStatuses.PAID) continue;

        const price = convertAmount(
            Number(invoice.price || 0),
            invoice.currency,
            appCurrency,
        );
        if (!price) continue;

        if (invoice.status === InvoiceStatuses.OVERDUE) {
            overdueTotal += price;
        }

        if (invoice.type === InvoiceTypes.ONE_TIME) {
            const start = toDateOnly(invoice.start_date);

            if (start >= monthStart && start < nextMonth) {
                dueThisMonth += price;
            }

            continue;
        }

        let current = new Date(toDateOnly(invoice.start_date));
        const rawEnd = invoice.end_date ? toDateOnly(invoice.end_date) : null;
        let guard = 0;

        while (current < nextMonth && guard++ < 5000) {
            if (current >= monthStart) {
                dueThisMonth += price;
                recurringCommitment += price;
                break;
            }

            const next = advanceRecurrenceDate(
                current,
                invoice.recurrence ?? InvoiceRecurrence.MONTHLY,
            );

            if (!next) break;
            if (rawEnd && next > rawEnd) break;

            current = next;
        }
    }

    return {
        dueThisMonth,
        overdueTotal,
        recurringCommitment,
        currency: appCurrency,
    };
};

export const buildUpcomingItems = (
    invoices: InvoiceEvent[],
    limit = 6,
    appCurrency: Currency = Currency.EUR,
): UpcomingItem[] => {
    const today = startOfDay(new Date());
    const items: UpcomingItem[] = [];

    for (const invoice of invoices) {
        if (invoice.status === InvoiceStatuses.PAID) continue;

        const amount = convertAmount(
            Number(invoice.price || 0),
            invoice.currency,
            appCurrency,
        );
        if (!amount) continue;

        if (invoice.type === InvoiceTypes.ONE_TIME) {
            const date = toDateOnly(invoice.start_date);

            if (date < today) continue;

            items.push({
                id: invoice.id,
                title: invoice.title,
                dateKey: toDateKey(date),
                amount,
                currency: appCurrency,
                type: invoice.type,
                recurrence: '',
            });

            continue;
        }

        let current = new Date(toDateOnly(invoice.start_date));
        const rawEnd = invoice.end_date ? toDateOnly(invoice.end_date) : null;
        let guard = 0;

        while (guard++ < 5000) {
            if (current >= today) {
                items.push({
                    id: invoice.id,
                    title: invoice.title,
                    dateKey: toDateKey(current),
                    amount,
                    currency: appCurrency,
                    type: invoice.type,
                    recurrence: invoice.recurrence ?? '',
                });

                break;
            }

            const next = advanceRecurrenceDate(
                current,
                invoice.recurrence ?? InvoiceRecurrence.MONTHLY,
            );

            if (!next) break;
            if (rawEnd && next > rawEnd) break;

            current = next;
        }
    }

    items.sort((a, b) => a.dateKey.localeCompare(b.dateKey));

    return items.slice(0, limit);
};

export const formatAmountWithSymbol = (
    amount: number,
    currency: Currency | null,
    maximumFractionDigits = 0,
) => {
    if (currency === null) {
        return amount.toLocaleString('en-US', { maximumFractionDigits });
    }

    return `${getCurrencySymbol(currency)}${amount.toLocaleString('en-US', {
        maximumFractionDigits,
    })}`;
};