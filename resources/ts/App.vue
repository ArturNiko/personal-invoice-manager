<script setup lang="ts">
import { computed, ref } from 'vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'

type ViewMode = 'calendar' | 'invoices'

const viewMode = ref<ViewMode>('calendar')

const calendarOptions = computed(() => ({
    plugins: [dayGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    height: '100%',
    expandRows: true,
    fixedWeekCount: false,
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: '',
    },
    buttonText: {
        today: 'Today',
    },
    events: [
        {
            title: 'Invoice follow-up',
            start: new Date().toISOString().slice(0, 10),
            backgroundColor: '#7c3aed',
            borderColor: '#7c3aed',
        },
        {
            title: 'Payment due',
            start: new Date(Date.now() + 86400000 * 3).toISOString().slice(0, 10),
            backgroundColor: '#2563eb',
            borderColor: '#2563eb',
        },
        {
            title: 'Send reminders',
            start: new Date(Date.now() + 86400000 * 7).toISOString().slice(0, 10),
            backgroundColor: '#0f766e',
            borderColor: '#0f766e',
        },
    ],
}))

const invoicePlaceholders = [
    {
        id: 'INV-1024',
        client: 'Northwind Studio',
        amount: '$2,450',
        due: 'Due in 3 days',
        status: 'Awaiting payment',
    },
    {
        id: 'INV-1025',
        client: 'Atlas Supply Co.',
        amount: '$890',
        due: 'Due next week',
        status: 'Draft',
    },
    {
        id: 'INV-1026',
        client: 'Summit Labs',
        amount: '$1,320',
        due: 'Sent yesterday',
        status: 'Tracking open',
    },
]
</script>

