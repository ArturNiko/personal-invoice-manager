<script setup lang="ts">
import axios from 'axios';
import { computed, ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';

import Button from '@/Components/Button.vue';
import InputSelect from '@/Components/Form/InputSelect.vue';
import { updateAppCurrency } from '@/Composables/useAppSettings';
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

const inputBaseClass =
    'w-full rounded-xl border bg-slate-900/80 px-3 py-2.5 text-slate-100 outline-none transition focus:ring-2';
const inputErrorClass =
    'border-red-500/60 focus:border-red-400 focus:ring-red-500/40';
const inputNormalClass =
    'border-white/10 focus:border-cyan-400 focus:ring-cyan-500/40';
const inputClass = (hasError: boolean) => [
    inputBaseClass,
    hasError ? inputErrorClass : inputNormalClass,
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
            res.data.message ?? 'Verification email sent again.';
    } catch {
        verificationStatus.value =
            'We could not resend the email. Please try again.';
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
        profileSuccess.value = 'Profile updated.';
    } catch {
        profileErrors.value = { email: ['Network error.'] };
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
                currency: ['Failed to update preferences.'],
            };
            return;
        }

        const data = await res.json();
        user.value = data.user;
        currency.value = data.user.currency ?? currency.value;
        updateAppCurrency(data.user.currency ?? currency.value);
        preferencesSuccess.value = 'Preferences updated.';
    } catch {
        preferencesErrors.value = { currency: ['Network error.'] };
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
                current_password: ['Failed to update password.'],
            };
            return;
        }
        passwordSuccess.value = 'Password updated.';
        currentPassword.value = '';
        newPassword.value = '';
        newPasswordConfirmation.value = '';
    } catch {
        passwordErrors.value = { current_password: ['Network error.'] };
    } finally {
        passwordLoading.value = false;
    }
}

