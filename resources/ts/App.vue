<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useRoute, RouterLink, RouterView } from 'vue-router';

import Icon from '@/Components/Icon.vue';
import Widget from '@/Components/Widget.vue';
import Calculator from '@/Widgets/Calculator.vue';
import { loadAppSettings } from '@/Composables/useAppSettings';
import { useTheme } from '@/Composables/useTheme';
import { locale, setLocale, t } from '@/i18n';

type NavItem = {
    to: string;
    routeName: string;
    labelKey: string;
    icon: string;
};

const navItems: NavItem[] = [
    {
        to: '/dashboard',
        routeName: 'dashboard',
        labelKey: 'app.navigation.dashboard',
        icon: 'dashboard',
    },
    {
        to: '/list',
        routeName: 'list',
        labelKey: 'app.navigation.list',
        icon: 'list',
    },
    {
        to: '/profile',
        routeName: 'profile',
        labelKey: 'app.navigation.profile',
        icon: 'user',
    },
];

const route = useRoute();
const { theme, toggleTheme } = useTheme();
const isCompactView = ref(false);
const isMenuOpen = ref(false);
const isDarkTheme = computed(() => theme.value === 'dark');

watch(
    () => route.path,
    () => {
        isMenuOpen.value = false;
    },
);

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
    isCompactView.value = window.innerWidth < 1024;
};

const isCreatePage = computed(() => route.path === '/invoice/create');

const onMenuKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') isMenuOpen.value = false;
};

onMounted(() => {
    void loadAppSettings();
    updateCalendarView();
    window.addEventListener('resize', updateCalendarView);
    window.addEventListener('keydown', onMenuKeydown);
});

onUnmounted(() => {
    window.removeEventListener('resize', updateCalendarView);
    window.removeEventListener('keydown', onMenuKeydown);
});

const isCurrentRoute = (name: string) => route.name === name;
const languageOptions = ['en', 'de'] as const;
</script>

