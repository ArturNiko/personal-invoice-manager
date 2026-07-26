<script setup lang="ts">
import InputLabel from './InputLabel.vue'

const props = defineProps<{
    modelValue: string
    label: string
    required?: boolean
    disabled?: boolean
}>()

const emit = defineEmits<{
    'update:modelValue': [value: string]
}>()

const handleInput = (event: Event) => {
    emit('update:modelValue', (event.target as HTMLInputElement).value)
}
</script>

<template>
    <InputLabel :label="label">
        <input
            :value="modelValue"
            type="date"
            :required="required"
            :disabled="disabled"
            class="w-full rounded-2xl border border-white/10 bg-slate-950/50 px-4 py-3 text-white outline-none focus:border-cyan-400/50 disabled:cursor-not-allowed disabled:opacity-50"
            @input="handleInput"
        />
    </InputLabel>
</template>

<style scoped>
input[type="date"] {
    color-scheme: dark;
}

input[type="date"]::-webkit-calendar-picker-indicator {
    opacity: 0.75;
    cursor: pointer;
}
</style>
