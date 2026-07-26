<script setup lang="ts">
import { computed } from 'vue'
import { getCurrencySymbol } from '@/Utils/Currency'
import { useRoute, useRouter } from 'vue-router'

import type { InvoiceEvent } from '@/Types/Invoice'

const props = defineProps<{ invoices: InvoiceEvent[] }>()

const route = useRoute()
const router = useRouter()

const searchQuery = computed({
    get: () => (typeof route.query.q === 'string' ? route.query.q : ''),
    set: (value: string) => {
        updateQuery({ q: value || undefined })
    },
})

const typeFilter = computed({
    get: () => {
        const value = typeof route.query.type === 'string' ? route.query.type : 'all'
        return value === 'one-time' || value === 'recurring' ? value : 'all'
    },
    set: (value: 'all' | 'one-time' | 'recurring') => {
        updateQuery({ type: value === 'all' ? undefined : value })
    },
})

const sortDirection = computed({
    get: () => (route.query.direction === 'desc' ? 'descending' : 'ascending'),
    set: (value: 'ascending' | 'descending') => {
        updateQuery({
            sort: 'start_date',
            direction: value === 'ascending' ? 'asc' : 'desc',
        })
    },
})

const updateQuery = (updates: Record<string, string | undefined>) => {
    router.replace({
        query: {
            ...route.query,
            ...updates,
        },
    })
}

const visibleInvoices = computed(() => props.invoices)

const filteredRecurringCount = computed(() => {
    return visibleInvoices.value.filter((invoice) => invoice.type === 'recurring').length
})

const filteredOneTimeCount = computed(() => {
    return visibleInvoices.value.filter((invoice) => invoice.type === 'one-time').length
})

const toggleSortDirection = () => {
    sortDirection.value = sortDirection.value === 'ascending' ? 'descending' : 'ascending'
}

const getTypeCountLabel = (count: number) => {
    return count === 1 ? 'item' : 'items'
}

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
            <span class="rounded-full border border-sky-400/30 bg-sky-400/10 px-3 py-1 text-xs font-medium text-sky-200">{{ visibleInvoices.length }} items</span>
        </div>

        <div class="border-b border-white/10 p-4 sm:p-6">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <label class="flex-1">
                    <span class="sr-only">Search invoices</span>
                    <input
                        v-model="searchQuery"
                        type="search"
                        placeholder="Search invoices by title, amount, type, currency..."
                        class="w-full rounded-2xl border border-white/10 bg-slate-950/50 px-4 py-3 text-sm text-white outline-none transition placeholder:text-slate-500 focus:border-cyan-400/50 focus:bg-slate-950/70"
                    />
                </label>

                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        class="rounded-2xl border px-4 py-3 text-sm font-medium transition"
                        :class="typeFilter === 'all' ? 'border-white/20 bg-white text-slate-950 shadow' : 'border-white/10 bg-slate-950/40 text-slate-300 hover:border-cyan-400/40 hover:text-white'"
                        @click="typeFilter = 'all'"
                    >
                        All
                    </button>
                    <button
                        type="button"
                        class="rounded-2xl border px-4 py-3 text-sm font-medium transition"
                        :class="typeFilter === 'one-time' ? 'border-sky-300/50 bg-sky-400/15 text-sky-100 shadow' : 'border-white/10 bg-slate-950/40 text-slate-300 hover:border-sky-400/40 hover:text-white'"
                        @click="typeFilter = 'one-time'"
                    >
                        One-time
                    </button>
                    <button
                        type="button"
                        class="rounded-2xl border px-4 py-3 text-sm font-medium transition"
                        :class="typeFilter === 'recurring' ? 'border-teal-300/50 bg-teal-400/15 text-teal-100 shadow' : 'border-white/10 bg-slate-950/40 text-slate-300 hover:border-teal-400/40 hover:text-white'"
                        @click="typeFilter = 'recurring'"
                    >
                        Recurring
                    </button>
                    <button
                        type="button"
                        class="rounded-2xl border border-white/10 bg-slate-950/40 px-4 py-3 text-sm font-medium text-slate-300 transition hover:border-cyan-400/40 hover:text-white"
                        @click="toggleSortDirection"
                    >
                        Sort: {{ sortDirection === 'ascending' ? 'Soonest first' : 'Latest first' }}
                    </button>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-auto p-4 sm:p-6">
            <div v-if="visibleInvoices.length" class="grid gap-4 lg:grid-cols-[1.3fr_0.7fr]">
                <div class="space-y-3">
                    <article
                        v-for="invoice in visibleInvoices"
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
                        This panel reflects the active backend search, filter, and sorting state.
                    </p>
                    <div class="mt-6 space-y-3 text-sm text-slate-300">
                        <div class="rounded-xl border border-white/10 bg-white/5 p-3">Visible invoices: {{ visibleInvoices.length }}</div>
                        <div class="rounded-xl border border-white/10 bg-white/5 p-3">Recurring: {{ filteredRecurringCount }} {{ getTypeCountLabel(filteredRecurringCount) }}</div>
                        <div class="rounded-xl border border-white/10 bg-white/5 p-3">One-time: {{ filteredOneTimeCount }} {{ getTypeCountLabel(filteredOneTimeCount) }}</div>
                    </div>
                </aside>
            </div>

            <div v-else class="flex h-full min-h-[20rem] items-center justify-center rounded-2xl border border-dashed border-white/10 bg-slate-950/20 p-6 text-center">
                <div>
                    <p class="text-sm uppercase tracking-[0.25em] text-slate-400">No data yet</p>
                    <h3 class="mt-2 text-xl font-semibold text-white">No invoices match your filters</h3>
                    <p class="mt-2 text-sm text-slate-400">
                        Try clearing the search, switching the type filter, or changing the sort order.
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
