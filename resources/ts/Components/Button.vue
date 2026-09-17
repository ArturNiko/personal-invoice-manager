<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        variant?:
            | 'solid'
            | 'outline'
            | 'danger'
            | 'sky'
            | 'teal'
            | 'amber'
            | 'emerald'
            | 'rose';
        size?: 'sm' | 'md';
        block?: boolean;
        type?: 'button' | 'submit' | 'reset';
        disabled?: boolean;
    }>(),
    {
        variant: 'solid',
        size: 'md',
        block: false,
        type: 'button',
        disabled: false,
    },
);

const variantClasses: Record<NonNullable<typeof props.variant>, string> = {
    solid: 'border-slate-900/10 bg-slate-900 text-slate-100 shadow-sm shadow-slate-500/20 hover:bg-slate-800 dark:border-white/10 dark:bg-white dark:text-slate-950 dark:shadow-black/20 dark:hover:bg-slate-200',
    outline:
        'border-slate-900/15 bg-slate-100/55 text-slate-700 shadow-sm shadow-slate-500/15 hover:border-cyan-700/45 hover:bg-white/75 hover:text-slate-900 dark:border-white/15 dark:bg-slate-950/55 dark:text-slate-300 dark:shadow-black/15 dark:hover:border-cyan-400/45 dark:hover:bg-slate-900/75 dark:hover:text-white',
    danger: 'border-red-600/30 bg-red-600/15 text-red-800 shadow-sm shadow-slate-500/15 hover:bg-red-600/25 dark:border-red-400/30 dark:bg-red-500/15 dark:text-red-100 dark:shadow-black/15 dark:hover:bg-red-500/25',
    sky: 'border-sky-600/30 bg-sky-600/15 text-sky-800 shadow-sm shadow-slate-500/15 hover:bg-sky-600/25 dark:border-sky-400/30 dark:bg-sky-400/15 dark:text-sky-100 dark:shadow-black/15 dark:hover:bg-sky-400/25',
    teal: 'border-teal-600/35 bg-teal-600/15 text-teal-900 shadow-sm shadow-slate-500/15 hover:bg-teal-600/25 dark:border-teal-400/35 dark:bg-teal-500/15 dark:text-teal-50 dark:shadow-black/15 dark:hover:bg-teal-500/25',
    amber: 'border-amber-600/35 bg-amber-700/15 text-amber-900 shadow-sm shadow-slate-500/15 hover:bg-amber-700/25 dark:border-amber-400/35 dark:bg-amber-500/15 dark:text-amber-50 dark:shadow-black/15 dark:hover:bg-amber-500/25',
    emerald:
        'border-emerald-600/35 bg-emerald-600/15 text-emerald-900 shadow-sm shadow-slate-500/15 hover:bg-emerald-600/25 dark:border-emerald-400/35 dark:bg-emerald-500/15 dark:text-emerald-50 dark:shadow-black/15 dark:hover:bg-emerald-500/25',
    rose: 'border-rose-600/35 bg-rose-700/15 text-rose-900 shadow-sm shadow-slate-500/15 hover:bg-rose-700/25 dark:border-rose-400/35 dark:bg-rose-500/15 dark:text-rose-50 dark:shadow-black/15 dark:hover:bg-rose-500/25',
};

const sizeClasses = {
    sm: 'px-3 py-1.5 text-xs',
    md: 'px-4 py-2.5 text-sm',
};

const buttonClasses = computed(() => [
    'inline-flex cursor-pointer items-center justify-center rounded-xl border font-medium tracking-wide transition duration-150 active:translate-y-px disabled:cursor-not-allowed disabled:opacity-60',
    variantClasses[props.variant],
    sizeClasses[props.size],
    props.block ? 'w-full' : '',
]);
</script>

<template>
    <button :type="type" :disabled="disabled" :class="buttonClasses">
        <slot />
    </button>
</template>
