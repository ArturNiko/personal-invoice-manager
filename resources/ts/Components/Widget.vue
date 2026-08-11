<script setup lang="ts">
import { ref } from 'vue';

import Icon from '@/Components/Icon.vue';


const props = defineProps<{
    icon: string
}>();

const state = ref({
    isOpen: false,
});

const toggleWidget = () => state.value.isOpen = !state.value.isOpen;
const closeWidget = () => state.value.isOpen = false;
</script>

<template>
    <button
        v-if="!state.isOpen"
        class="fixed bottom-4 right-4 z-50 inline-flex h-14 w-14 items-center justify-center rounded-full border border-cyan-400/20 bg-slate-900/85 text-cyan-200 shadow-[0_20px_45px_-18px_rgba(6,182,212,0.35)] backdrop-blur-xl transition duration-200 hover:-translate-y-0.5 hover:bg-slate-800/90 hover:text-white focus:outline-none focus:ring-2 focus:ring-cyan-400/60 focus:ring-offset-2 focus:ring-offset-transparent"
        type="button"
        @click="toggleWidget"
    >
        <Icon
            :icon="icon"
            theme="dark"
        />
    </button>

    <Teleport
        v-if="state.isOpen"
        to="body"
    >
        <div class="fixed inset-0 z-40 flex items-center justify-center  p-2 backdrop-blur-sm sm:p-4">
            <div
                class="z-50 flex max-h-[calc(100dvh-1rem)] w-full max-w-[min(100vw-1rem,26rem)] flex-col overflow-hidden rounded-[1.75rem] border border-white/10 bg-slate-900/90 shadow-[0_24px_70px_-24px_rgba(2,6,23,0.9)] backdrop-blur-xl sm:max-h-[calc(100dvh-2rem)] sm:max-w-[min(92vw,26rem)]"
                @click.stop
            >
                <div class="flex items-center justify-between border-b border-white/10 bg-slate-950/40 px-4 py-3">
                    <div>
                        <p class="text-xs uppercase tracking-[0.28em] text-cyan-300/70">Widget</p>
                        <p class="mt-1 text-sm font-medium text-white">Invoice calculator</p>
                    </div>

                    <button
                        type="button"
                        class="rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-2 text-xs font-medium text-cyan-200 transition hover:bg-cyan-400/20 hover:text-white"
                        @click="closeWidget"
                    >
                        Close
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-4">
                    <slot />
                </div>
            </div>

            <button
                type="button"
                class="absolute inset-0 h-full w-full cursor-default"
                aria-label="Close widget backdrop"
                @click="closeWidget"
            />
        </div>
    </Teleport>
</template>