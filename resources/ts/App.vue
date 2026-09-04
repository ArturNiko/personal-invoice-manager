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

const isAuthRoute = computed(() =>
    ['/login', '/register', '/forgot-password', '/verify-email'].includes(route.path)
    || route.path.startsWith('/reset-password'),
);

const isAppLayout = computed(() => !isAuthRoute.value);

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
            invoices.value = [];
            return;
        }

        invoices.value = Array.isArray(response.data?.data) ? response.data.data : [];
    } catch (error) {
        console.error('Error fetching invoices:', error);
        invoices.value = [];

        if (axios.isAxiosError(error) && error.response?.status === 401) {
            window.location.href = '/login';
        }
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

const isCurrentRoute = (name: string) => route.name === name;
</script>

<template>
    <div v-if="isAppLayout" class="relative isolate min-h-dvh overflow-hidden bg-slate-950 text-slate-100">
        <div
            class="pointer-events-none fixed inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,_rgba(99,102,241,0.26),_transparent_35%),radial-gradient(circle_at_top_right,_rgba(16,185,129,0.18),_transparent_32%),linear-gradient(180deg,_rgba(15,23,42,1)_0%,_rgba(2,6,23,1)_100%)]"
        ></div>

        <div
            class="relative mx-auto flex min-h-dvh w-full max-w-7xl flex-col gap-6 px-2 py-5 sm:px-4 md:px-6 lg:px-8 lg:py-8"
        >
            <header
                class="rounded-[2rem] border border-white/10 bg-slate-900/60 p-3 shadow-[0_20px_50px_rgba(15,23,42,0.6)] backdrop-blur-xl"
            >
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-3 px-2 py-1">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl border border-cyan-300/30 bg-cyan-400/10 text-sm font-bold text-cyan-200">
                            PIM
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-[0.35em] text-cyan-300/80">
                                Personal
                            </p>
                            <h1 class="text-lg font-semibold tracking-tight text-white">
                                Invoice Manager
                            </h1>
                        </div>
                    </div>

                    <nav class="flex flex-wrap gap-2 md:gap-3">
                        <RouterLink
                            to="/calendar"
                            aria-label="Calendar"
                            class="group inline-flex items-center justify-center gap-2 rounded-2xl border px-3 py-3 text-sm font-semibold transition-all duration-200 sm:px-4 sm:min-w-[140px]"
                            :class="
                                isCurrentRoute('calendar')
                                    ? 'border-white/10 bg-white text-slate-950 shadow-lg shadow-slate-950/30'
                                    : 'border-white/10 bg-slate-900/80 text-slate-100 hover:border-white/20 hover:bg-slate-800/80'
                            "
                        >
                            <Icon
                                icon="calendar"
                                :theme="isCurrentRoute('calendar') ? 'light' : 'dark'"
                                class="h-5 w-5 shrink-0"
                            />
                            <span class="hidden sm:inline">Calendar</span>
                        </RouterLink>

                        <RouterLink
                            to="/list"
                            aria-label="List"
                            class="group inline-flex items-center justify-center gap-2 rounded-2xl border px-3 py-3 text-sm font-semibold transition-all duration-200 sm:px-4 sm:min-w-[140px]"
                            :class="
                                isCurrentRoute('list')
                                    ? 'border-white/10 bg-white text-slate-950 shadow-lg shadow-slate-950/30'
                                    : 'border-white/10 bg-slate-900/80 text-slate-100 hover:border-white/20 hover:bg-slate-800/80'
                            "
                        >
                            <Icon
                                icon="list"
                                :theme="isCurrentRoute('list') ? 'light' : 'dark'"
                                class="h-5 w-5 shrink-0"
                            />
                            <span class="hidden sm:inline">List</span>
                        </RouterLink>

                        <RouterLink
                            v-if="!isCreatePage"
                            to="/invoice/create"
                            aria-label="Create invoice"
                            class="group inline-flex items-center justify-center gap-2 rounded-2xl border border-cyan-400/30 bg-cyan-500/10 px-3 py-3 text-sm font-semibold text-cyan-200 shadow-lg shadow-cyan-950/30 transition-all duration-200 hover:bg-cyan-500/20 sm:px-4 sm:min-w-[140px]"
                            :class="isCurrentRoute('invoice-create') ? 'ring-2 ring-cyan-300/70 bg-cyan-400/15' : ''"
                        >
                            <Icon
                                icon="add"
                                :theme="'dark'"
                                class="h-5 w-5 shrink-0"
                            />
                            <span class="hidden sm:inline">Create</span>
                        </RouterLink>

                        <RouterLink
                            to="/profile"
                            aria-label="Profile"
                            class="group inline-flex items-center justify-center gap-2 rounded-2xl border border-violet-400/30 bg-violet-500/10 px-3 py-3 text-sm font-semibold text-violet-100 shadow-lg shadow-violet-950/30 transition-all duration-200 hover:bg-violet-500/20 sm:px-4 sm:min-w-[140px]"
                            :class="isCurrentRoute('profile') ? 'ring-1 ring-violet-300/60' : ''"
                        >
                            <Icon
                                icon="user"
                                :theme="'dark'"
                                class="h-5 w-5 shrink-0"
                            />
                            <span class="hidden sm:inline">Profile</span>
                        </RouterLink>
                    </nav>
                </div>
            </header>

            <section 
                v-show="!isCompactView && !isCurrentRoute('profile')"
                class="grid gap-4 sm:grid-cols-2" 
            >
                <article
                    v-if="isCurrentRoute('calendar') || isCurrentRoute('list')"
                    class="rounded-2xl border border-white/10 bg-slate-900/75 p-4 backdrop-blur"
                >
                    <p
                        class="text-xs uppercase tracking-[0.25em] text-slate-400"
                    >
                        View
                    </p>
                    <p class="mt-2 text-2xl font-semibold text-white">
                        {{
                            isCurrentRoute('list')
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
                        {{ (invoices ?? []).length }}
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

    <div v-else class="min-h-dvh bg-slate-950 text-slate-100">
        <RouterView v-slot="{ Component }">
            <Transition name="fade" mode="out-in">
                <component :is="Component" :key="route.path" />
            </Transition>
        </RouterView>
    </div>

    <Widget v-if="!isAuthRoute" icon="calculator">
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
