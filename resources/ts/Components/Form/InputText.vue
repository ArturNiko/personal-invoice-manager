<script setup lang="ts">
import { computed } from 'vue';

import InputLabel from './InputLabel.vue';

const props = withDefaults(
    defineProps<{
        modelValue: string;
        label: string;
        placeholder?: string;
        required?: boolean;
        disabled?: boolean;
        type?: 'text' | 'email' | 'password';
        error?: boolean;
    }>(),
    {
        placeholder: '',
        required: false,
        disabled: false,
        type: 'text',
        error: false,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const inputClass = computed(() => {
    const base = [
        'w-full rounded-xl border bg-white/80 px-3 py-2.5',
        'text-slate-900 outline-none transition focus:ring-2',
        'dark:bg-slate-900/80 dark:text-slate-100',
    ].join(' ');

    const state = props.error
        ? [
              'border-red-600/60 focus:border-red-600',
              'focus:ring-red-600/40',
              'dark:border-red-500/60 dark:focus:border-red-400',
              'dark:focus:ring-red-500/40',
          ].join(' ')
        : [
              'border-slate-900/10 focus:border-cyan-700',
              'focus:ring-cyan-700/40',
              'dark:border-white/10 dark:focus:border-cyan-400',
              'dark:focus:ring-cyan-500/40',
          ].join(' ');

    return [base, state].join(' ');
});

const handleInput = (event: Event) => {
    emit('update:modelValue', (event.target as HTMLInputElement).value);
};
</script>

<template>
    <InputLabel :label="label">
        <input
            :value="modelValue"
            :type="type"
            :placeholder="placeholder"
            :required="required"
            :disabled="disabled"
            :class="inputClass"
            @input="handleInput"
        />
    </InputLabel>
</template>
