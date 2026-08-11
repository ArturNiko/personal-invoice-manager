<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        variant?: 'solid' | 'outline' | 'danger' | 'sky' | 'teal' | 'amber' | 'emerald' | 'rose'
        size?: 'sm' | 'md'
        block?: boolean
        type?: 'button' | 'submit' | 'reset'
        disabled?: boolean
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
    solid: 'border-white/10 bg-white text-slate-950 shadow-sm shadow-slate-950/20 hover:bg-slate-200',
    outline: 'border-white/15 bg-slate-950/55 text-slate-300 shadow-sm shadow-slate-950/15 hover:border-cyan-400/45 hover:bg-slate-900/75 hover:text-white',
    danger: 'border-red-400/30 bg-red-500/15 text-red-100 shadow-sm shadow-slate-950/15 hover:bg-red-500/25',
    sky: 'border-sky-400/30 bg-sky-400/15 text-sky-100 shadow-sm shadow-slate-950/15 hover:bg-sky-400/25',
    teal: 'border-teal-400/35 bg-teal-500/15 text-teal-50 shadow-sm shadow-slate-950/15 hover:bg-teal-500/25',
    amber: 'border-amber-400/35 bg-amber-500/15 text-amber-50 shadow-sm shadow-slate-950/15 hover:bg-amber-500/25',
    emerald: 'border-emerald-400/35 bg-emerald-500/15 text-emerald-50 shadow-sm shadow-slate-950/15 hover:bg-emerald-500/25',
    rose: 'border-rose-400/35 bg-rose-500/15 text-rose-50 shadow-sm shadow-slate-950/15 hover:bg-rose-500/25',
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
    <button
        :type="type"
        :disabled="disabled"
        :class="buttonClasses"
    >
        <slot />
    </button>
</template>