async function deleteAccount() {
    if (!confirm('This action is irreversible. Delete your account?')) return;
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
            deleteErrors.value = { password: ['Failed to delete account.'] };
            return;
        }

        document.body.dataset.authenticated = '0';
        router.push('/login');
    } catch {
        deleteErrors.value = { password: ['Network error.'] };
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
    <div class="mx-auto w-full max-w-6xl space-y-5 py-4 sm:space-y-6 sm:py-6">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-white sm:text-2xl">
                    Profile settings
                </h2>
                <p class="mt-1 text-sm text-slate-400">
                    Manage your account details, preferences, and security.
                </p>
            </div>
        </div>

        <section
            v-if="!isEmailVerified"
            class="flex flex-col gap-4 rounded-2xl border border-red-500/30 bg-red-500/10 p-4 backdrop-blur-xl sm:flex-row sm:items-center sm:justify-between sm:p-5"
        >
            <div>
                <p class="text-sm font-semibold text-red-300">
                    Your email address is not verified yet.
                </p>
                <p
                    v-if="verificationStatus"
                    class="mt-1 text-sm text-red-200/90"
                >
                    {{ verificationStatus }}
                </p>
                <p v-else class="mt-1 text-sm text-red-200/80">
                    You can still use the app, but verification helps keep your
                    account secure.
                </p>
            </div>
            <Button
                variant="danger"
                size="sm"
                class="shrink-0"
                :disabled="verificationLoading"
                @click="resendVerificationEmail"
            >
                {{
                    verificationLoading
                        ? 'Sending...'
                        : 'Resend verification email'
                }}
            </Button>
        </section>

        <div
            class="grid gap-5 lg:grid-cols-[220px_minmax(0,1fr)] lg:items-start"
        >
            <aside class="lg:sticky lg:top-6">
                <nav
                    class="flex gap-2 overflow-x-auto rounded-2xl border border-white/10 bg-slate-900/70 p-2 backdrop-blur-xl lg:flex-col lg:overflow-visible"
                >
                    <button
                        v-for="item in [
                            { id: 'account', label: 'Account' },
                            { id: 'preferences', label: 'Preferences' },
                            { id: 'security', label: 'Security' },
                            { id: 'danger', label: 'Danger zone' },
                        ]"
                        :key="item.id"
                        type="button"
                        class="shrink-0 rounded-xl px-3 py-2.5 text-sm font-medium transition lg:shrink"
                        :class="
                            activeSection === item.id
                                ? 'bg-white text-slate-950 shadow-sm shadow-slate-950/30'
                                : 'text-slate-300 hover:bg-white/5 hover:text-white'
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
                    Log out
                </Button>
            </aside>

            <div class="min-w-0 space-y-5 sm:space-y-6">
                <section
                    id="account"
                    class="scroll-mt-28 rounded-2xl border border-white/10 bg-white/5 p-4 shadow-xl shadow-slate-950/25 backdrop-blur-xl sm:p-5"
                >
                    <div>
                        <p
                            class="text-[11px] font-semibold uppercase tracking-[0.25em] text-cyan-300/70"
                        >
                            Account
                        </p>
                        <h3 class="mt-2 text-lg font-semibold text-white">
                            Profile
                        </h3>
                        <p class="mt-1.5 text-sm text-slate-400">
                            Your name and sign-in email address.
                        </p>
                    </div>

                    <div
                        v-if="profileSuccess"
                        class="mt-5 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300"
                    >
                        {{ profileSuccess }}
                    </div>

                    <form
                        @submit.prevent="updateProfile"
                        class="mt-5 grid gap-4 sm:grid-cols-2"
                    >
                        <div>
                            <label
                                for="name"
                                class="mb-1.5 block text-sm font-medium text-slate-200"
                                >Name</label
                            >
                            <input
                                id="name"
                                v-model="name"
                                type="text"
                                required
                                :class="inputClass(!!profileErrors.name)"
                            />
                            <p
                                v-if="profileErrors.name"
                                class="mt-1.5 text-xs text-red-400"
                            >
                                {{ profileErrors.name[0] }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="profile-email"
                                class="mb-1.5 block text-sm font-medium text-slate-200"
                                >Email</label
                            >
                            <input
                                id="profile-email"
                                v-model="email"
                                type="email"
                                required
                                :class="inputClass(!!profileErrors.email)"
                            />
                            <p
                                v-if="profileErrors.email"
                                class="mt-1.5 text-xs text-red-400"
                            >
                                {{ profileErrors.email[0] }}
                            </p>
                        </div>

                        <div class="flex justify-end sm:col-span-2">
                            <Button
                                type="submit"
                                :disabled="profileLoading"
                            >
                                {{
                                    profileLoading
                                        ? 'Saving...'
                                        : 'Save changes'
                                }}
                            </Button>
                        </div>
                    </form>
                </section>

                <section
                    id="preferences"
                    class="scroll-mt-28 rounded-2xl border border-white/10 bg-white/5 p-4 shadow-xl shadow-slate-950/25 backdrop-blur-xl sm:p-5"
                >
                    <div>
                        <p
                            class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-300/70"
                        >
                            Preferences
                        </p>
                        <h3 class="mt-1 text-lg font-semibold text-white">
                            Display currency
                        </h3>
                        <p class="mt-1.5 text-sm text-slate-400">
                            Dashboard totals and the forecast are converted
                            into this currency.
                        </p>
                    </div>

                    <div
                        v-if="preferencesSuccess"
                        class="mt-5 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300"
                    >
                        {{ preferencesSuccess }}
                    </div>

                    <form
                        @submit.prevent="updatePreferences"
                        class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
                    >
                        <div class="w-full sm:max-w-xs">
                            <InputSelect
                                v-model="currency"
                                label="Preferred currency"
                                :options="currencyOptions"
                            />
                            <p
                                v-if="preferencesErrors.currency"
                                class="mt-1.5 text-xs text-red-400"
                            >
                                {{ preferencesErrors.currency[0] }}
                            </p>
                        </div>

                        <div class="flex justify-end sm:shrink-0">
                            <Button
                                type="submit"
                                :disabled="preferencesLoading"
                            >
                                {{
                                    preferencesLoading
                                        ? 'Saving...'
                                        : 'Save preferences'
                                }}
                            </Button>
                        </div>
                    </form>
                </section>

                <section
                    id="security"
                    class="scroll-mt-28 rounded-2xl border border-white/10 bg-white/5 p-4 shadow-xl shadow-slate-950/25 backdrop-blur-xl sm:p-5"
                >
                    <div>
                        <p
                            class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-300/70"
                        >
                            Security
                        </p>
                        <h3 class="mt-1 text-lg font-semibold text-white">
                            Change password
                        </h3>
                        <p class="mt-1.5 text-sm text-slate-400">
                            Choose a strong password you don't use elsewhere.
                        </p>
                    </div>

                    <div
                        v-if="passwordSuccess"
                        class="mt-5 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300"
                    >
                        {{ passwordSuccess }}
                    </div>

                    <form
                        @submit.prevent="updatePassword"
                        class="mt-5 grid gap-4 sm:grid-cols-2"
                    >
                        <div>
                            <label
                                for="current-password"
                                class="mb-1.5 block text-sm font-medium text-slate-200"
                                >Current password</label
                            >
                            <input
                                id="current-password"
                                v-model="currentPassword"
                                type="password"
                                required
                                autocomplete="current-password"
                                :class="
                                    inputClass(
                                        !!passwordErrors.current_password,
                                    )
                                "
                            />
                            <p
                                v-if="passwordErrors.current_password"
                                class="mt-1.5 text-xs text-red-400"
                            >
                                {{ passwordErrors.current_password[0] }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="new-password"
                                class="mb-1.5 block text-sm font-medium text-slate-200"
                                >New password</label
                            >
                            <input
                                id="new-password"
                                v-model="newPassword"
                                type="password"
                                required
                                autocomplete="new-password"
                                :class="
                                    inputClass(!!passwordErrors.password)
                                "
                            />
                            <p
                                v-if="passwordErrors.password"
                                class="mt-1.5 text-xs text-red-400"
                            >
                                {{ passwordErrors.password[0] }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="new-password-confirmation"
                                class="mb-1.5 block text-sm font-medium text-slate-200"
                                >Confirm new password</label
                            >
                            <input
                                id="new-password-confirmation"
                                v-model="newPasswordConfirmation"
                                type="password"
                                required
                                autocomplete="new-password"
                                class="w-full rounded-xl border border-white/10 bg-slate-900/80 px-3 py-2.5 text-slate-100 outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-500/40"
                            />
                        </div>

                        <div class="flex justify-end sm:col-span-2">
                            <Button type="submit" :disabled="passwordLoading">
                                {{
                                    passwordLoading
                                        ? 'Updating...'
                                        : 'Update password'
                                }}
                            </Button>
                        </div>
                    </form>
                </section>

                <section
                    id="danger"
                    class="scroll-mt-28 rounded-2xl border border-red-500/20 bg-red-500/5 p-4 shadow-xl shadow-slate-950/25 backdrop-blur-xl sm:p-5"
                >
                    <div>
                        <p
                            class="text-xs font-semibold uppercase tracking-[0.25em] text-red-400"
                        >
                            Danger zone
                        </p>
                        <h3 class="mt-1 text-lg font-semibold text-white">
                            Delete account
                        </h3>
                        <p class="mt-1.5 text-sm text-slate-400">
                            Permanently delete your account and all associated
                            data. This cannot be undone.
                        </p>
                    </div>

                    <form
                        @submit.prevent="deleteAccount"
                        class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
                    >
                        <div class="w-full sm:max-w-xs">
                            <label
                                for="delete-password"
                                class="mb-1.5 block text-sm font-medium text-slate-200"
                                >Confirm your password</label
                            >
                            <input
                                id="delete-password"
                                v-model="deletePassword"
                                type="password"
                                required
                                autocomplete="current-password"
                                :class="inputClass(!!deleteErrors.password)"
                            />
                            <p
                                v-if="deleteErrors.password"
                                class="mt-1.5 text-xs text-red-400"
                            >
                                {{ deleteErrors.password[0] }}
                            </p>
                        </div>

                        <div class="flex justify-end sm:shrink-0">
                            <Button
                                type="submit"
                                variant="danger"
                                :disabled="deleteLoading"
                            >
                                {{
                                    deleteLoading
                                        ? 'Deleting...'
                                        : 'Delete account'
                                }}
                            </Button>
                        </div>
                    </form>
                </section>

                <div class="flex justify-center lg:hidden">
                    <Button variant="outline" @click="logout">Log out</Button>
                </div>
            </div>
        </div>
    </div>
</template>