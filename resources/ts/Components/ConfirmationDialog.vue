<script setup lang="ts">
import { onBeforeUnmount, onMounted } from 'vue';

import Button from '@/Components/Button.vue';

const props = withDefaults(
    defineProps<{
        open: boolean
        title?: string
        message?: string
        confirmLabel?: string
        cancelLabel?: string
        busyLabel?: string
        busy?: boolean
    }>(),
    {
        title: 'Delete invoice?',
        message: 'This action cannot be undone.',
        confirmLabel: 'Delete',
        cancelLabel: 'Cancel',
        busyLabel: 'Deleting...',
        busy: false,
    },
);

const emit = defineEmits<{
    close: []
    confirm: []
}>();

const handleKeydown = (event: KeyboardEvent) => {
    if (!props.open || event.key !== 'Escape') return;

    emit('close');
};

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm"
            role="presentation"
            @click.self="emit('close')"
        >
            <section
                class="w-full max-w-md rounded-[1.75rem] border border-white/10 bg-slate-900/95 p-5 shadow-2xl shadow-slate-950/60"
                role="dialog"
                aria-modal="true"
                :aria-label="title"
            >
                <p class="text-xs uppercase tracking-[0.28em] text-rose-300/80">
                    Confirmation
                </p>
                <h2 class="mt-2 text-xl font-semibold text-white">
                    {{ title }}
                </h2>
                <p class="mt-2 text-sm leading-6 text-slate-300">
                    {{ message }}
                </p>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <Button
                        variant="outline"
                        @click="emit('close')"
                    >
                        {{ cancelLabel }}
                    </Button>
                    <Button
                        variant="danger"
                        :disabled="busy"
                        @click="emit('confirm')"
                    >
                        {{ busy ? busyLabel : confirmLabel }}
                    </Button>
                </div>
            </section>
        </div>
    </Teleport>
</template>