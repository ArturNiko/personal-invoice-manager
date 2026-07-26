<script setup lang="ts">
import { onMounted, ref } from 'vue'
import axios from 'axios'
import { useRoute, RouterView } from 'vue-router'

import type { InvoiceIndexResponse, InvoiceEvent } from '@/Types/Invoice'

const invoices = ref<InvoiceEvent[]>([])
const route = useRoute()

onMounted(async () => {
    try {
        const response = await axios.get<InvoiceIndexResponse>('/invoices')

        if (response.status !== 200) {
            console.error('Failed to fetch invoices:', response.statusText)
            return
        }

        invoices.value = response.data.data
    } catch (error) {
        console.error('Error fetching invoices:', error)
    }
})

</script>

<template>
    <div class="relative min-h-screen overflow-hidden bg-slate-950 text-slate-100">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(99,102,241,0.26),_transparent_35%),radial-gradient(circle_at_top_right,_rgba(16,185,129,0.18),_transparent_32%),linear-gradient(180deg,_rgba(15,23,42,1)_0%,_rgba(2,6,23,1)_100%)]"></div>

        <div class="relative mx-auto flex min-h-screen w-full max-w-7xl flex-col gap-6 px-4 py-5 sm:px-6 lg:px-8 lg:py-8">
            <header class="flex flex-col gap-4 rounded-3xl border border-white/10 bg-white/5 p-5 shadow-2xl shadow-slate-950/40 backdrop-blur-xl lg:flex-row lg:items-center lg:justify-between">
                <div class="space-y-2">
                    <h1 class="text-2xl font-semibold uppercase tracking-[0.3em] text-cyan-300/80">Personal Invoice Manager</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base">
                        Never lose track of an invoice again.
                    </p>
                </div>

                <div class="inline-flex rounded-2xl border border-white/10 bg-slate-900/70 p-1 shadow-lg shadow-slate-950/30">
                    <RouterLink
                        to="/calendar"
                        class="rounded-xl px-4 py-2 text-sm font-medium transition"
                        :class="route.path === '/calendar' ? 'bg-white text-slate-950 shadow' : 'text-slate-300 hover:text-white'"
                    >
                        Calendar
                    </RouterLink>
                    <RouterLink
                        to="/list"
                        class="rounded-xl px-4 py-2 text-sm font-medium transition"
                        :class="route.path === '/list' ? 'bg-white text-slate-950 shadow' : 'text-slate-300 hover:text-white'"
                    >
                        List
                    </RouterLink>
                </div>
            </header>

            <section class="grid gap-4 sm:grid-cols-2">
                <article class="rounded-2xl border border-white/10 bg-slate-900/75 p-4 backdrop-blur">
                    <p class="text-xs uppercase tracking-[0.25em] text-slate-400">View</p>
                    <p class="mt-2 text-2xl font-semibold text-white">
                        {{ route.path === '/list' ? 'List View' : 'Calendar View' }}
                    </p>
                    <p class="mt-1 text-sm text-slate-400">
                        Switch between a chronological list or an interactive calendar to manage your invoices.
                    </p>
                </article>

                <article class="rounded-2xl border border-white/10 bg-slate-900/75 p-4 backdrop-blur">
                    <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Invoices</p>
                    <p class="mt-2 text-2xl font-semibold text-white">{{ invoices.length }}</p>
                    <p class="mt-1 text-sm text-slate-400">
                        Track upcoming due dates and keep all your invoices organized in one place.
                    </p>
                </article>
            </section>

            <main class="min-h-0 flex-1">
                <RouterView v-slot="{ Component }">
                    <Transition name="fade" mode="out-in">
                        <component :is="Component" :key="route.path" :invoices="invoices" />
                    </Transition>
                </RouterView>
            </main>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 180ms ease, transform 180ms ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(8px);
}
</style>