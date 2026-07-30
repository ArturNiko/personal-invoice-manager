<script setup lang="ts">
import { computed } from 'vue'
import { getCurrencySymbol } from '@/Utils/Currency'
import { useRouter } from 'vue-router'

import Badge from '@/Components/Badge.vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'

import type { EventContentArg } from '@fullcalendar/core'
import type { InvoiceEvent } from '@/Types/Invoice'

const router = useRouter()
const props = defineProps<{ invoices: InvoiceEvent[] }>()

const recurrenceIntervals: Record<string, number> = {
    weekly: 7,
    biweekly: 14,
    monthly: 1,
    quarterly: 3,
    semiannual: 6,
    yearly: 12,
}

const toDateOnly = (value: string) => {
    const datePart = value.split('T')[0].split(' ')[0]
    const [year, month, day] = datePart.split('-').map(Number)

    return new Date(year, month - 1, day)
}

const formatDateOnly = (value: Date) => {
    return value.toISOString().slice(0, 10)
}

const addMonths = (date: Date, months: number) => {
    const nextDate = new Date(date)
    nextDate.setMonth(nextDate.getMonth() + months)
    return nextDate
}

const addYears = (date: Date, years: number) => {
    return addMonths(date, years * 12)
}

const addInterval = (date: Date, recurrence: string) => {
    const nextDate = new Date(date)

    if (recurrence === 'weekly' || recurrence === 'biweekly') {
        nextDate.setDate(nextDate.getDate() + recurrenceIntervals[recurrence])
        return nextDate
    }

    return addMonths(nextDate, recurrenceIntervals[recurrence] ?? 1)
}

const buildInvoiceEvents = (invoice: InvoiceEvent) => {
    if (invoice.type !== 'recurring') {
        return [
            {
                id: invoice.id.toString(),
                title: invoice.title,
                start: invoice.start_date,
                allDay: true,
                extendedProps: {
                    amountLabel: getAmountLabel(invoice),
                    type: invoice.type,
                    recurrence: invoice.recurrence,
                },
                classNames: getEventClassNames(invoice),
            },
        ]
    }

    const events = []
    const startDate = toDateOnly(invoice.start_date)
    const endDate = invoice.end_date ? toDateOnly(invoice.end_date) : addYears(startDate, 10)
    let currentDate = new Date(startDate)

    while (currentDate <= endDate) {
        events.push({
            id: `${invoice.id}-${formatDateOnly(currentDate)}`,
            title: invoice.title,
            start: formatDateOnly(currentDate),
            allDay: true,
            extendedProps: {
                amountLabel: getAmountLabel(invoice),
                type: invoice.type,
                recurrence: invoice.recurrence,
            },
            classNames: getEventClassNames(invoice),
        })

        currentDate = addInterval(currentDate, invoice.recurrence ?? 'monthly')
    }

    return events
}

const getEventClassNames = (event: InvoiceEvent) => {
    const baseClass = 'invoice-event'
    const typeClass = `invoice-event--${event.type.toLowerCase()}`
    return [baseClass, typeClass]
}

const getAmountLabel = (event: InvoiceEvent) => {
    return event.recurrence 
        ? `${getCurrencySymbol(event.currency)}${event.price_occurrence} / ${event.recurrence}` 
        : `${getCurrencySymbol(event.currency)}${event.price_total}`
}

const handleEventClick = (clickEvent: { event: InvoiceEvent }) => {
    // how do i get the data
    router.push({ name: 'invoice-edit', params: { id: clickEvent.event.id } })
}
    
const calendarOptions = computed(() => ({
    plugins: [dayGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    height: 'auto',
    contentHeight: 'auto',
    aspectRatio: 1.45,
    expandRows: true,
    fixedWeekCount: true,
    dayMaxEventRows: 3,
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: '',
    },
    buttonText: {
        today: 'Today',
    },
    
    events: props.invoices.flatMap((invoice) => buildInvoiceEvents(invoice)),
    eventContent: (eventInfo: EventContentArg) => ({
        html: `
            <div class="invoice-event-content">
                <div class="invoice-event-title">${eventInfo.event.title}</div>
                <div class="invoice-event-meta">${eventInfo.event.extendedProps.amountLabel ?? ''}</div>
            </div>
        `,
    }),
    eventClick: handleEventClick
}))


</script>

<template>
    <section class="calendar-shell flex h-full min-h-[38rem] flex-col overflow-hidden rounded-[2rem] border border-white/10 bg-white/5 shadow-2xl shadow-slate-950/40 backdrop-blur-xl">
        <div class="flex items-center justify-between border-b border-white/10 px-5 py-4 sm:px-6">
            <div>
                <h2 class="text-lg font-semibold text-white">Calendar</h2>
                <p class="text-sm text-slate-400">Monthly planning view with invoice-related reminders.</p>
            </div>
            <Badge variant="emerald" size="md">Full size</Badge>
        </div>

        <div class="flex-1 p-3 sm:p-5">
            <FullCalendar :options="calendarOptions" class="invoice-calendar" />
        </div>
    </section>
</template>

<style>
/* Root calendar container and overall text color */
.invoice-calendar.fc {
    height: 100%;
    color: rgb(226 232 240);
}

/* Forces the calendar view area to keep enough height for the month grid */
.invoice-calendar.fc .fc-view-harness {
    min-height: 38rem;
}

