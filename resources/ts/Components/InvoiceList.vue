<script setup lang="ts">
import { computed } from 'vue'
import { getCurrencySymbol } from '@/Utils/Currency'

import type { InvoiceEvent } from '@/Types/Invoice'

const props = defineProps<{ invoices: InvoiceEvent[] }>()

const sortedInvoices = computed(() => {
    return [...props.invoices].sort((a, b) => {
        return new Date(a.start_date).getTime() - new Date(b.start_date).getTime()
    })
})

const formatDate = (dateValue?: string) => {
    if (!dateValue) {
        return 'No due date'
    }

    const parsedDate = new Date(dateValue)

    if (Number.isNaN(parsedDate.getTime())) {
        return dateValue
    }

    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(parsedDate)
}

const formatAmount = (invoice: InvoiceEvent) => {
    const amount = invoice.recurrence ? invoice.price_occurrence : invoice.price_total
    const symbol = getCurrencySymbol(invoice.currency)

    return `${symbol}${amount}`
}

const getTypeClasses = (type: string) => {
    return type === 'recurring'
        ? 'border-teal-400/35 bg-teal-500/10 text-teal-100'
        : 'border-sky-400/35 bg-sky-500/10 text-sky-100'
}

const getTypeLabel = (type: string) => {
    return type === 'recurring' ? 'Recurring' : 'One-time'
}
</script>

<template>
    <section class="flex h-full min-h-[36rem] flex-col overflow-hidden rounded-[2rem] border border-white/10 bg-white/5 shadow-2xl shadow-slate-950/40 backdrop-blur-xl">
        <div class="flex items-center justify-between border-b border-white/10 px-5 py-4 sm:px-6">
            <div>
                <h2 class="text-lg font-semibold text-white">Invoice list</h2>
                <p class="text-sm text-slate-400">Event-backed invoice entries synced from your API feed.</p>
            </div>
            <span class="rounded-full border border-sky-400/30 bg-sky-400/10 px-3 py-1 text-xs font-medium text-sky-200">{{ sortedInvoices.length }} items</span>
        </div>

        <div class="flex-1 overflow-auto p-4 sm:p-6">
            <div v-if="sortedInvoices.length" class="grid gap-4 lg:grid-cols-[1.3fr_0.7fr]">
                <div class="space-y-3">
                    <article
                        v-for="invoice in sortedInvoices"
                        :key="invoice.id"
                        class="rounded-2xl border border-white/10 bg-slate-950/40 p-4 transition hover:border-cyan-400/40 hover:bg-slate-950/60"
                    >
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold text-white">{{ invoice.title }}</p>
                                <p class="text-sm text-slate-400">ID #{{ invoice.id }}</p>
                            </div>
                            <div class="text-left sm:text-right">
                                <p class="text-lg font-semibold text-white">{{ formatAmount(invoice) }}</p>
                                <p class="text-sm text-slate-400">{{ formatDate(invoice.start_date) }}</p>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-between gap-2 text-xs uppercase tracking-[0.2em]">
                            <span
                                class="rounded-full border px-2.5 py-1 font-medium"
                                :class="getTypeClasses(invoice.type)"
                            >
                                {{ getTypeLabel(invoice.type) }}
                            </span>
                            <span class="text-slate-400">
                                {{ invoice.recurrence ? invoice.recurrence : 'Single charge' }}
                            </span>
                        </div>
                    </article>
                </div>

                <aside class="rounded-2xl border border-dashed border-white/10 bg-slate-950/30 p-5">
                    <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Summary</p>
                    <h3 class="mt-2 text-xl font-semibold text-white">Invoice insights</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-400">
                        This panel now reflects live event rows and can be expanded with search, filters, and quick actions.
                    </p>
                    <div class="mt-6 space-y-3 text-sm text-slate-300">
                        <div class="rounded-xl border border-white/10 bg-white/5 p-3">Total invoices: {{ sortedInvoices.length }}</div>
                        <div class="rounded-xl border border-white/10 bg-white/5 p-3">Recurring: {{ sortedInvoices.filter((invoice) => invoice.type === 'recurring').length }}</div>
                        <div class="rounded-xl border border-white/10 bg-white/5 p-3">One-time: {{ sortedInvoices.filter((invoice) => invoice.type === 'one-time').length }}</div>
                    </div>
                </aside>
            </div>

            <div v-else class="flex h-full min-h-[20rem] items-center justify-center rounded-2xl border border-dashed border-white/10 bg-slate-950/20 p-6 text-center">
                <div>
                    <p class="text-sm uppercase tracking-[0.25em] text-slate-400">No data yet</p>
                    <h3 class="mt-2 text-xl font-semibold text-white">No invoices available</h3>
                    <p class="mt-2 text-sm text-slate-400">
                        Once invoices are fetched from the API, they will appear here automatically.
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
