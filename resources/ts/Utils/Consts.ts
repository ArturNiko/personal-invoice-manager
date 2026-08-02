import { InvoiceStatuses, InvoiceTypes, InvoiceRecurrence } from "@/Types/Invoice";
import { Currency } from "@/Types/Currency";

export const invoiceStatusOptions = [
    { label: "Pending", value: InvoiceStatuses.PENDING },
    { label: "Paid", value: InvoiceStatuses.PAID },
    { label: "Overdue", value: InvoiceStatuses.OVERDUE },
];

export const invoiceTypeOptions = [
    { label: "One-time", value: InvoiceTypes.ONE_TIME },
    { label: "Recurring", value: InvoiceTypes.RECURRING },
];

export const invoiceRecurrenceOptions = [
    { label: "None", value: InvoiceRecurrence.NONE },
    { label: "Weekly", value: InvoiceRecurrence.WEEKLY },
    { label: "Biweekly", value: InvoiceRecurrence.BIWEEKLY },
    { label: "Monthly", value: InvoiceRecurrence.MONTHLY },
    { label: "Quarterly", value: InvoiceRecurrence.QUARTERLY },
    { label: "Semiannual", value: InvoiceRecurrence.SEMIANNUAL },
    { label: "Yearly", value: InvoiceRecurrence.YEARLY },
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