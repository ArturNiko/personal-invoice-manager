<script setup lang="ts">
import InputLabel from "./InputLabel.vue";

const props = defineProps<{
    modelValue: string;
    label: string;
    options: ReadonlyArray<{
        readonly label: string;
        readonly value: string;
    }>;
    disabled?: boolean;
    required?: boolean;
}>();

const emit = defineEmits<{
    "update:modelValue": [value: string];
}>();

const handleChange = (event: Event) => {
    emit("update:modelValue", (event.target as HTMLSelectElement).value);
};
</script>

<template>
    <InputLabel :label="label">
        <select
            :value="modelValue"
            :disabled="disabled"
            :required="required"
            class="input-select w-full rounded-2xl border border-white/10 bg-slate-950/50 px-4 py-3 pr-11 text-white outline-none appearance-none focus:border-cyan-400/50 disabled:cursor-not-allowed disabled:opacity-50"
            @change="handleChange"
        >
            <option
                v-for="option in props.options"
                :key="option.value"
                :value="option.value"
            >
                {{ option.label }}
            </option>
        </select>
    </InputLabel>
</template>

<style scoped>
.input-select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%23cbd5e1' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.85rem center;
    background-size: 1rem 1rem;
}
</style>
