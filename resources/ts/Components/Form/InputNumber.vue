<script setup lang="ts">
import InputLabel from './InputLabel.vue'

const props = defineProps<{
    modelValue: string
    label: string
    placeholder?: string
    required?: boolean
    disabled?: boolean
    min?: number | string
    step?: number | string
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
            type="number"
            :placeholder="placeholder"
            :required="required"
            :disabled="disabled"
            :min="min"
            :step="step"
            class="w-full rounded-2xl border border-white/10 bg-slate-950/50 px-4 py-3 text-white outline-none placeholder:text-slate-500 focus:border-cyan-400/50 disabled:cursor-not-allowed disabled:opacity-50"
            @input="handleInput"
        />
    </InputLabel>
</template>
<style scoped>
/* Chrome, Safari, Edge, Opera */
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Firefox */
input[type="number"] {
    -moz-appearance: textfield;
    appearance: textfield;
}
</style>
