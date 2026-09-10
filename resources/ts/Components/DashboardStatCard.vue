<script setup lang="ts">
import { computed } from 'vue';

import Icon from '@/Components/Icon.vue';

const props = withDefaults(
    defineProps<{
        label: string;
        value: string;
        hint?: string;
        icon?: string;
        tone?: 'default' | 'sky' | 'teal' | 'rose' | 'amber';
    }>(),
    {
        hint: '',
        icon: '',
        tone: 'default',
    },
);

const toneClasses: Record<NonNullable<typeof props.tone>, string> = {
    default: 'border-white/10 bg-slate-900/75 text-white',
    sky: 'border-sky-400/25 bg-sky-500/10 text-sky-100',
    teal: 'border-teal-400/25 bg-teal-500/10 text-teal-100',
    rose: 'border-rose-400/25 bg-rose-500/10 text-rose-100',
    amber: 'border-amber-400/25 bg-amber-500/10 text-amber-100',
};

const labelClasses: Record<NonNullable<typeof props.tone>, string> = {
    default: 'text-slate-400',
    sky: 'text-sky-300/70',
    teal: 'text-teal-300/70',
    rose: 'text-rose-300/70',
    amber: 'text-amber-300/70',
};

const hintClasses: Record<NonNullable<typeof props.tone>, string> = {
    default: 'text-slate-400',
    sky: 'text-sky-300/60',
    teal: 'text-teal-300/60',
    rose: 'text-rose-300/60',
    amber: 'text-amber-300/60',
};

const cardClasses = computed(() => [
    'flex flex-col justify-between gap-2 rounded-2xl border p-2 backdrop-blur sm:gap-4 sm:p-4',
    toneClasses[props.tone],
]);

const iconBoxClasses = computed(() => [
    'flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border sm:h-10 sm:w-10',
    toneClasses[props.tone],
]);
</script>

<template>
    <article :class="cardClasses">
        <div class="flex items-start justify-between gap-3">
            <p
                :class="[
                    'text-[9px] font-semibold uppercase tracking-[0.1em] sm:text-xs sm:tracking-[0.22em]',
                    labelClasses[tone],
                ]"
            >
                {{ label }}
            </p>
            <div v-if="icon" :class="iconBoxClasses" class="hidden sm:flex">
                <Icon
                    :icon="icon"
                    theme="dark"
                    class="h-5 w-5"
                />
            </div>
        </div>

        <div>
            <p
                class="text-base font-semibold text-white sm:text-2xl lg:text-3xl"
            >
                {{ value }}
            </p>
            <p
                v-if="hint"
                :class="['mt-1 hidden text-xs sm:block sm:text-sm', hintClasses[tone]]"
            >
                {{ hint }}
            </p>
        </div>
    </article>
</template>