<template>
    <div class="relative min-h-screen overflow-hidden bg-slate-950 text-slate-100">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(99,102,241,0.26),_transparent_35%),radial-gradient(circle_at_top_right,_rgba(16,185,129,0.18),_transparent_32%),linear-gradient(180deg,_rgba(15,23,42,1)_0%,_rgba(2,6,23,1)_100%)]"></div>

        <div class="relative mx-auto flex min-h-screen w-full max-w-7xl flex-col gap-6 px-4 py-5 sm:px-6 lg:px-8 lg:py-8">
            <header class="flex flex-col gap-4 rounded-3xl border border-white/10 bg-white/5 p-5 shadow-2xl shadow-slate-950/40 backdrop-blur-xl lg:flex-row lg:items-center lg:justify-between">
                <div class="space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-cyan-300/80">Personal Invoice Reader</p>
                    <div>
                        <h1 class="text-3xl font-semibold tracking-tight text-white sm:text-4xl">Track invoices from the calendar or the list view</h1>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base">
                            Use the calendar for payment timing and reminders now, then switch to the invoice list once the data layer is ready.
                        </p>
                    </div>
                </div>

                <div class="inline-flex rounded-2xl border border-white/10 bg-slate-900/70 p-1 shadow-lg shadow-slate-950/30">
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-medium transition"
                        :class="viewMode === 'calendar' ? 'bg-white text-slate-950 shadow' : 'text-slate-300 hover:text-white'"
                        @click="viewMode = 'calendar'"
                    >
                        Calendar
                    </button>
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-medium transition"
                        :class="viewMode === 'invoices' ? 'bg-white text-slate-950 shadow' : 'text-slate-300 hover:text-white'"
                        @click="viewMode = 'invoices'"
                    >
                        Invoices
                    </button>
                </div>
            </header>

            <section class="grid gap-4 sm:grid-cols-3">
                <article class="rounded-2xl border border-white/10 bg-slate-900/75 p-4 backdrop-blur">
                    <p class="text-xs uppercase tracking-[0.25em] text-slate-400">View</p>
                    <p class="mt-2 text-2xl font-semibold text-white">{{ viewMode === 'calendar' ? 'Calendar' : 'Invoices' }}</p>
                    <p class="mt-1 text-sm text-slate-400">Toggle between scheduling and list management.</p>
                </article>
                <article class="rounded-2xl border border-white/10 bg-slate-900/75 p-4 backdrop-blur">
                    <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Upcoming</p>
                    <p class="mt-2 text-2xl font-semibold text-white">3 reminders</p>
                    <p class="mt-1 text-sm text-slate-400">Placeholder events are already in the calendar.</p>
                </article>
                <article class="rounded-2xl border border-white/10 bg-slate-900/75 p-4 backdrop-blur">
                    <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Invoices</p>
                    <p class="mt-2 text-2xl font-semibold text-white">Coming soon</p>
                    <p class="mt-1 text-sm text-slate-400">The list view is ready for real invoice data later.</p>
                </article>
            </section>

            <main class="min-h-0 flex-1">
                <Transition name="fade" mode="out-in">
                    <section
                        v-if="viewMode === 'calendar'"
                        key="calendar"
                        class="flex h-full min-h-[36rem] flex-col overflow-hidden rounded-[2rem] border border-white/10 bg-white/5 shadow-2xl shadow-slate-950/40 backdrop-blur-xl"
                    >
                        <div class="flex items-center justify-between border-b border-white/10 px-5 py-4 sm:px-6">
                            <div>
                                <h2 class="text-lg font-semibold text-white">FullCalendar</h2>
                                <p class="text-sm text-slate-400">Monthly planning view with invoice-related reminders.</p>
                            </div>
                            <span class="rounded-full border border-emerald-400/30 bg-emerald-400/10 px-3 py-1 text-xs font-medium text-emerald-200">Full size</span>
                        </div>

                        <div class="calendar-shell flex-1 min-h-0 p-3 sm:p-5">
                            <FullCalendar :options="calendarOptions" class="invoice-calendar h-full" />
                        </div>
                    </section>

                    <section
                        v-else
                        key="invoices"
                        class="flex h-full min-h-[36rem] flex-col overflow-hidden rounded-[2rem] border border-white/10 bg-white/5 shadow-2xl shadow-slate-950/40 backdrop-blur-xl"
                    >
                        <div class="flex items-center justify-between border-b border-white/10 px-5 py-4 sm:px-6">
                            <div>
                                <h2 class="text-lg font-semibold text-white">Invoice list</h2>
                                <p class="text-sm text-slate-400">A placeholder structure for the future invoice feed.</p>
                            </div>
                            <span class="rounded-full border border-sky-400/30 bg-sky-400/10 px-3 py-1 text-xs font-medium text-sky-200">Placeholder</span>
                        </div>

                        <div class="flex-1 overflow-auto p-4 sm:p-6">
                            <div class="grid gap-4 lg:grid-cols-[1.3fr_0.7fr]">
                                <div class="space-y-3">
                                    <article
                                        v-for="invoice in invoicePlaceholders"
                                        :key="invoice.id"
                                        class="rounded-2xl border border-white/10 bg-slate-950/40 p-4 transition hover:border-cyan-400/40 hover:bg-slate-950/60"
                                    >
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <p class="text-sm font-semibold text-white">{{ invoice.id }}</p>
                                                <p class="text-sm text-slate-400">{{ invoice.client }}</p>
                                            </div>
                                            <div class="text-left sm:text-right">
                                                <p class="text-lg font-semibold text-white">{{ invoice.amount }}</p>
                                                <p class="text-sm text-slate-400">{{ invoice.due }}</p>
                                            </div>
                                        </div>
                                        <div class="mt-4 flex items-center justify-between text-xs uppercase tracking-[0.2em] text-slate-400">
                                            <span>{{ invoice.status }}</span>
                                            <span class="text-cyan-300">Ready for data</span>
                                        </div>
                                    </article>
                                </div>

                                <aside class="rounded-2xl border border-dashed border-white/10 bg-slate-950/30 p-5">
                                    <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Future panel</p>
                                    <h3 class="mt-2 text-xl font-semibold text-white">Invoice details</h3>
                                    <p class="mt-2 text-sm leading-6 text-slate-400">
                                        This area can later hold filters, search, invoice preview, or synced actions without changing the landing shell.
                                    </p>
                                    <div class="mt-6 space-y-3 text-sm text-slate-300">
                                        <div class="rounded-xl border border-white/10 bg-white/5 p-3">Client metadata</div>
                                        <div class="rounded-xl border border-white/10 bg-white/5 p-3">Payment status</div>
                                        <div class="rounded-xl border border-white/10 bg-white/5 p-3">Invoice actions</div>
                                    </div>
                                </aside>
                            </div>
                        </div>
                    </section>
                </Transition>
            </main>
        </div>
    </div>
</template>

<style scoped>
.invoice-calendar :deep(.fc) {
    height: 100%;
    color: rgb(226 232 240);
}

.invoice-calendar :deep(.fc .fc-toolbar-title) {
    color: white;
    font-size: 1.3rem;
    font-weight: 700;
}

.invoice-calendar :deep(.fc .fc-button-primary) {
    background-color: rgba(15, 23, 42, 0.9);
    border-color: rgba(148, 163, 184, 0.2);
}

.invoice-calendar :deep(.fc .fc-button-primary:not(:disabled).fc-button-active),
.invoice-calendar :deep(.fc .fc-button-primary:hover) {
    background-color: rgb(14 165 233);
    border-color: rgb(14 165 233);
}

.invoice-calendar :deep(.fc-theme-standard td),
.invoice-calendar :deep(.fc-theme-standard th),
.invoice-calendar :deep(.fc-theme-standard .fc-scrollgrid) {
    border-color: rgba(148, 163, 184, 0.16);
}

.invoice-calendar :deep(.fc .fc-daygrid-day-number),
.invoice-calendar :deep(.fc .fc-col-header-cell-cushion) {
    color: rgb(226 232 240);
    text-decoration: none;
}

.invoice-calendar :deep(.fc .fc-daygrid-event) {
    border-radius: 0.75rem;
    padding-inline: 0.25rem;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 180ms ease, transform 180ms ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(8px);
}
</style>