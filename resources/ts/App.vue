<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useRoute, RouterLink, RouterView } from 'vue-router';

import Icon from '@/Components/Icon.vue';
import Widget from '@/Components/Widget.vue';
import Calculator from '@/Widgets/Calculator.vue';
import { useInvoices } from '@/Composables/useInvoices';
import { loadAppSettings } from '@/Composables/useAppSettings';

const route = useRoute();
const isCompactView = ref(false);
const { totalAmountDisplay } = useInvoices();

const isAuthRoute = computed(
    () =>
        ['/login', '/register', '/forgot-password', '/verify-email'].includes(
            route.path,
        ) || route.path.startsWith('/reset-password'),
);

const isNotFoundRoute = computed(() => route.name === 'fallback');

const isAppLayout = computed(
    () => !isAuthRoute.value && !isNotFoundRoute.value,
);
const showVerificationReminder = computed(
    () => route.query.verification === 'needed',
);

const updateCalendarView = () => {
    isCompactView.value = window.innerWidth < 768;
};

const isCreatePage = computed(() => route.path === '/invoice/create');

onMounted(() => {
    void loadAppSettings();
    updateCalendarView();
    window.addEventListener('resize', updateCalendarView);
});

onUnmounted(() => {
    window.removeEventListener('resize', updateCalendarView);
});

const isCurrentRoute = (name: string) => route.name === name;
</script>

<template>
    <div
        v-if="isAppLayout"
        class="relative isolate min-h-dvh overflow-hidden bg-slate-950 text-slate-100"
    >
        <div
            class="pointer-events-none fixed inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,_rgba(99,102,241,0.26),_transparent_35%),radial-gradient(circle_at_top_right,_rgba(16,185,129,0.18),_transparent_32%),linear-gradient(180deg,_rgba(15,23,42,1)_0%,_rgba(2,6,23,1)_100%)]"
        ></div>

        <div
            class="relative mx-auto flex min-h-dvh w-full max-w-7xl flex-col gap-3 px-2 py-4 sm:gap-6 sm:px-4 sm:py-5 md:px-6 lg:px-8 lg:py-8"
        >
            <header
                class="rounded-[2rem] border border-white/10 bg-slate-900/60 p-3 shadow-[0_20px_50px_rgba(15,23,42,0.6)] backdrop-blur-xl"
            >
                <div
                    class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                >
                    <RouterLink
                        to="/dashboard"
                        class="flex items-center gap-3 px-2 py-1 transition-opacity hover:opacity-90"
                    >
                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-2xl border border-cyan-300/30 bg-cyan-400/10 text-sm font-bold text-cyan-200"
                        >
                            PIM
                        </div>
                        <div>
                            <p
                                class="text-[10px] font-semibold uppercase tracking-[0.35em] text-cyan-300/80"
                            >
                                Personal
                            </p>
                            <h1
                                class="text-lg font-semibold tracking-tight text-white"
                            >
                                Invoice Manager
                            </h1>
                        </div>
                    </RouterLink>

                    <nav
                        class="flex items-center gap-2 overflow-x-auto rounded-2xl border border-white/10 bg-slate-950/60 p-1.5 backdrop-blur-xl scrollbar-none lg:justify-center"
                    >
                        <RouterLink
                            to="/list"
                            aria-label="List"
                            class="group inline-flex items-center justify-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200 md:min-w-[112px]"
                            :class="
                                isCurrentRoute('list')
                                    ? 'bg-white text-slate-950 shadow-sm shadow-slate-950/30'
                                    : 'text-slate-300 hover:bg-white/5 hover:text-white'
                            "
                        >
                            <Icon
                                icon="list"
                                :theme="
                                    isCurrentRoute('list') ? 'light' : 'dark'
                                "
                                class="h-4 w-4 shrink-0 md:h-5 md:w-5"
                            />
                            <span class="hidden md:inline">List</span>
                        </RouterLink>

                        <RouterLink
                            v-if="!isCreatePage"
                            to="/invoice/create"
                            aria-label="Create invoice"
                            class="group inline-flex items-center justify-center gap-2 rounded-xl border border-cyan-400/30 bg-cyan-500/10 px-3 py-2.5 text-sm font-medium text-cyan-100 transition-all duration-200 hover:bg-cyan-500/20 md:min-w-[120px]"
                            :class="
                                isCurrentRoute('invoice-create')
                                    ? 'ring-1 ring-cyan-300/60 bg-cyan-500/15'
                                    : ''
                            "
                        >
                            <Icon
                                icon="add"
                                :theme="'dark'"
                                class="h-4 w-4 shrink-0 md:h-5 md:w-5"
                            />
                            <span class="hidden md:inline">Create</span>
                        </RouterLink>

                        <RouterLink
                            to="/profile"
                            aria-label="Profile"
                            class="group inline-flex items-center justify-center gap-2 rounded-xl border border-violet-400/30 bg-violet-500/10 px-3 py-2.5 text-sm font-medium text-violet-50 transition-all duration-200 hover:bg-violet-500/20 md:min-w-[120px]"
                            :class="
                                isCurrentRoute('profile')
                                    ? 'ring-1 ring-violet-300/60 bg-violet-500/15'
                                    : ''
                            "
                        >
                            <Icon
                                icon="user"
                                :theme="'dark'"
                                class="h-4 w-4 shrink-0 md:h-5 md:w-5"
                            />
                            <span class="hidden md:inline">Profile</span>
                        </RouterLink>
                    </nav>
                </div>
            </header>

            <section
                v-if="showVerificationReminder"
                class="rounded-2xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200"
            >
                Your email address is not verified yet. You can still use the
                app, but please open your profile to resend the verification
                email.
            </section>

            <main class="min-h-0 flex-1">
                <RouterView v-slot="{ Component }">
                    <Transition name="fade" mode="out-in">
                        <component :is="Component" :key="route.path" />
                    </Transition>
                </RouterView>
            </main>
        </div>
    </div>

    <div v-else-if="isAuthRoute" class="min-h-dvh bg-slate-950 text-slate-100">
        <RouterView v-slot="{ Component }">
            <Transition name="fade" mode="out-in">
                <component :is="Component" :key="route.path" />
            </Transition>
        </RouterView>
    </div>

    <div v-else class="min-h-dvh bg-slate-950 text-slate-100">
        <RouterView v-slot="{ Component }">
            <Transition name="fade" mode="out-in">
                <component :is="Component" :key="route.path" />
            </Transition>
        </RouterView>
    </div>

    <Widget v-if="!isAuthRoute && !isNotFoundRoute" icon="calculator">
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
