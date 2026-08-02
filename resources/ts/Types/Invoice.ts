import { Currency } from "@/Types/Currency";

export interface InvoiceEvent {
    id: number
    title: string
    description?: string
    status: InvoiceStatuses
    price: number
    currency: Currency
    type: InvoiceTypes
    recurrence?: InvoiceRecurrence
    start_date: string
    end_date?: string
}

export interface InvoiceIndexResponse {
    current_page: number
    data: InvoiceEvent[]
    first_page_url: string
    from: number
    last_page: number
    last_page_url: string
    links: {
        url: string | null
        label: string
        active: boolean
    }[]
    next_page_url: string | null
    path: string
    per_page: number
    prev_page_url: string | null
    to: number
    total: number
}

export enum InvoiceStatuses {
    PENDING = "pending",
    PAID = "paid",
    OVERDUE = "overdue",
}

export enum InvoiceTypes {
    ONE_TIME = "one-time",
    RECURRING = "recurring",
}

export enum InvoiceRecurrence {
    NONE = "",
    WEEKLY = "weekly",
    BIWEEKLY = "biweekly",
    MONTHLY = "monthly",
    QUARTERLY = "quarterly",
    SEMIANNUAL = "semiannual",
    YEARLY = "yearly",
}