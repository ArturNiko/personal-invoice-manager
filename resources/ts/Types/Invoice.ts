export interface InvoiceEvent {
    id: number
    title: string
    description?: string
    status: 'pending' | 'paid' | 'overdue'
    price_occurrence: number
    price_total: number
    currency: string
    type: string
    recurrence?: string
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