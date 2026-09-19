<script setup lang="ts">
import axios from 'axios';
import { computed, ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';

import Button from '@/Components/Button.vue';
import InputSelect from '@/Components/Form/InputSelect.vue';
import InputText from '@/Components/Form/InputText.vue';
import { updateAppCurrency } from '@/Composables/useAppSettings';
import { t } from '@/i18n';
import { currencyOptions } from '@/Utils/Consts';

const router = useRouter();
const csrfToken =
    document.head
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content') ?? '';

const user = ref({ name: '', email: '', currency: 'EUR', email_verified: true });

const name = ref('');
const email = ref('');
const currency = ref('EUR');
const currentPassword = ref('');
const newPassword = ref('');
const newPasswordConfirmation = ref('');
const deletePassword = ref('');

const profileErrors = ref<Record<string, string[]>>({});
const profileSuccess = ref('');
const profileLoading = ref(false);

const preferencesErrors = ref<Record<string, string[]>>({});
const preferencesSuccess = ref('');
const preferencesLoading = ref(false);

const passwordErrors = ref<Record<string, string[]>>({});
const passwordSuccess = ref('');
const passwordLoading = ref(false);

const deleteErrors = ref<Record<string, string[]>>({});
const deleteLoading = ref(false);

const verificationStatus = ref('');
const verificationLoading = ref(false);

const isEmailVerified = computed(() => user.value.email_verified === true);

const csrf =
    document.head
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content') ?? '';

const activeSection = ref('account');

const sections = [
    { id: 'account', label: t('profile.account') },
    { id: 'preferences', label: t('profile.preferences') },
    { id: 'security', label: t('profile.security') },
    { id: 'danger', label: t('profile.dangerZone') },
];

const scrollToSection = (id: string) => {
    activeSection.value = id;
    document.getElementById(id)?.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
    });
};

async function fetchProfile() {
    try {
        const res = await fetch('/profile', {
            headers: { Accept: 'application/json' },
        });
        if (res.status === 401) {
            router.push('/login');
            return;
        }
        const data = await res.json();
        user.value = data.user;
        name.value = data.user.name;
        email.value = data.user.email;
        currency.value = data.user.currency ?? 'EUR';
    } catch {
        router.push('/login');
    }
}

async function resendVerificationEmail() {
    verificationStatus.value = '';
    verificationLoading.value = true;

    try {
        const res = await axios.post(
            '/email/verification-notification',
            {},
            {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                },
            },
        );

        verificationStatus.value =
            res.data.message ?? t('profile.emailVerificationSent');
    } catch {
        verificationStatus.value = t('profile.emailVerificationFailed');
    } finally {
        verificationLoading.value = false;
    }
}

async function updateProfile() {
    profileErrors.value = {};
    profileSuccess.value = '';
    profileLoading.value = true;
    try {
        const res = await fetch('/profile', {
            method: 'PUT',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ name: name.value, email: email.value }),
        });

        if (res.status === 422) {
            const data = await res.json();
            profileErrors.value = data.errors ?? {};
            return;
        }

        if (!res.ok) {
            profileErrors.value = { email: ['Failed to update profile.'] };
            return;
        }

        const data = await res.json();
        user.value = data.user;
        profileSuccess.value = t('profile.profileUpdated');
    } catch {
        profileErrors.value = { email: [t('profile.networkError')] };
    } finally {
        profileLoading.value = false;
    }
}

async function updatePreferences() {
    preferencesErrors.value = {};
    preferencesSuccess.value = '';
    preferencesLoading.value = true;
    try {
        const res = await fetch('/profile', {
            method: 'PUT',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({
                name: name.value,
                email: email.value,
                currency: currency.value,
            }),
        });

        if (res.status === 422) {
            const data = await res.json();
            preferencesErrors.value = data.errors ?? {};
            return;
        }

        if (!res.ok) {
            preferencesErrors.value = {
                currency: [t('profile.failedToUpdatePreferences')],
            };
            return;
        }

        const data = await res.json();
        user.value = data.user;
        currency.value = data.user.currency ?? currency.value;
        updateAppCurrency(data.user.currency ?? currency.value);
        preferencesSuccess.value = t('profile.preferencesUpdated');
    } catch {
        preferencesErrors.value = { currency: [t('profile.networkError')] };
    } finally {
        preferencesLoading.value = false;
    }
}

