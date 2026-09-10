<script setup lang="ts">
import {
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    Filler,
    Legend,
    LinearScale,
    Tooltip,
    type ActiveElement,
    type ChartConfiguration,
    type ChartEvent,
} from 'chart.js';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

import type { MonthlyForecast } from '@/Utils/Forecast';

Chart.register(
    BarController,
    BarElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
    Filler,
);

const props = withDefaults(
    defineProps<{
        forecast: MonthlyForecast[];
        currencySymbol: string;
    }>(),
    {
        currencySymbol: '€',
    },
);

const emit = defineEmits<{
    selectMonth: [key: string];
}>();

const canvasRef = ref<HTMLCanvasElement | null>(null);
let chart: Chart<'bar'> | null = null;

const hasData = computed(() =>
    props.forecast.some((month) => month.total > 0),
);

const formatValue = (value: number) => {
    return `${props.currencySymbol}${value.toLocaleString('en-US', {
        maximumFractionDigits: 0,
    })}`;
};

const formatAxisValue = (value: number) => {
    const compact = new Intl.NumberFormat('en-US', {
        notation: 'compact',
        maximumFractionDigits: 1,
    }).format(value);

    return `${compact} ${props.currencySymbol}`.trim();
};

const buildConfig = (): ChartConfiguration<'bar'> => ({
    type: 'bar',
    data: {
        labels: props.forecast.map((month) => month.label),
        datasets: [
            {
                label: 'Recurring',
                data: props.forecast.map((month) => month.recurringTotal),
                backgroundColor: '#2dd4bf',
                hoverBackgroundColor: '#5eead4',
                borderRadius: 5,
                borderSkipped: true,
                stack: 'total',
            },
            {
                label: 'One-time',
                data: props.forecast.map((month) => month.oneTimeTotal),
                backgroundColor: '#38bdf8',
                hoverBackgroundColor: '#7dd3fc',
                borderRadius: 5,
                borderSkipped: true,
                stack: 'total',
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 250 },
        devicePixelRatio: Math.min(window.devicePixelRatio || 1, 2),
        color: '#94a3b8',
        interaction: { mode: 'index', intersect: false },
        onClick: (event: ChartEvent, elements: ActiveElement[]) => {
            if (!elements.length) return;

            const index = elements[0].index;
            const month = props.forecast[index];

            if (month) emit('selectMonth', month.key);
        },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(15,23,42,0.95)',
                titleColor: '#f1f5f9',
                bodyColor: '#cbd5e1',
                borderColor: 'rgba(255,255,255,0.1)',
                borderWidth: 1,
                padding: 12,
                cornerRadius: 10,
                boxWidth: 10,
                boxHeight: 10,
                usePointStyle: true,
                callbacks: {
                    label: (context) =>
                        `${context.dataset.label}: ${formatValue(
                            context.parsed.y,
                        )}`,
                    afterBody: (items) => {
                        const index = items[0]?.dataIndex;

                        if (index === undefined) return '';

                        const month = props.forecast[index];

                        return month
                            ? `Total: ${formatValue(month.total)}`
                            : '';
                    },
                },
            },
        },
        scales: {
            x: {
                stacked: true,
                grid: { display: false },
                border: { display: false },
                ticks: {
                    color: '#64748b',
                    font: { size: 11, weight: 600 },
                },
            },
            y: {
                stacked: true,
                beginAtZero: true,
                grid: { color: 'rgba(255,255,255,0.06)' },
                border: { display: false },
                ticks: {
                    color: '#64748b',
                    maxTicksLimit: 6,
                    callback: (value) => formatAxisValue(Number(value)),
                },
            },
        },
    },
});

const updateChart = () => {
    if (!chart) return;

    chart.data.labels = props.forecast.map((month) => month.label);
    chart.data.datasets[0].data = props.forecast.map(
        (month) => month.recurringTotal,
    );
    chart.data.datasets[1].data = props.forecast.map(
        (month) => month.oneTimeTotal,
    );
    chart.update();
};

onMounted(async () => {
    await nextTick();

    if (!canvasRef.value) return;

    chart = new Chart<'bar'>(canvasRef.value, buildConfig());
});

watch(
    () => [props.forecast, props.currencySymbol],
    () => updateChart(),
    { deep: true },
);

onBeforeUnmount(() => {
    chart?.destroy();
    chart = null;
});
</script>

<template>
    <div>
        <div class="mb-4 flex items-center gap-4">
            <span
                class="inline-flex items-center gap-2 text-xs font-medium text-slate-300"
            >
                <span class="h-2.5 w-2.5 rounded-sm bg-teal-400"></span>
                Recurring
            </span>
            <span
                class="inline-flex items-center gap-2 text-xs font-medium text-slate-300"
            >
                <span class="h-2.5 w-2.5 rounded-sm bg-sky-400"></span>
                One-time
            </span>
        </div>

        <div class="relative h-64 w-full">
            <canvas ref="canvasRef"></canvas>

            <div
                v-if="!hasData"
                class="pointer-events-none absolute inset-0 grid place-items-center text-sm text-slate-400"
            >
                No upcoming payments in the forecast window.
            </div>
        </div>
    </div>
</template>