<template>
    <div
        v-if="isAppLayout"
        class="relative isolate min-h-dvh overflow-hidden bg-[var(--app-bg)] text-slate-800 dark:text-slate-100"
    >
        <div
            class="app-aurora pointer-events-none fixed inset-0 -z-10"
        ></div>

        <div
            class="relative mx-auto flex min-h-dvh w-full max-w-7xl flex-col gap-3 px-2 py-4 sm:gap-6 sm:px-4 sm:py-5 md:px-6 lg:px-8 lg:py-8"
        >
            <header
                class="rounded-[1.75rem] border border-slate-900/10 bg-white/60 p-2.5 shadow-[0_12px_24px_-18px_rgba(15,23,42,0.25)] backdrop-blur-xl dark:border-white/10 dark:bg-slate-900/60 sm:p-3"
            >
                <div class="flex items-center justify-between gap-2">
                    <RouterLink
                        to="/dashboard"
                        class="group flex min-w-0 items-center gap-2.5 transition-opacity hover:opacity-90"
                    >
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-cyan-700/40 bg-cyan-700/10 text-xs font-black tracking-tight text-cyan-700 shadow-[inset_0_1px_0_rgba(15,23,42,0.06)] transition group-hover:border-cyan-700/60 dark:border-cyan-300/40 dark:bg-cyan-400/10 dark:text-cyan-200 dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.08)] dark:group-hover:border-cyan-300/60"
                        >
                            PIM
                        </div>
                        <div class="min-w-0">
                            <p
                                class="hidden text-[0.55rem] font-semibold uppercase tracking-[0.34em] text-cyan-700/80 dark:text-cyan-300/80 sm:block"
                            >
                                {{ t('app.personal') }}
                            </p>
                            <h1
                                class="truncate text-base font-bold leading-tight tracking-[-0.05em] text-slate-900 dark:text-white sm:text-lg"
                            >
                                {{ t('app.invoiceManager') }}
                            </h1>
                        </div>
                    </RouterLink>

                    <nav
                        aria-label="Primary"
                        class="hidden items-center gap-1 rounded-2xl border border-slate-900/10 bg-slate-100/50 p-1 dark:border-white/10 dark:bg-slate-950/50 lg:flex"
                    >
                        <RouterLink
                            v-for="item in navItems"
                            :key="item.routeName"
                            :to="item.to"
                            :aria-label="t(item.labelKey)"
                            class="flex h-10 items-center gap-2 rounded-xl px-3.5 text-sm font-semibold transition-all duration-200"
                            :class="
                                isCurrentRoute(item.routeName)
                                    ? 'bg-slate-900 text-slate-100 shadow-sm shadow-slate-500/20 dark:bg-white dark:text-slate-950 dark:shadow-black/20'
                                    : 'text-slate-700 hover:bg-slate-900/5 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-white/5 dark:hover:text-white'
                            "
                        >
                            <Icon
                                :icon="item.icon"
                                :theme="
                                    isCurrentRoute(item.routeName)
                                        ? 'light'
                                        : 'dark'
                                "
                                class="h-4 w-4 shrink-0"
                            />
                            {{ t(item.labelKey) }}
                        </RouterLink>

                        <template v-if="!isCreatePage">
                            <div class="mx-1 h-6 w-px bg-slate-900/10 dark:bg-white/10"></div>

                            <RouterLink
                                v-if="!isCreatePage"
                                to="/invoice/create"
                                :aria-label="t('app.navigation.createInvoice')"
                                class="flex h-10 items-center gap-2 rounded-xl border border-cyan-700/30 bg-cyan-500/15 px-3.5 text-sm font-semibold text-cyan-800 transition-all duration-200 hover:bg-cyan-500/25 hover:text-slate-900 dark:border-cyan-400/30 dark:text-cyan-100 dark:hover:text-white"
                            >
                                <Icon
                                    icon="add"
                                    theme="dark"
                                    class="h-4 w-4 shrink-0"
                                />
                                {{ t('app.navigation.create') }}
                            </RouterLink>
                        </template>
                    </nav>

                    <div
                        class="flex shrink-0 items-center justify-end gap-1.5"
                    >
                        <div
                            class="flex items-center gap-1 rounded-xl border border-slate-900/10 bg-slate-100/60 p-1 dark:border-white/10 dark:bg-slate-950/60"
                        >
                            <button
                                v-for="language in languageOptions"
                                :key="language"
                                type="button"
                                class="flex h-9 min-w-[3.1rem] items-center justify-center rounded-lg px-2 text-[0.7rem] font-bold uppercase tracking-[0.18em] transition"
                                :class="
                                    locale === language
                                        ? 'bg-slate-900 text-slate-100 shadow-sm shadow-slate-100/20 dark:bg-white dark:text-slate-950 dark:shadow-slate-950/20'
                                        : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
                                "
                                @click="setLocale(language)"
                            >
                                {{ language }}
                            </button>
                        </div>

                        <button
                            type="button"
                            :aria-label="
                                theme === 'dark'
                                    ? t('app.theme.toLight')
                                    : t('app.theme.toDark')
                            "
                            class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-900/10 bg-slate-100/60 text-slate-800 transition hover:bg-slate-900/5 hover:text-slate-900 shadow-[0_8px_20px_-12px_rgba(15,23,42,0.12)] dark:border-white/10 dark:bg-slate-950/60 dark:text-slate-200 dark:hover:bg-white/5 dark:hover:text-white dark:shadow-[0_8px_20px_-12px_rgba(15,23,42,0.8)]"
                            @click="toggleTheme"
                        >
                            <Icon
                                :icon="isDarkTheme ? 'sun' : 'moon'"
                                theme="dark"
                                class="h-4 w-4"
                            />
                        </button>

                        <button
                            type="button"
                            :aria-label="
                                isMenuOpen
                                    ? t('app.navigation.closeMenu')
                                    : t('app.navigation.openMenu')
                            "
                            :aria-expanded="isMenuOpen"
                            aria-haspopup="true"
                            class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-900/10 bg-slate-100/60 text-slate-700 transition hover:bg-slate-900/5 hover:text-slate-900 dark:border-white/10 dark:bg-slate-950/60 dark:text-slate-300 dark:hover:bg-white/5 dark:hover:text-white lg:hidden"
                            @click="isMenuOpen = !isMenuOpen"
                        >
                            <Icon
                                :icon="isMenuOpen ? 'close' : 'menu'"
                                theme="dark"
                                class="h-5 w-5"
                            />
                        </button>
                    </div>
                </div>

                <Transition name="menu">
                    <nav
                        v-if="isMenuOpen"
                        aria-label="Primary"
                        class="mt-2.5 flex flex-col gap-1 rounded-[1.2rem] border border-slate-900/10 bg-slate-100/60 p-1.5 dark:border-white/10 dark:bg-slate-950/60 lg:hidden"
                    >
                        <RouterLink
                            v-for="item in navItems"
                            :key="item.routeName"
                            :to="item.to"
                            :aria-label="t(item.labelKey)"
                            class="flex h-12 items-center gap-3 rounded-xl px-3 transition-all duration-200"
                            :class="
                                isCurrentRoute(item.routeName)
                                    ? 'bg-slate-900 text-slate-100 shadow-sm shadow-slate-500/30 dark:bg-white dark:text-slate-950 dark:shadow-black/30'
                                    : 'text-slate-700 hover:bg-slate-900/5 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-white/5 dark:hover:text-white'
                            "
                        >
                            <Icon
                                :icon="item.icon"
                                :theme="
                                    isCurrentRoute(item.routeName)
                                        ? 'light'
                                        : 'dark'
                                "
                                class="h-5 w-5 shrink-0"
                            />
                            <span class="text-sm font-semibold">
                                {{ t(item.labelKey) }}
                            </span>
                            <span
                                v-if="isCurrentRoute(item.routeName)"
                                class="ml-auto h-1.5 w-1.5 rounded-full bg-sky-500"
                            ></span>
                        </RouterLink>

                        <template v-if="!isCreatePage">
                            <div class="mx-2 h-px bg-slate-900/10 dark:bg-white/10"></div>

                            <RouterLink
                                to="/invoice/create"
                                :aria-label="t('app.navigation.createInvoice')"
                                class="flex h-12 items-center gap-3 rounded-xl border border-cyan-700/30 bg-cyan-500/15 px-3 text-cyan-800 transition-all duration-200 hover:bg-cyan-500/25 hover:text-slate-900 dark:border-cyan-400/30 dark:text-cyan-100 dark:hover:text-white"
                            >
                                <Icon
                                    icon="add"
                                    theme="dark"
                                    class="h-5 w-5 shrink-0"
                                />
                                <span class="text-sm font-semibold">
                                    {{ t('app.navigation.create') }}
                                </span>
                            </RouterLink>
                        </template>
                    </nav>
                </Transition>
            </header>

            <section
                v-if="showVerificationReminder"
                class="rounded-2xl border border-red-300/80 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200"
            >
                {{ t('app.verificationReminder') }}
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

    <div
        v-else-if="isAuthRoute"
        class="min-h-dvh bg-slate-100 text-slate-900 dark:bg-slate-950 dark:text-slate-100"
    >
        <div class="absolute right-4 top-4 z-20">
            <div
                class="flex items-center gap-1 rounded-xl border border-slate-900/10 bg-white/80 p-1 backdrop-blur-xl dark:border-white/10 dark:bg-slate-900/80"
            >
                <button
                    v-for="language in languageOptions"
                    :key="language"
                    type="button"
                    class="rounded-lg px-2.5 py-1.5 text-xs font-semibold uppercase tracking-[0.18em] transition"
                    :class="
                        locale === language
                            ? 'bg-slate-900 text-slate-100 shadow-sm dark:bg-white dark:text-slate-950'
                            : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
                    "
                    @click="setLocale(language)"
                >
                    {{ language }}
                </button>

                <button
                    type="button"
                    :aria-label="
                        theme === 'dark'
                            ? t('app.theme.toLight')
                            : t('app.theme.toDark')
                    "
                    class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 transition hover:text-slate-900 dark:text-slate-400 dark:hover:text-white"
                    @click="toggleTheme"
                >
                    <Icon
                        :icon="theme === 'dark' ? 'sun' : 'moon'"
                        :theme="theme === 'dark' ? 'dark' : 'light'"
                        class="h-4 w-4"
                    />
                </button>
            </div>
        </div>

        <RouterView v-slot="{ Component }">
            <Transition name="fade" mode="out-in">
                <component :is="Component" :key="route.path" />
            </Transition>
        </RouterView>
    </div>

    <div
        v-else
        class="min-h-dvh bg-slate-100 text-slate-900 dark:bg-slate-950 dark:text-slate-100"
    >
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

.menu-enter-active,
.menu-leave-active {
    transform-origin: top;
    transition:
        opacity 160ms ease,
        transform 160ms ease;
}

.menu-enter-from,
.menu-leave-to {
    opacity: 0;
    transform: translateY(-6px) scaleY(0.98);
}
</style>