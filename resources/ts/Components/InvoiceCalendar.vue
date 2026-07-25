<script setup lang="ts">
import { computed } from 'vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'

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
</script>

<template>
    <section class="calendar-shell flex h-full min-h-[38rem] flex-col overflow-hidden rounded-[2rem] border border-white/10 bg-white/5 shadow-2xl shadow-slate-950/40 backdrop-blur-xl">
        <div class="flex items-center justify-between border-b border-white/10 px-5 py-4 sm:px-6">
            <div>
                <h2 class="text-lg font-semibold text-white">FullCalendar</h2>
                <p class="text-sm text-slate-400">Monthly planning view with invoice-related reminders.</p>
            </div>
            <span class="rounded-full border border-emerald-400/30 bg-emerald-400/10 px-3 py-1 text-xs font-medium text-emerald-200">Full size</span>
        </div>

        <div class="flex-1 p-3 sm:p-5">
            <FullCalendar :options="calendarOptions" class="invoice-calendar" />
        </div>
    </section>
</template>

<style scoped>
.invoice-calendar :deep(.fc) {
    height: 100%;
    color: rgb(226 232 240);
}

.invoice-calendar :deep(.fc .fc-view-harness) {
    min-height: 38rem;
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

.invoice-calendar :deep(.fc .fc-daygrid-day-number) {
    padding: 0.5rem;
}

.invoice-calendar :deep(.fc .fc-daygrid-day-frame) {
    min-height: 5.25rem;
}

.invoice-calendar :deep(.fc .fc-daygrid-day.fc-day-other .fc-daygrid-day-number) {
    color: rgb(148 163 184);
}

.invoice-calendar :deep(.fc .fc-daygrid-day.fc-day-today) {
    background: rgb(15 23 42 / 0.85);
    box-shadow: inset 0 0 0 1px rgb(56 189 248 / 0.35);
}

.invoice-calendar :deep(.fc .fc-daygrid-body-unbalanced .fc-daygrid-day-events),
.invoice-calendar :deep(.fc .fc-daygrid-body-natural .fc-daygrid-day-events) {
    min-height: 2rem;
}

.invoice-calendar :deep(.fc .fc-daygrid-event) {
    border-radius: 0.75rem;
}

</style>