async function updatePassword() {
    passwordErrors.value = {};
    passwordSuccess.value = '';
    passwordLoading.value = true;
    try {
        const res = await fetch('/profile/password', {
            method: 'PUT',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({
                current_password: currentPassword.value,
                password: newPassword.value,
                password_confirmation: newPasswordConfirmation.value,
            }),
        });

        if (res.status === 422) {
            const data = await res.json();
            passwordErrors.value = data.errors ?? {};
            return;
        }

        if (!res.ok) {
            passwordErrors.value = {
                current_password: [t('profile.failedToUpdatePassword')],
            };
            return;
        }
        passwordSuccess.value = t('profile.passwordUpdated');
        currentPassword.value = '';
        newPassword.value = '';
        newPasswordConfirmation.value = '';
    } catch {
        passwordErrors.value = {
            current_password: [t('profile.networkError')],
        };
    } finally {
        passwordLoading.value = false;
    }
}

async function deleteAccount() {
    if (!confirm(t('profile.deleteAccountConfirm'))) return;
    deleteErrors.value = {};
    deleteLoading.value = true;
    try {
        const res = await fetch('/profile', {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ password: deletePassword.value }),
        });

        if (res.status === 422) {
            const data = await res.json();
            deleteErrors.value = data.errors ?? {};
            return;
        }

        if (!res.ok) {
            deleteErrors.value = { password: [t('profile.failedToDeleteAccount')] };
            return;
        }

        document.body.dataset.authenticated = '0';
        router.push('/login');
    } 
    catch {
        deleteErrors.value = { password: [t('profile.networkError')] };
    } finally {
        deleteLoading.value = false;
    }
}

async function logout() {
    try {
        await fetch('/logout', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
        });
    } catch {
        // ignore
    }

    document.body.dataset.authenticated = '0';
    router.push('/login');
}

onMounted(fetchProfile);
</script>

