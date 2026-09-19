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
    default: 'border-slate-900/10 bg-white/75 text-slate-900 dark:border-white/10 dark:bg-slate-900/75 dark:text-white',
    sky: 'border-sky-600/25 bg-sky-600/10 text-sky-800 dark:border-sky-400/25 dark:bg-sky-500/10 dark:text-sky-100',
    teal: 'border-teal-600/25 bg-teal-600/10 text-teal-800 dark:border-teal-400/25 dark:bg-teal-500/10 dark:text-teal-100',
    rose: 'border-rose-600/25 bg-rose-700/10 text-rose-800 dark:border-rose-400/25 dark:bg-rose-500/10 dark:text-rose-100',
    amber: 'border-amber-600/25 bg-amber-700/10 text-amber-800 dark:border-amber-400/25 dark:bg-amber-500/10 dark:text-amber-100',
};

const labelClasses: Record<NonNullable<typeof props.tone>, string> = {
    default: 'text-slate-600 dark:text-slate-400',
    sky: 'text-sky-700/70 dark:text-sky-300/70',
    teal: 'text-teal-700/70 dark:text-teal-300/70',
    rose: 'text-rose-700/70 dark:text-rose-300/70',
    amber: 'text-amber-700/70 dark:text-amber-300/70',
};

const hintClasses: Record<NonNullable<typeof props.tone>, string> = {
    default: 'text-slate-600 dark:text-slate-400',
    sky: 'text-sky-700/60 dark:text-sky-300/60',
    teal: 'text-teal-700/60 dark:text-teal-300/60',
    rose: 'text-rose-700/60 dark:text-rose-300/60',
    amber: 'text-amber-700/60 dark:text-amber-300/60',
};

</script>

<template>
    <article 
        class="flex flex-col gap-2 rounded-2xl border p-2 backdrop-blur sm:gap-4 sm:p-4" 
        :class="toneClasses[tone]"
    >
        <div class="flex items-start gap-3">
            <p
                :class="[
                    'text-[9px] font-semibold uppercase tracking-[0.1em] sm:text-xs sm:tracking-[0.22em]',
                    labelClasses[tone],
                ]"
            >
                {{ label }}
            </p>
            <div 
                v-if="icon" 
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border sm:h-10 sm:w-10 hidden sm:flex"
                :class="toneClasses[tone]"
            >
                <Icon
                    :icon="icon"
                    theme="dark"
                    class="h-5 w-5"
                />
            </div>
        </div>

        <div>
            <p
                class="text-base font-semibold text-slate-900 sm:text-2xl lg:text-3xl dark:text-white"
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