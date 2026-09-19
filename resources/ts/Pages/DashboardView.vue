<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';
import { getCurrencySymbol } from '@/Utils/Currency';
import { useRouter } from 'vue-router';

import Badge from '@/Components/Badge.vue';
import Tooltip from '@/Components/Tooltip.vue';
import DashboardStatCard from '@/Components/DashboardStatCard.vue';
import ForecastChart from '@/Widgets/ForecastChart.vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import deLocale from '@fullcalendar/core/locales/de';
import {
    buildMonthlyForecast,
    buildUpcomingItems,
    computeDashboardStats,
    formatAmountWithSymbol,
    type UpcomingItem,
} from '@/Utils/Forecast';

import type { CalendarOptions } from '@fullcalendar/core';
import type {
    EventClickArg,
    EventContentArg,
    EventInput,
} from '@fullcalendar/core';
import {
    InvoiceRecurrence,
    InvoiceTypes,
    type InvoiceEvent,
} from '@/Types/Invoice';
import { useInvoices } from '@/Composables/useInvoices';
import { useAppSettings } from '@/Composables/useAppSettings';
import { locale, t } from '@/i18n';

type CalendarDateClickArg = {
    dateStr: string;
    jsEvent: MouseEvent;
    dayEl: HTMLElement;
};

const router = useRouter();
const { invoices, fetchInvoices } = useInvoices();
const { appCurrency, appCurrencySymbol, loadAppSettings } = useAppSettings();
const isCompactView = ref(false);
const tooltipDate = ref('');
const tooltip = ref<InstanceType<typeof Tooltip> | null>(null);
const calendarRef = ref<InstanceType<typeof FullCalendar> | null>(null);
const forecastRange = ref(6);

const forecastMonths = computed(() =>
    buildMonthlyForecast(invoices.value, forecastRange.value, appCurrency.value),
);

const upcomingItems = computed(() =>
    buildUpcomingItems(invoices.value, 6, appCurrency.value),
);

const dashboardStats = computed(() =>
    computeDashboardStats(invoices.value, appCurrency.value),
);

const statsDisplay = computed(() => {
    const stats = dashboardStats.value;

    return {
        dueThisMonth: formatAmountWithSymbol(
            stats.dueThisMonth,
            stats.currency,
        ),
        overdue: formatAmountWithSymbol(stats.overdueTotal, stats.currency),
        recurring: formatAmountWithSymbol(
            stats.recurringCommitment,
            stats.currency,
        ),
    };
});

const setForecastRange = (months: number) => {
    forecastRange.value = months;
};

const goToMonth = (key: string) => {
    const api = calendarRef.value?.getApi();

    if (!api) return;

    const [year, month] = key.split('-').map(Number);

    api.changeView('dayGridMonth');
    api.gotoDate(new Date(year, month - 1, 1));
};

const formatUpcomingDate = (dateKey: string) => {
    const [year, month, day] = dateKey.split('-').map(Number);

    return new Intl.DateTimeFormat(
        locale.value === 'de' ? 'de-DE' : 'en-US',
        {
            weekday: 'short',
            month: 'short',
            day: 'numeric',
        },
    ).format(new Date(year, month - 1, day));
};

const formatUpcomingAmount = (item: UpcomingItem) => {
    return `${getCurrencySymbol(item.currency)}${item.amount.toLocaleString(
        locale.value === 'de' ? 'de-DE' : 'en-US',
        { maximumFractionDigits: 2 },
    )}`;
};

const getUpcomingTypeLabel = (type: InvoiceTypes) => {
    return type === InvoiceTypes.RECURRING
        ? t('invoices.recurring')
        : t('invoices.oneTime');
};

const getUpcomingTypeVariant = (type: InvoiceTypes) => {
    return type === InvoiceTypes.RECURRING ? 'teal' : 'sky';
};

const updateCalendarView = () => {
    const width = window.innerWidth;
    const nextCompactView = width < 768;

    if (isCompactView.value !== nextCompactView)
        isCompactView.value = nextCompactView;

    if (!calendarRef.value) return;

    const api = calendarRef.value.getApi();
    const nextView = calendarView.value;

    if (api.view?.type !== nextView) {
        api.changeView(nextView);
    }
};

