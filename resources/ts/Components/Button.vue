<script setup lang="ts">
import { computed } from 'vue'

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
)

const variantClasses: Record<NonNullable<typeof props.variant>, string> = {
    solid: 'border-white/10 bg-white text-slate-950 hover:bg-slate-200',
    outline: 'border-white/10 bg-slate-950/40 text-slate-300 hover:border-cyan-400/40 hover:text-white',
    danger: 'border-red-400/30 bg-red-500/10 text-red-100 hover:bg-red-500/20',
    sky: 'border-sky-400/30 bg-sky-400/10 text-sky-200 hover:bg-sky-400/15',
    teal: 'border-teal-400/35 bg-teal-500/10 text-teal-100 hover:bg-teal-500/15',
    amber: 'border-amber-400/35 bg-amber-500/10 text-amber-100 hover:bg-amber-500/15',
    emerald: 'border-emerald-400/35 bg-emerald-500/10 text-emerald-100 hover:bg-emerald-500/15',
    rose: 'border-rose-400/35 bg-rose-500/10 text-rose-100 hover:bg-rose-500/15',
}

const sizeClasses = {
    sm: 'px-2.5 py-1 text-xs',
    md: 'px-4 py-3 text-sm',
}

const buttonClasses = computed(() => [
    'inline-flex cursor-pointer items-center justify-center rounded-full border font-medium transition disabled:cursor-not-allowed disabled:opacity-60',
    variantClasses[props.variant],
    sizeClasses[props.size],
    props.block ? 'w-full' : '',
])
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