<script setup lang="ts">
import InputLabel from './InputLabel.vue';

const props = defineProps<{
    modelValue: string;
    currency: string;
    label: string;
    currencyLabel?: string;
    placeholder?: string;
    required?: boolean;
    disabled?: boolean;
    min?: number | string;
    step?: number | string;
    currencyOptions: ReadonlyArray<{
        readonly label: string;
        readonly value: string;
    }>;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
    'update:currency': [value: string];
}>();

const handleAmountInput = (event: Event) => {
    emit('update:modelValue', (event.target as HTMLInputElement).value);
};

const handleCurrencyChange = (event: Event) => {
    emit('update:currency', (event.target as HTMLSelectElement).value);
};
</script>

<template>
    <InputLabel :label="label">
        <div
            class="grid grid-cols-[minmax(0,1fr)_7.5rem] overflow-hidden rounded-2xl border border-slate-900/10 bg-slate-100/50 shadow-sm shadow-slate-500/20 dark:border-white/10 dark:bg-slate-950/50 dark:shadow-black/20"
        >
            <div class="flex min-w-0 items-center">
                <input
                    :value="modelValue"
                    type="number"
                    :placeholder="placeholder"
                    :required="required"
                    :disabled="disabled"
                    :min="min"
                    :step="step"
                    class="w-full border-0 bg-transparent px-4 py-3 text-slate-900 outline-none placeholder:text-slate-500 disabled:cursor-not-allowed disabled:opacity-50 dark:text-white"
                    @input="handleAmountInput"
                />
            </div>

            <div class="border-l border-slate-900/10 bg-white/40 dark:border-white/10 dark:bg-slate-900/40">
                <span class="sr-only">{{ currencyLabel ?? 'Currency' }}</span>
                <select
                    :value="currency"
                    :disabled="disabled"
                    class="currency-select h-full w-full border-0 bg-transparent px-3 py-3 pr-9 text-slate-900 outline-none appearance-none disabled:cursor-not-allowed disabled:opacity-50 dark:text-white"
                    @change="handleCurrencyChange"
                >
                    <option
                        v-for="option in currencyOptions"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
            </div>
        </div>
    </InputLabel>
</template>
<style scoped>
/* Chrome, Safari, Edge, Opera */
input[type='number']::-webkit-inner-spin-button,
input[type='number']::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Firefox */
input[type='number'] {
    -moz-appearance: textfield;
    appearance: textfield;
}

.currency-select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%23cbd5e1' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.85rem center;
    background-size: 1rem 1rem;
}

html.light .currency-select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%23475569' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3E%3C/svg%3E");
}
</style>
