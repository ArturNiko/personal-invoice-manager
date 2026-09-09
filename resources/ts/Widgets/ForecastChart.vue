<script setup lang="ts">
import { computed } from 'vue';

import type { MonthlyForecast } from '@/Utils/Forecast';

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

const maxTotal = computed(() => {
    return Math.max(
        ...props.forecast.map((month) => month.total),
        0,
    );
});

const segmentHeight = (value: number) => {
    if (!value || maxTotal.value <= 0) return '0%';

    const percent = (value / maxTotal.value) * 100;

    return `${Math.max(percent, 3)}%`;
};

const formatValue = (value: number) => {
    return `${props.currencySymbol}${value.toLocaleString('en-US', {
        maximumFractionDigits: 0,
    })}`;
};

const isCurrentMonth = (key: string) => {
    const today = new Date();
    const currentKey = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`;

    return key === currentKey;
};
</script>

<template>
    <div>
        <div class="mb-4 flex items-center gap-4">
            <span
                class="inline-flex items-center gap-2 text-xs font-medium text-slate-300"
            >
                <span
                    class="h-2.5 w-2.5 rounded-sm bg-gradient-to-t from-teal-500 to-teal-400"
                ></span>
                Recurring
            </span>
            <span
                class="inline-flex items-center gap-2 text-xs font-medium text-slate-300"
            >
                <span
                    class="h-2.5 w-2.5 rounded-sm bg-gradient-to-t from-sky-600 to-sky-400"
                ></span>
                One-time
            </span>
        </div>

        <div class="flex items-end gap-2 overflow-x-auto pb-1 sm:gap-3">
            <div
                v-for="month in forecast"
                :key="month.key"
                class="group relative flex min-w-[2.5rem] flex-1 flex-col items-center"
            >
                <div
                    class="pointer-events-none absolute bottom-7 left-1/2 z-10 mb-2 w-40 -translate-x-1/2 rounded-xl border border-white/10 bg-slate-900/95 px-3 py-2 text-xs shadow-xl opacity-0 backdrop-blur transition duration-150 group-hover:opacity-100"
                >
                    <p class="font-semibold text-white">
                        {{ formatValue(month.total) }}
                        <span class="font-normal text-slate-400">total</span>
                    </p>
                    <p class="mt-1 flex items-center gap-1.5 text-slate-300">
                        <span
                            class="h-2 w-2 rounded-sm bg-teal-400"
                        ></span>
                        {{ formatValue(month.recurringTotal) }}
                    </p>
                    <p class="mt-0.5 flex items-center gap-1.5 text-slate-300">
                        <span
                            class="h-2 w-2 rounded-sm bg-sky-500"
                        ></span>
                        {{ formatValue(month.oneTimeTotal) }}
                    </p>
                </div>

                <button
                    type="button"
                    class="flex h-40 w-full cursor-pointer items-end justify-center rounded-xl border border-transparent transition duration-150 group-hover:border-cyan-400/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400/60"
                    :class="
                        isCurrentMonth(month.key)
                            ? 'border-white/15 bg-slate-950/40'
                            : ''
                    "
                    :title="`View ${month.label} in calendar`"
                    @click="emit('selectMonth', month.key)"
                >
                    <span
                        class="flex h-full w-full flex-col justify-end overflow-hidden rounded-lg"
                        :class="[
                            month.total > 0
                                ? 'shadow-[0_10px_25px_-10px_rgba(2,6,23,0.6)]'
                                : '',
                            isCurrentMonth(month.key)
                                ? 'ring-1 ring-slate-300/20'
                                : '',
                        ]"
                    >
                        <span
                            v-if="month.recurringTotal > 0"
                            class="w-full bg-gradient-to-t from-teal-600 to-teal-400"
                            :style="{ height: segmentHeight(month.recurringTotal) }"
                        ></span>
                        <span
                            v-if="month.oneTimeTotal > 0"
                            class="w-full bg-gradient-to-t from-sky-700 to-sky-400"
                            :style="{ height: segmentHeight(month.oneTimeTotal) }"
                        ></span>
                    </span>
                </button>

                <span
                    class="mt-2 text-[0.65rem] font-semibold uppercase tracking-[0.12em]"
                    :class="
                        isCurrentMonth(month.key)
                            ? 'text-cyan-300'
                            : 'text-slate-400'
                    "
                >
                    {{ month.label }}
                </span>
                <span
                    v-if="isCurrentMonth(month.key)"
                    class="mt-0.5 text-[0.6rem] uppercase tracking-[0.12em] text-cyan-400/70"
                >
                    now
                </span>
            </div>
        </div>

        <p
            v-if="maxTotal === 0"
            class="mt-4 text-center text-sm text-slate-400"
        >
            No upcoming payments in the forecast window.
        </p>
    </div>
</template>