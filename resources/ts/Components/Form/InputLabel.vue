<script setup lang="ts">
defineProps<{
    label: string;
}>();
</script>

<template>
    <label class="space-y-3">
        <span
            class="block text-base font-semibold tracking-tight text-slate-200 pl-2"
            >{{ label }}</span
        >
        <slot />
    </label>
</template>
