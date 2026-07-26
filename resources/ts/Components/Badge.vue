<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
    defineProps<{
        variant?: 'sky' | 'teal' | 'emerald' | 'rose' | 'amber'
        size?: 'sm' | 'md'
    }>(),
    {
        variant: 'sky',
        size: 'sm',
    },
)

const variantClasses: Record<NonNullable<typeof props.variant>, string> = {
    sky: 'border-sky-400/30 bg-sky-400/10 text-sky-200',
    teal: 'border-teal-400/35 bg-teal-500/10 text-teal-100',
    emerald: 'border-emerald-400/35 bg-emerald-500/10 text-emerald-100',
    rose: 'border-rose-400/35 bg-rose-500/10 text-rose-100',
    amber: 'border-amber-400/35 bg-amber-500/10 text-amber-100',
}

const sizeClasses = {
    sm: 'px-2.5 py-1 text-xs',
    md: 'px-3 py-1 text-sm',
}

const badgeClasses = computed(() => [
    'inline-flex items-center rounded-full border font-medium leading-none',
    variantClasses[props.variant],
    sizeClasses[props.size],
])
</script>

<template>
    <span :class="badgeClasses">
        <slot />
    </span>
</template>