/* Month title in the toolbar */
.invoice-calendar.fc .fc-toolbar-title {
    color: white;
    font-size: 1.3rem;
    font-weight: 700;
}

/* Navigation buttons in the toolbar */
.invoice-calendar.fc .fc-button-primary {
    background-color: rgba(15, 23, 42, 0.9);
    border-color: rgba(148, 163, 184, 0.2);
}

.invoice-calendar.fc .fc-button-primary:not(:disabled).fc-button-active,
.invoice-calendar.fc .fc-button-primary:hover {
    background-color: rgb(14 165 233);
    border-color: rgb(14 165 233);
}

/* Grid borders for the header row and day cells */
.invoice-calendar.fc .fc-theme-standard td,
.invoice-calendar.fc .fc-theme-standard th,
.invoice-calendar.fc .fc-theme-standard .fc-scrollgrid {
    border-color: rgba(51, 65, 85, 0.95);
}

/* Keep the grid background dark so the white borders don't pop too hard */
.invoice-calendar.fc .fc-theme-standard .fc-scrollgrid {
    background: rgba(15, 23, 42, 0.6);
}

/* Weekday strip at the top of the calendar */
.invoice-calendar.fc .fc-col-header,
.invoice-calendar.fc .fc-col-header-cell {
    background: rgba(15, 23, 42, 0.95);
}

.invoice-calendar.fc .fc-col-header-cell-cushion {
    color: rgb(148 163 184);
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.22em;
    text-transform: uppercase;
}

/* Day numbers inside each month cell */
.invoice-calendar.fc .fc-daygrid-day-number {
    color: rgb(226 232 240);
    text-decoration: none;
}

/* Day number placement and spacing */
.invoice-calendar.fc .fc-daygrid-day-number {
    padding: 0.5rem;
}

/* Month cell height */
.invoice-calendar.fc .fc-daygrid-day-frame {
    min-height: 5.25rem;
}

/* The faded days from the previous/next month */
.invoice-calendar.fc .fc-daygrid-day.fc-day-other .fc-daygrid-day-number {
    color: rgb(148 163 184);
}

/* Highlight the current day */
.invoice-calendar.fc .fc-daygrid-day.fc-day-today {
    background: rgb(15 23 42 / 0.85);
    box-shadow: inset 0 0 0 1px rgb(56 189 248 / 0.35);
}

/* Event area inside each day cell */
.invoice-calendar.fc .fc-daygrid-body-unbalanced .fc-daygrid-day-events,
.invoice-calendar.fc .fc-daygrid-body-natural .fc-daygrid-day-events {
    min-height: 2.5rem;
}

/* Base event pill shell and spacing */
.invoice-calendar.fc .fc-daygrid-event.invoice-event {
    margin: 0.35rem 0.35rem 0;
    border: 1px solid rgba(148, 163, 184, 0.18);
    border-radius: 0.9rem;
    box-shadow: 0 6px 14px rgba(2, 6, 23, 0.32);
    overflow: hidden;
    position: relative;
    background-clip: padding-box;
    cursor: pointer;
}

/* Soft accent bar on the left side of each event */
.invoice-calendar.fc .fc-daygrid-event.invoice-event::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 0.28rem;
    border-radius: 999px 0 0 999px;
    background: rgba(148, 163, 184, 0.55);
}

/* Individual event themes */
.invoice-calendar.fc .fc-daygrid-event.invoice-event {
    background: linear-gradient(135deg, rgba(88, 28, 135, 0.9), rgba(109, 40, 217, 0.82));
}

.invoice-calendar.fc .fc-daygrid-event.invoice-event--one-time {
    background: linear-gradient(135deg, rgba(30, 64, 175, 0.9), rgba(37, 99, 235, 0.82));
}

.invoice-calendar.fc .fc-daygrid-event.invoice-event--recurring {
    background: linear-gradient(135deg, rgba(15, 118, 110, 0.92), rgba(20, 184, 166, 0.75));
}

/* Event pill inner padding */
.invoice-calendar.fc .fc-daygrid-event.invoice-event .fc-event-main,
.invoice-calendar.fc .fc-daygrid-event.invoice-event .fc-event-main-frame,
.invoice-calendar.fc .fc-daygrid-event.invoice-event .invoice-event-content {
    padding: 0.5rem 0.8rem 0.5rem 0.5rem;
    cursor: pointer;
}

/* Custom event layout inside the pill */
.invoice-calendar.fc .invoice-event-content {
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
}

/* Event title line */
.invoice-calendar.fc .invoice-event-title {
    font-size: 0.78rem;
    font-weight: 600;
    line-height: 1.15;
    letter-spacing: 0.01em;
}

/* Event meta line for amount or recurrence */
.invoice-calendar.fc .invoice-event-meta {
    color: rgba(226, 232, 240, 0.75);
    font-size: 0.68rem;
    font-weight: 500;
    letter-spacing: 0.02em;
    line-height: 1.1;
}

/* Hide the default FullCalendar title node because we render our own layout */
.invoice-calendar.fc .fc-event-title {
    display: none;
}

/* Reduce the default event color clash a bit by making hover calmer */
.invoice-calendar.fc .fc-daygrid-event.invoice-event:hover {
    filter: brightness(1.08) saturate(1.02);
    transform: translateY(-1px);
}

</style>