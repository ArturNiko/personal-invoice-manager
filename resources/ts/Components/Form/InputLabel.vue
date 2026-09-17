<script setup lang="ts">
defineProps<{
    label: string;
}>();
</script>

<template>
    <label class="space-y-3">
        <span
            class="block text-base font-semibold tracking-tight text-slate-800 pl-2 dark:text-slate-200"
            >{{ label }}</span
        >
        <slot />
    </label>
</template>