const calendarView = computed(() => {
    if (isCompactView.value) return 'dayGridWeek';

    return 'dayGridMonth';
});

const toDateOnly = (value: string) => {
    const datePart = value.split('T')[0].split(' ')[0];
    const [year, month, day] = datePart.split('-').map(Number);

    return new Date(year, month - 1, day);
};

const formatDateOnly = (value: Date) => {
    return value.toISOString().slice(0, 10);
};

const addMonths = (date: Date, months: number) => {
    const nextDate = new Date(date);
    nextDate.setMonth(nextDate.getMonth() + months);
    return nextDate;
};

const addYears = (date: Date, years: number) => {
    return addMonths(date, years * 12);
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

const advanceCalendarRecurrenceDate = (date: Date, recurrence: string) => {
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

const buildInvoiceEvents = (invoice: InvoiceEvent): EventInput[] => {
    if (invoice.type !== InvoiceTypes.RECURRING) {
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
                    invoiceId: invoice.id,
                },
                classNames: getEventClassNames(invoice),
            },
        ];
    }

    const events: EventInput[] = [];
    const startDate = toDateOnly(invoice.start_date);
    const endDate = invoice.end_date
        ? toDateOnly(invoice.end_date)
        : addYears(startDate, 10);
    let currentDate = new Date(startDate);

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
                invoiceId: invoice.id,
            },
            classNames: getEventClassNames(invoice),
        });

        const nextDate = advanceCalendarRecurrenceDate(
            currentDate,
            invoice.recurrence ?? InvoiceRecurrence.MONTHLY,
        );

        if (!nextDate) break;

        currentDate = nextDate;
    }

    return events;
};

const getEventClassNames = (event: InvoiceEvent) => {
    const baseClass = 'invoice-event';
    const typeClass = `invoice-event--${event.type.toLowerCase()}`;
    return [baseClass, typeClass];
};

const getAmountLabel = (event: InvoiceEvent) => {
    return event.recurrence
        ? `${getCurrencySymbol(event.currency)}${event.price} / ${event.recurrence}`
        : `${getCurrencySymbol(event.currency)}${event.price}`;
};

const closeTooltip = () => {
    tooltip.value?.close();
    tooltipDate.value = '';
};

const openTooltip = async (clickInfo: CalendarDateClickArg) => {
    console.log('Opening tooltip for date:', clickInfo.dateStr);
    tooltipDate.value = clickInfo.dateStr;

    tooltip.value?.open(clickInfo.jsEvent, clickInfo.dayEl);
};

const createInvoiceForDay = () => {
    if (!tooltipDate.value) return;

    router.push({
        name: 'invoice-create',
        query: {
            date: tooltipDate.value,
        },
    });
};

const handleEventClick = (clickEvent: EventClickArg) => {
    router.push({
        name: 'invoice-edit',
        params: {
            id: String(
                clickEvent.event.extendedProps.invoiceId ?? clickEvent.event.id,
            ),
        },
    });
};

const calendarOptions = computed<CalendarOptions>(() => ({
    plugins: [dayGridPlugin, interactionPlugin],
    locales: [deLocale],
    locale: locale.value,
    initialView: calendarView.value,
    height: 'auto',
    contentHeight: 'auto',
    aspectRatio: isCompactView.value ? 1.1 : 1.45,
    expandRows: true,
    fixedWeekCount: true,
    dayMaxEventRows: isCompactView.value ? 2 : 3,
    dayHeaderFormat: isCompactView.value
        ? { weekday: 'short', day: 'numeric' }
        : { weekday: 'short' },
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: '',
    },
    buttonText: {
        today: t('dashboard.today'),
    },

    events: invoices.value.flatMap((invoice) => buildInvoiceEvents(invoice)),
    eventContent: (eventInfo: EventContentArg) => ({
        html: `
            <div class="invoice-event-content${isCompactView.value ? ' invoice-event-content--compact' : ''}">
                <div class="invoice-event-title">${eventInfo.event.title}</div>
                ${isCompactView.value ? '' : `<div class="invoice-event-meta">${eventInfo.event.extendedProps.amountLabel ?? ''}</div>`}
            </div>
        `,
    }),
    eventClick: handleEventClick,
    dateClick: (clickInfo: CalendarDateClickArg) => {
        if (tooltip.value?.isOpen && tooltipDate.value === clickInfo.dateStr) {
            closeTooltip();
            return;
        }

        openTooltip(clickInfo);
    },
}));