<template>
    <div class="mx-auto w-full max-w-6xl space-y-4 px-2 py-4 sm:space-y-6 sm:px-0 sm:py-6">
        <div class="flex items-end justify-between gap-4">
            <div class="min-w-0">
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white sm:text-2xl">
                    {{ t('profile.settingsTitle') }}
                </h2>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                    {{ t('profile.settingsSubtitle') }}
                </p>
            </div>
        </div>

        <section
            v-if="!isEmailVerified"
            class="flex flex-col gap-4 rounded-2xl border border-red-600/30 bg-red-600/10 p-4 backdrop-blur-xl sm:flex-row sm:items-center sm:justify-between sm:p-5 dark:border-red-500/30 dark:bg-red-500/10"
        >
            <div class="min-w-0">
                <p class="text-sm font-semibold text-red-800 dark:text-red-300">
                    {{ t('profile.emailUnverifiedTitle') }}
                </p>
                <p
                    v-if="verificationStatus"
                    class="mt-1 text-sm text-red-800/90 dark:text-red-200/90"
                >
                    {{ verificationStatus }}
                </p>
                <p v-else class="mt-1 text-sm text-red-800/80 dark:text-red-200/80">
                    {{ t('profile.emailUnverifiedText') }}
                </p>
            </div>
            <Button
                variant="danger"
                size="sm"
                class="w-full shrink-0 sm:w-auto"
                :disabled="verificationLoading"
                @click="resendVerificationEmail"
            >
                {{
                    verificationLoading
                        ? t('profile.sendingVerification')
                        : t('profile.resendVerification')
                }}
            </Button>
        </section>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-[220px_minmax(0,1fr)] lg:items-start">
                <aside class="w-full lg:sticky lg:top-6 min-w-0">
                <nav class="flex gap-2 max-w-full overflow-x-auto rounded-2xl border border-slate-900/10 dark:border-white/10 bg-white/70 p-2 backdrop-blur-xl lg:flex-col lg:overflow-visible dark:bg-slate-900/70">
                    <button
                        v-for="item in sections"
                        :key="item.id"
                        type="button"
                        class="shrink lg:shrink-0 rounded-xl px-2.5 py-2 text-[11px] font-medium transition sm:px-3 sm:py-2.5 sm:text-sm"
                        :class="
                            activeSection === item.id
                                ? 'bg-slate-900 text-white shadow-sm shadow-slate-500/30 dark:bg-white dark:text-slate-950 dark:shadow-black/30'
                                : 'text-slate-700 dark:text-slate-300 hover:bg-slate-900/5 dark:hover:bg-white/5 hover:text-slate-900 dark:hover:text-white'
                        "
                        @click="scrollToSection(item.id)"
                    >
                        {{ item.label }}
                    </button>
                </nav>

                <Button
                    variant="outline"
                    block
                    class="mt-3 hidden lg:inline-flex"
                    @click="logout"
                >
                    {{ t('profile.logout') }}
                </Button>
            </aside>

            <div class="min-w-0 space-y-5 sm:space-y-6">
                <section
                    id="account"
                    class="scroll-mt-28 rounded-2xl border border-slate-900/10 dark:border-white/10 bg-slate-900/5 p-4 shadow-xl shadow-slate-500/25 backdrop-blur-xl sm:p-5 dark:bg-white/5 dark:shadow-black/25"
                >
                    <div>
                        <p
                            class="text-[11px] font-semibold uppercase tracking-[0.25em] text-cyan-700/70 dark:text-cyan-300/70"
                        >
                            {{ t('profile.account') }}
                        </p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-900 dark:text-white">
                            {{ t('profile.profileCardTitle') }}
                        </h3>
                        <p class="mt-1.5 text-sm text-slate-600 dark:text-slate-400">
                            {{ t('profile.profileCardSubtitle') }}
                        </p>
                    </div>

                    <div
                        v-if="profileSuccess"
                        class="mt-5 rounded-xl border border-emerald-600/30 bg-emerald-600/10 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300"
                    >
                        {{ profileSuccess }}
                    </div>

                    <form
                        @submit.prevent="updateProfile"
                        class="mt-5 grid gap-4 sm:grid-cols-2"
                    >
                        <div class="min-w-0">
                            <InputText
                                v-model="name"
                                :label="t('profile.name')"
                                type="text"
                                :error="!!profileErrors.name"
                                required
                            />
                            <p
                                v-if="profileErrors.name"
                                class="mt-1.5 text-xs text-red-600 dark:text-red-400"
                            >
                                {{ profileErrors.name[0] }}
                            </p>
                        </div>

                        <div class="min-w-0">
                            <InputText
                                v-model="email"
                                :label="t('profile.email')"
                                type="email"
                                :error="!!profileErrors.email"
                                required
                            />
                            <p
                                v-if="profileErrors.email"
                                class="mt-1.5 text-xs text-red-600 dark:text-red-400"
                            >
                                {{ profileErrors.email[0] }}
                            </p>
                        </div>

                        <div class="flex justify-end sm:col-span-2">
                            <Button
                                type="submit"
                                class="w-full sm:w-auto"
                                :disabled="profileLoading"
                            >
                                {{
                                    profileLoading
                                        ? t('profile.saving')
                                        : t('profile.saveChanges')
                                }}
                            </Button>
                        </div>
                    </form>
                </section>

                <section
                    id="preferences"
                    class="scroll-mt-28 rounded-2xl border border-slate-900/10 dark:border-white/10 bg-slate-900/5 p-4 shadow-xl shadow-slate-500/25 backdrop-blur-xl sm:p-5 dark:bg-white/5 dark:shadow-black/25"
                >
                    <div>
                        <p
                            class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-700/70 dark:text-cyan-300/70"
                        >
                            {{ t('profile.preferences') }}
                        </p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">
                            {{ t('profile.displayCurrency') }}
                        </h3>
                        <p class="mt-1.5 text-sm text-slate-600 dark:text-slate-400">
                            {{ t('profile.displayCurrencySubtitle') }}
                        </p>
                    </div>

                    <div
                        v-if="preferencesSuccess"
                        class="mt-5 rounded-xl border border-emerald-600/30 bg-emerald-600/10 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300"
                    >
                        {{ preferencesSuccess }}
                    </div>

                    <form
                        @submit.prevent="updatePreferences"
                        class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
                    >
                        <div class="w-full min-w-0 sm:max-w-xs">
                            <InputSelect
                                v-model="currency"
                                :label="t('profile.preferredCurrency')"
                                :options="currencyOptions"
                            />
                            <p
                                v-if="preferencesErrors.currency"
                                class="mt-1.5 text-xs text-red-600 dark:text-red-400"
                            >
                                {{ preferencesErrors.currency[0] }}
                            </p>
                        </div>

                        <div class="flex justify-end sm:shrink-0">
                            <Button
                                type="submit"
                                class="w-full sm:w-auto"
                                :disabled="preferencesLoading"
                            >
                                {{
                                    preferencesLoading
                                        ? t('profile.savePreferencesLoading')
                                        : t('profile.savePreferences')
                                }}
                            </Button>
                        </div>
                    </form>
                </section>

                <section
                    id="security"
                    class="scroll-mt-28 rounded-2xl border border-slate-900/10 dark:border-white/10 bg-slate-900/5 p-4 shadow-xl shadow-slate-500/25 backdrop-blur-xl sm:p-5 dark:bg-white/5 dark:shadow-black/25"
                >
                    <div>
                        <p
                            class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-700/70 dark:text-cyan-300/70"
                        >
                            {{ t('profile.security') }}
                        </p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">
                            {{ t('profile.changePassword') }}
                        </h3>
                        <p class="mt-1.5 text-sm text-slate-600 dark:text-slate-400">
                            {{ t('profile.changePasswordSubtitle') }}
                        </p>
                    </div>

                    <div
                        v-if="passwordSuccess"
                        class="mt-5 rounded-xl border border-emerald-600/30 bg-emerald-600/10 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300"
                    >
                        {{ passwordSuccess }}
                    </div>

                    <form
                        @submit.prevent="updatePassword"
                        class="mt-5 grid gap-4 sm:grid-cols-2"
                    >
                        <div class="min-w-0">
                            <InputText
                                v-model="currentPassword"
                                :label="t('profile.currentPassword')"
                                type="password"
                                :error="!!passwordErrors.current_password"
                                required
                            />
                            <p
                                v-if="passwordErrors.current_password"
                                class="mt-1.5 text-xs text-red-600 dark:text-red-400"
                            >
                                {{ passwordErrors.current_password[0] }}
                            </p>
                        </div>

                        <div class="min-w-0">
                            <InputText
                                v-model="newPassword"
                                :label="t('profile.newPassword')"
                                type="password"
                                :error="!!passwordErrors.password"
                                required
                            />
                            <p
                                v-if="passwordErrors.password"
                                class="mt-1.5 text-xs text-red-600 dark:text-red-400"
                            >
                                {{ passwordErrors.password[0] }}
                            </p>
                        </div>

                        <div class="min-w-0">
                            <InputText
                                v-model="newPasswordConfirmation"
                                :label="t('profile.confirmNewPassword')"
                                type="password"
                                required
                            />
                        </div>

                        <div class="flex justify-end sm:col-span-2">
                            <Button
                                type="submit"
                                class="w-full sm:w-auto"
                                :disabled="passwordLoading"
                            >
                                {{
                                    passwordLoading
                                        ? t('profile.updatingPassword')
                                        : t('profile.updatePassword')
                                }}
                            </Button>
                        </div>
                    </form>
                </section>

                <section
                    id="danger"
                    class="scroll-mt-28 rounded-2xl border border-red-600/20 bg-red-600/5 p-4 shadow-xl shadow-slate-500/25 backdrop-blur-xl sm:p-5 dark:border-red-500/20 dark:bg-red-500/5 dark:shadow-black/25"
                >
                    <div>
                        <p
                            class="text-xs font-semibold uppercase tracking-[0.25em] text-red-600 dark:text-red-400"
                        >
                            {{ t('profile.dangerZone') }}
                        </p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">
                            {{ t('profile.deleteAccount') }}
                        </h3>
                        <p class="mt-1.5 text-sm text-slate-600 dark:text-slate-400">
                            {{ t('profile.deleteAccountSubtitle') }}
                        </p>
                    </div>

                    <form
                        @submit.prevent="deleteAccount"
                        class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
                    >
                        <div class="w-full min-w-0 sm:max-w-xs">
                            <InputText
                                v-model="deletePassword"
                                :label="t('profile.confirmPasswordDelete')"
                                type="password"
                                :error="!!deleteErrors.password"
                                required
                            />
                            <p
                                v-if="deleteErrors.password"
                                class="mt-1.5 text-xs text-red-600 dark:text-red-400"
                            >
                                {{ deleteErrors.password[0] }}
                            </p>
                        </div>

                        <div class="flex justify-end sm:shrink-0">
                            <Button
                                type="submit"
                                variant="danger"
                                class="w-full sm:w-auto"
                                :disabled="deleteLoading"
                            >
                                {{
                                    deleteLoading
                                        ? t('profile.deletingAccount')
                                        : t('profile.deleteAccountButton')
                                }}
                            </Button>
                        </div>
                    </form>
                </section>

                <div class="flex justify-center lg:hidden">
                    <Button variant="outline" @click="logout">
                        {{ t('profile.logout') }}
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>