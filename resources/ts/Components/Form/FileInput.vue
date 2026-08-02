<script setup lang="ts">
import { computed } from 'vue'

import Badge from '@/Components/Badge.vue'


const props = withDefaults(
    defineProps<{
        modelValue: File | null
        label?: string
        accept?: string
        hint?: string
        buttonLabel?: string
    }>(),
    {
        label: 'Upload file',
        accept: 'application/pdf',
        hint: 'PDF',
        buttonLabel: 'Choose file',
    },
)

const emit = defineEmits<{
    'update:modelValue': [value: File | null]
}>()

const inputId = `file-input-${Math.random().toString(36).slice(2, 10)}`

const selectedName = computed(() => props.modelValue?.name ?? 'No file selected')

const onFileChange = (event: Event) => {
    const input = event.target as HTMLInputElement
    emit('update:modelValue', input.files?.[0] ?? null)
}
</script>

<template>
    <label
        :for="inputId"
        class="group block rounded-[1.4rem] border border-dashed border-white/10 bg-slate-950/60 p-4 transition hover:border-cyan-300/50 hover:bg-slate-950/75"
    >
        <input
            :id="inputId"
            type="file"
            :accept="accept"
            class="sr-only"
            @change="onFileChange"
        >

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <span class="block text-sm font-medium text-slate-200">
                    {{ label }}
                </span>
                <p class="text-sm text-slate-400">
                    {{ selectedName }}
                </p>
            </div>
            <Badge variant="sky" size="md" class="mb-auto">
                {{ hint }}
            </Badge>
        </div>

        <div class="mt-4">
            <span class="inline-flex cursor-pointer items-center rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-white transition hover:border-cyan-400/50 hover:bg-cyan-400/10">
                {{ buttonLabel }}
            </span>
        </div>
    </label>
</template>