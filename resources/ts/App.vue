<script setup lang="ts">
import axios from 'axios'
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, RouterLink, RouterView } from 'vue-router';

import Icon from '@/Components/Icon.vue';
import Widget from '@/Components/Widget.vue';
import Calculator from '@/Widgets/Calculator.vue';

import type { InvoiceIndexResponse, InvoiceEvent } from '@/Types/Invoice';


const invoices = ref<InvoiceEvent[]>([]);
const route = useRoute();
const isCompactView = ref(false);

const invoiceQuery = computed(() => ({
    q: typeof route.query.q === 'string' ? route.query.q : undefined,
    type: typeof route.query.type === 'string' ? route.query.type : undefined,
    sort: typeof route.query.sort === 'string' ? route.query.sort : undefined,
    direction:
        typeof route.query.direction === 'string'
            ? route.query.direction
            : undefined,
    status:
        typeof route.query.status === 'string' ? route.query.status : undefined,
    recurrence:
        typeof route.query.recurrence === 'string'
            ? route.query.recurrence
            : undefined,
    per_page:
        typeof route.query.per_page === 'string'
            ? route.query.per_page
            : undefined,
}));

const fetchInvoices = async () => {
    try {
        const response = await axios.get<InvoiceIndexResponse>('/invoices', {
            params: invoiceQuery.value,
        });

        if (response.status !== 200) {
            console.error('Failed to fetch invoices:', response.statusText);
            return;
        }

        invoices.value = response.data.data;
    } catch (error) {
        console.error('Error fetching invoices:', error);
    }
};

const updateCalendarView = () => {
    isCompactView.value = window.innerWidth < 768;
};

const isCreatePage = computed(() => route.path === '/create');

onMounted(async () => {
    await fetchInvoices();
    updateCalendarView();
    window.addEventListener('resize', updateCalendarView);
})

watch(
    () => route.fullPath,
    async () => await fetchInvoices(),
);

const isCurrentRoute = (path: string) => route.path === path;
</script>

<template>
    <div
        class="relative isolate min-h-dvh overflow-hidden bg-slate-950 text-slate-100"
    >
        <div
            class="pointer-events-none fixed inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,_rgba(99,102,241,0.26),_transparent_35%),radial-gradient(circle_at_top_right,_rgba(16,185,129,0.18),_transparent_32%),linear-gradient(180deg,_rgba(15,23,42,1)_0%,_rgba(2,6,23,1)_100%)]"
        ></div>

        <div
            class="relative mx-auto flex min-h-dvh w-full max-w-7xl flex-col gap-6 px-2 py-5 sm:px-4 md:px-6 lg:px-8 lg:py-8"
        >
            <header
                class="flex flex-col gap-4 rounded-3xl border border-white/10 bg-white/5 p-5 shadow-2xl shadow-slate-950/40 backdrop-blur-xl lg:flex-row lg:items-center lg:justify-between"
            >
                <div class="space-y-2">
                    <h1
                        class="text-2xl font-semibold uppercase tracking-[0.3em] text-cyan-300/80"
                    >
                        Personal Invoice Manager
                    </h1>
                    <p
                        class="mt-2 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base"
                    >
                        Never lose track of an invoice again.
                    </p>
                </div>

                <div class="flex flex-row flex-nowrap items-center gap-2 sm:gap-3">
                    <div
                        class="inline-flex shrink-0 rounded-2xl border border-white/10 bg-slate-900/70 p-1 shadow-lg shadow-slate-950/30"
                    >
                        <RouterLink
                            to="/calendar"
                            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl px-0 text-sm font-medium transition sm:w-auto sm:px-4"
                            :class="
                                isCurrentRoute('/calendar')
                                    ? 'bg-white text-slate-950 shadow'
                                    : 'text-slate-300 hover:text-white'
                            "
                        >
                            <Icon
                                icon="calendar"
                                :theme="isCurrentRoute('/calendar') ? 'light' : 'dark'"
                            />
                            <span class="sr-only sm:not-sr-only sm:ml-2">Calendar</span>
                        </RouterLink>
                        <RouterLink
                            to="/list"
                            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl px-0 text-sm font-medium transition sm:w-auto sm:px-4"
                            :class="
                                isCurrentRoute('/list')
                                    ? 'bg-white text-slate-950 shadow'
                                    : 'text-slate-300 hover:text-white'
                            "
                        >
                            <Icon
                                icon="list"
                                :theme="isCurrentRoute('/list') ? 'light' : 'dark'"
                            />
                            <span class="sr-only sm:not-sr-only sm:ml-2">List</span>
                        </RouterLink>
                    </div>

                    <RouterLink
                        v-if="!isCreatePage"
                        to="/create"
                        class="inline-flex h-11 w-11 shrink-0 items-center justify-center gap-2 rounded-2xl border border-cyan-400/20 bg-cyan-400/10 px-0 text-sm font-semibold text-cyan-200 shadow-lg shadow-slate-950/25 transition hover:-translate-y-0.5 hover:bg-cyan-400/20 hover:text-white sm:w-auto sm:px-4"
                    >
                        <Icon
                            icon="add"
                            :theme="isCurrentRoute('/create') ? 'light' : 'dark'"
                        />
                        <span class="sr-only sm:not-sr-only">Create</span>
                    </RouterLink>
                </div>
            </header>

            <section 
                v-show="!isCompactView"
                class="grid gap-4 sm:grid-cols-2" 
            >
                <article
                    v-if="route.path === '/calendar' || route.path === '/list'"
                    class="rounded-2xl border border-white/10 bg-slate-900/75 p-4 backdrop-blur"
                >
                    <p
                        class="text-xs uppercase tracking-[0.25em] text-slate-400"
                    >
                        View
                    </p>
                    <p class="mt-2 text-2xl font-semibold text-white">
                        {{
                            route.path === "/list"
                                ? "List View"
                                : "Calendar View"
                        }}
                    </p>
                    <p class="mt-1 text-sm text-slate-400">
                        Switch between a chronological list or an interactive
                        calendar to manage your invoices.
                    </p>
                </article>

                <article
                    class="rounded-2xl border border-white/10 bg-slate-900/75 p-4 backdrop-blur"
                >
                    <p
                        class="text-xs uppercase tracking-[0.25em] text-slate-400"
                    >
                        Invoices
                    </p>
                    <p class="mt-2 text-2xl font-semibold text-white">
                        {{ invoices.length }}
                    </p>
                    <p class="mt-1 text-sm text-slate-400">
                        Track upcoming due dates and keep all your invoices
                        organized in one place.
                    </p>
                </article>
            </section>

            <main class="min-h-0 flex-1">
                <RouterView v-slot="{ Component }">
                    <Transition
                        name="fade"
                        mode="out-in"
                    >
                        <component
                            :is="Component"
                            :key="route.path"
                            :invoices="invoices"
                        />
                    </Transition>
                </RouterView>
            </main>
        </div>
    </div>
    <Widget icon="calculator">
        <Calculator />
    </Widget>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition:
        opacity 180ms ease,
        transform 180ms ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(8px);
}
</style>
