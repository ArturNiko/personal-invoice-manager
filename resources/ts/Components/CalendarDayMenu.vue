<script setup lang="ts">
defineProps<{
    date: string;
    style: Record<string, string>;
}>();

const emit = defineEmits<{
    create: [];
    close: [];
}>();
</script>

<template>
    <div
        class="calendar-day-menu fixed z-50 w-64 rounded-2xl border border-white/10 bg-slate-950/95 p-3 shadow-2xl shadow-slate-950/50 backdrop-blur-xl"
        :style="style"
        @click.stop
    >
        <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Selected day</p>
        <p class="mt-1 text-sm font-semibold text-white">{{ date }}</p>

        <button
            type="button"
            class="mt-3 w-full rounded-xl bg-cyan-400 px-3 py-2 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300"
            @click.stop="emit('create')"
        >
            Create invoice for this day
        </button>

        <p class="mt-2 text-xs leading-5 text-slate-400">
            More day actions might be available in future updates.
        </p>
    </div>
</template>