onMounted(() => {
    void loadAppSettings();
    void fetchInvoices();
    updateCalendarView();
    window.addEventListener('resize', updateCalendarView);
    window.addEventListener('orientationchange', updateCalendarView);

    nextTick(() => {
        updateCalendarView();
    });
});

onUnmounted(() => {
    window.removeEventListener('resize', updateCalendarView);
});
</script>

<template>
    <div class="space-y-3 sm:space-y-5">
        <section class="grid grid-cols-1 gap-2 xl:grid-cols-3 sm:gap-4">
            <DashboardStatCard
                :label="t('dashboard.dueThisMonth')"
                :value="statsDisplay.dueThisMonth"
                :hint="t('dashboard.dueThisMonthHint')"
                tone="sky"
            />
            <DashboardStatCard
                :label="t('dashboard.overdue')"
                :value="statsDisplay.overdue"
                :hint="t('dashboard.overdueHint')"
                tone="rose"
            />
            <DashboardStatCard
                :label="t('dashboard.recurringCommitments')"
                :value="statsDisplay.recurring"
                :hint="t('dashboard.recurringCommitmentsHint')"
                tone="teal"
            />
        </section>

        <section class="calendar-shell relative flex h-full min-h-[30rem] flex-col overflow-hidden rounded-[2rem] border border-slate-900/10 bg-slate-900/5 shadow-[0_18px_40px_-28px_rgba(15,23,42,0.45)] backdrop-blur-xl dark:border-white/10 dark:bg-white/5 sm:min-h-[38rem]">
        <div class="flex items-center justify-between border-b border-slate-900/10 px-5 py-4 sm:px-6 dark:border-white/10">
            <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                    {{ t('dashboard.calendarTitle') }}
                </h2>
                <p class="mr-4 text-sm text-slate-600 dark:text-slate-400">
                    {{ t('dashboard.calendarSubtitle') }}
                </p>
            </div>
            <Badge variant="sky" size="md"
                >{{ invoices.length }} {{ t('dashboard.invoices') }}</Badge
            >
        </div>

        <div class="flex-1 p-3 sm:p-5">
            <div class="overflow-x-auto">
                <FullCalendar
                    ref="calendarRef"
                    :key="calendarView"
                    :options="calendarOptions"
                    class="invoice-calendar"
                    :class="{ 'invoice-calendar--compact': isCompactView }"
                />
            </div>
        </div>
        <Tooltip ref="tooltip" width="w-72" @close="closeTooltip">
            <div class="space-y-2">
                <p class="text-xs uppercase tracking-[0.24em] text-slate-600 dark:text-slate-400">
                    {{ t('dashboard.selectedDay') }}
                </p>
                <p class="text-xs text-slate-600 dark:text-slate-400">{{ tooltipDate }}</p>

                <button
                    type="button"
                    class="mt-2 w-full rounded-xl bg-cyan-700 px-3 py-2 text-sm font-semibold text-white transition hover:bg-cyan-800 dark:bg-cyan-400 dark:text-slate-950 dark:hover:bg-cyan-300"
                    @click.stop="createInvoiceForDay"
                >
                    {{ t('dashboard.createInvoiceForDay') }}
                </button>

                <p class="text-xs leading-5 text-slate-600 dark:text-slate-400">
                    {{ t('dashboard.moreDayActions') }}
                </p>
            </div>
        </Tooltip>
        </section>

        <section class="dashboard-panel rounded-[2rem] border border-slate-900/10 bg-slate-900/5 p-5 shadow-2xl shadow-slate-500/40 backdrop-blur-xl dark:border-white/10 dark:bg-white/5 dark:shadow-black/40 sm:p-6">
            <div class="flex flex-col gap-3 border-b border-slate-900/10 pb-4 sm:flex-row sm:items-center sm:justify-between sm:pb-5 dark:border-white/10">
                <div>
                    <p class="text-xs uppercase tracking-[0.28em] text-cyan-700/70 dark:text-cyan-300/70">
                        {{ t('dashboard.forecast') }}
                    </p>
                    <h2 class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">
                        {{ t('dashboard.upcomingPayments') }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                        {{ t('dashboard.upcomingPaymentsDescription') }}
                    </p>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <button
                        type="button"
                        class="rounded-xl border px-3 py-2 text-sm font-semibold transition"
                        :class="
                            forecastRange === 6
                                ? 'border-slate-900/10 bg-slate-900 text-white dark:border-white/10 dark:bg-white dark:text-slate-950'
                                : 'border-slate-900/10 bg-white/80 text-slate-600 hover:bg-slate-200/80 dark:border-white/10 dark:bg-slate-900/80 dark:text-slate-300 dark:hover:bg-slate-800/80'
                        "
                        @click="setForecastRange(6)"
                    >
                        {{ t('dashboard.months6') }}
                    </button>
                    <button
                        type="button"
                        class="rounded-xl border px-3 py-2 text-sm font-semibold transition"
                        :class="
                            forecastRange === 12
                                ? 'border-slate-900/10 bg-slate-900 text-white dark:border-white/10 dark:bg-white dark:text-slate-950'
                                : 'border-slate-900/10 bg-white/80 text-slate-600 hover:bg-slate-200/80 dark:border-white/10 dark:bg-slate-900/80 dark:text-slate-300 dark:hover:bg-slate-800/80'
                        "
                        @click="setForecastRange(12)"
                    >
                        {{ t('dashboard.months12') }}
                    </button>
                </div>
            </div>

            <div class="pt-5">
                <ForecastChart
                    :forecast="forecastMonths"
                    :currency-symbol="appCurrencySymbol"
                    @select-month="goToMonth"
                />
            </div>
        </section>

        <section class="dashboard-panel rounded-[2rem] border border-slate-900/10 bg-slate-900/5 p-5 shadow-2xl shadow-slate-500/40 backdrop-blur-xl dark:border-white/10 dark:bg-white/5 dark:shadow-black/40 sm:p-6">
            <div class="border-b border-slate-900/10 pb-4 sm:pb-5 dark:border-white/10">
                <p class="text-xs uppercase tracking-[0.28em] text-cyan-700/70 dark:text-cyan-300/70">
                    {{ t('dashboard.upcoming') }}
                </p>
                <h2 class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">
                    {{ t('dashboard.recentUpcoming') }}
                </h2>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                    {{ t('dashboard.recentUpcomingDescription') }}
                </p>
            </div>

            <div
                v-if="upcomingItems.length"
                class="divide-y divide-slate-900/5 dark:divide-white/5"
            >
                <div
                    v-for="item in upcomingItems"
                    :key="`${item.id}-${item.dateKey}`"
                    class="flex items-center justify-between gap-4 py-3 sm:py-4"
                >
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">
                            {{ item.title }}
                        </p>
                        <p class="mt-0.5 text-xs text-slate-600 dark:text-slate-400">
                            {{ formatUpcomingDate(item.dateKey) }}
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-3">
                        <Badge :variant="getUpcomingTypeVariant(item.type)">
                            {{ getUpcomingTypeLabel(item.type) }}
                        </Badge>
                        <p class="whitespace-nowrap text-sm font-semibold text-slate-900 dark:text-white">
                            {{ formatUpcomingAmount(item) }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="flex min-h-[8rem] items-center justify-center rounded-2xl border border-dashed border-slate-900/10 bg-slate-100/20 p-6 text-center dark:border-white/10 dark:bg-slate-950/20"
            >
                <div>
                    <p class="text-xs uppercase tracking-[0.25em] text-slate-600 dark:text-slate-400">
                        {{ t('dashboard.noData') }}
                    </p>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                        {{ t('dashboard.noDataHint') }}
                    </p>
                </div>
            </div>
        </section>
    </div>
</template>

<style>
/* Root calendar container and overall text color */
.invoice-calendar.fc {
    height: 100%;
    color: rgb(226 232 240);
}

/* Keep the dark-mode panel shadow but soften it for light surfaces */
html.light .dashboard-panel {
    box-shadow: 0 12px 28px -18px rgba(15, 23, 42, 0.22);
}

.invoice-calendar--compact.fc {
    min-width: 44rem;
}

/* Forces the calendar view area to keep enough height for the month grid */
.invoice-calendar.fc .fc-view-harness {
    min-height: 38rem;
}

/* Month title in the toolbar */
.invoice-calendar.fc .fc-toolbar-title {
    color: rgb(248 250 252);
    font-size: 1.3rem;
    font-weight: 700;
}

/* Navigation buttons in the toolbar */
.invoice-calendar.fc .fc-button-primary {
    background-color: rgba(15, 23, 42, 0.9);
    border-color: transparent;
    appearance: none;
    box-shadow: none;
    outline: none;
}

.invoice-calendar.fc .fc-button-primary:focus,
.invoice-calendar.fc .fc-button-primary:focus-visible {
    box-shadow: none;
    outline: none;
}

.invoice-calendar.fc .fc-button-primary:active,
.invoice-calendar.fc .fc-button-primary:focus:not(:focus-visible) {
    box-shadow: none;
    outline: none;
}

.invoice-calendar.fc .fc-button-primary:not(:disabled).fc-button-active,
.invoice-calendar.fc .fc-button-primary:hover {
    background-color: rgb(14 165 233);
    border-color: transparent;
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
    margin: 0.25rem 0.3rem 0;
    border: 1px solid rgba(148, 163, 184, 0.16);
    border-radius: 0.8rem;
    box-shadow: 0 5px 12px rgba(15, 23, 42, 0.12);
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
    width: 0.22rem;
    border-radius: 999px 0 0 999px;
    background: rgba(148, 163, 184, 0.55);
}

/* Individual event themes */
.invoice-calendar.fc .fc-daygrid-event.invoice-event {
    background: linear-gradient(
        135deg,
        rgba(129, 140, 248, 0.65),
        rgba(96, 165, 250, 0.58)
    );
}

.invoice-calendar.fc .fc-daygrid-event.invoice-event--one-time {
    background: linear-gradient(
        135deg,
        rgba(96, 165, 250, 0.62),
        rgba(125, 211, 252, 0.52)
    );
}

.invoice-calendar.fc .fc-daygrid-event.invoice-event--recurring {
    background: linear-gradient(
        135deg,
        rgba(45, 212, 191, 0.64),
        rgba(20, 184, 166, 0.52)
    );
}

/* Event pill inner padding */
.invoice-calendar.fc .fc-daygrid-event.invoice-event .fc-event-main,
.invoice-calendar.fc .fc-daygrid-event.invoice-event .fc-event-main-frame,
.invoice-calendar.fc .fc-daygrid-event.invoice-event .invoice-event-content {
    padding: 0.35rem 0.7rem 0.35rem 0.45rem;
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
    display: block;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    mask-image: linear-gradient(90deg, #000 78%, transparent 100%);
    -webkit-mask-image: linear-gradient(90deg, #000 78%, transparent 100%);
    font-size: 0.74rem;
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

.invoice-calendar--compact.fc .fc-daygrid-event.invoice-event {
    margin: 0.15rem 0.18rem 0;
    border-radius: 0.65rem;
    box-shadow: 0 3px 8px rgba(2, 6, 23, 0.22);
}

.invoice-calendar--compact.fc .fc-daygrid-event.invoice-event::before {
    width: 0.18rem;
}

.invoice-calendar--compact.fc .fc-daygrid-event.invoice-event .fc-event-main,
.invoice-calendar--compact.fc
    .fc-daygrid-event.invoice-event
    .fc-event-main-frame,
.invoice-calendar--compact.fc
    .fc-daygrid-event.invoice-event
    .invoice-event-content {
    padding: 0.28rem 0.45rem 0.28rem 0.38rem;
}

.invoice-calendar--compact.fc .invoice-event-content--compact {
    gap: 0;
}

.invoice-calendar--compact.fc .invoice-event-title {
    font-size: 0.65rem;
    line-height: 1.1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    mask-image: linear-gradient(90deg, #000 72%, transparent 100%);
    -webkit-mask-image: linear-gradient(90deg, #000 72%, transparent 100%);
}

.invoice-calendar--compact.fc .fc-daygrid-event .fc-event-time,
.invoice-calendar--compact.fc .invoice-event-meta {
    display: none;
}

.invoice-calendar--compact.fc .fc-daygrid-day-frame {
    min-height: 3.4rem;
}

.invoice-calendar--compact.fc .fc-daygrid-day-number {
    padding: 0.25rem 0.35rem;
    font-size: 0.72rem;
}

.invoice-calendar--compact.fc .fc-col-header-cell-cushion {
    font-size: 0.66rem;
    letter-spacing: 0.16em;
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

/* ---------------------------------------------------------------- */
/* Light theme adjustments for the calendar (dark-first by default). */
/* ---------------------------------------------------------------- */

html.light .invoice-calendar.fc {
    color: rgb(30 41 59);
}

html.light .invoice-calendar.fc .fc-toolbar-title {
    color: rgb(30 41 59);
}

html.light .invoice-calendar.fc .fc-button-primary {
    background-color: #ffffff;
    border-color: transparent;
    appearance: none;
    color: rgb(51 65 85);
    box-shadow: none;
    outline: none;
}

html.light .invoice-calendar.fc .fc-button-primary:focus,
html.light .invoice-calendar.fc .fc-button-primary:focus-visible {
    box-shadow: none;
    outline: none;
}

html.light .invoice-calendar.fc .fc-button-primary:active,
html.light .invoice-calendar.fc .fc-button-primary:focus:not(:focus-visible) {
    box-shadow: none;
    outline: none;
}

html.light .invoice-calendar.fc .fc-button-primary:not(:disabled).fc-button-active,
html.light .invoice-calendar.fc .fc-button-primary:hover {
    background-color: rgb(14 165 233);
    border-color: transparent;
    color: #ffffff;
}

html.light .invoice-calendar.fc .fc-theme-standard td,
html.light .invoice-calendar.fc .fc-theme-standard th,
html.light .invoice-calendar.fc .fc-theme-standard .fc-scrollgrid {
    border-color: rgba(203, 213, 225, 0.95);
}

html.light .invoice-calendar.fc .fc-theme-standard .fc-scrollgrid {
    background: rgba(255, 255, 255, 0.6);
}

html.light .invoice-calendar.fc .fc-col-header,
html.light .invoice-calendar.fc .fc-col-header-cell {
    background: rgba(248, 250, 252, 0.95);
}

html.light .invoice-calendar.fc .fc-col-header-cell-cushion {
    color: rgb(100 116 139);
}

html.light .invoice-calendar.fc .fc-daygrid-day-number {
    color: rgb(30 41 59);
}

html.light .invoice-calendar.fc .fc-daygrid-day.fc-day-other .fc-daygrid-day-number {
    color: rgb(148 163 184);
}

html.light .invoice-calendar.fc .fc-daygrid-day.fc-day-today {
    background: rgb(224 242 254 / 0.85);
    box-shadow: inset 0 0 0 1px rgb(56 189 248 / 0.45);
}

html.light .invoice-calendar.fc .fc-daygrid-event.invoice-event {
    border-color: rgba(100, 116, 139, 0.25);
    box-shadow: 0 5px 12px rgba(15, 23, 42, 0.12);
}

/* Event pills keep their saturated gradients in both themes, so their
   text must stay light regardless of the surrounding theme. */
html.light .invoice-calendar.fc .fc-daygrid-event.invoice-event,
html.light .invoice-calendar.fc .invoice-event-title {
    color: rgb(248 250 252);
}

html.light .invoice-calendar.fc .invoice-event-meta {
    color: rgba(248, 250, 252, 0.78);
}
</style>
