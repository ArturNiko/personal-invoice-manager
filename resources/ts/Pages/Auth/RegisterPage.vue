<script setup lang="ts">
import axios from 'axios';
import { ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { t } from '@/i18n';

import InputText from '@/Components/Form/InputText.vue';


const router = useRouter();
const csrfToken =
    document.head
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content') ?? '';

const name = ref('');
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const errors = ref<Record<string, string[]>>({});
const generalError = ref('');
const loading = ref(false);

const submit = async () => {
    errors.value = {};
    generalError.value = '';
    loading.value = true;

    try {
        const res = await axios.post(
            '/register',
            {
                name: name.value,
                email: email.value,
                password: password.value,
                password_confirmation: passwordConfirmation.value,
            },
            {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                },
            },
        );

        if (res.data.redirect) {
            document.body.dataset.authenticated = '1';
            router.push(res.data.redirect);
            return;
        }

        document.body.dataset.authenticated = '1';
        router.push('/dashboard');
    } 
    catch (error) {
        if (axios.isAxiosError(error)) {
            if (error.response?.status === 419) {
                generalError.value = t('auth.sessionExpired');
                return;
            }

            if (error.response?.status === 422) {
                errors.value = error.response.data.errors ?? {};

                if (!Object.keys(errors.value).length) {
                    generalError.value =
                        error.response.data.message ?? 'Registration failed.';
                }

                return;
            }
        }

        generalError.value = t('auth.somethingWentWrong');
    } 
    finally {
        loading.value = false;
    }
};
</script>

<template>
    <div
        class="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10 text-slate-900 dark:bg-slate-950 dark:text-slate-100"
    >
        <div
            class="w-full max-w-md rounded-3xl border border-slate-900/10 bg-slate-900/5 p-6 shadow-2xl shadow-slate-500/50 backdrop-blur-xl dark:border-white/10 dark:bg-white/5 dark:shadow-black/50 sm:p-8"
        >
            <div class="mb-8 text-center">
                <p
                    class="text-xs font-semibold uppercase tracking-[0.35em] text-cyan-700/80 dark:text-cyan-300/80"
                >
                    {{ t('auth.createAccount') }}
                </p>
                <h1 class="mt-3 text-3xl font-semibold text-slate-900 dark:text-white">
                    {{ t('auth.register') }}
                </h1>
            </div>

            <div
                v-if="generalError"
                class="mb-5 rounded-xl border border-red-600/30 bg-red-600/10 px-4 py-3 text-sm text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300"
            >
                {{ generalError }}
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <InputText
                        v-model="name"
                        :label="t('profile.name')"
                        type="text"
                        :error="!!errors.name"
                        required
                    />
                    <p v-if="errors.name" class="mt-1.5 text-xs text-red-600 dark:text-red-400">
                        {{ errors.name[0] }}
                    </p>
                </div>

                <div>
                    <InputText
                        v-model="email"
                        :label="t('auth.email')"
                        type="email"
                        :error="!!errors.email"
                        required
                    />
                    <p v-if="errors.email" class="mt-1.5 text-xs text-red-600 dark:text-red-400">
                        {{ errors.email[0] }}
                    </p>
                </div>

                <div>
                    <InputText
                        v-model="password"
                        :label="t('auth.password')"
                        type="password"
                        :error="!!errors.password"
                        required
                    />
                    <p
                        v-if="errors.password"
                        class="mt-1.5 text-xs text-red-600 dark:text-red-400"
                    >
                        {{ errors.password[0] }}
                    </p>
                </div>

                <div>
                    <InputText
                        v-model="passwordConfirmation"
                        :label="t('auth.confirmPassword')"
                        type="password"
                        :error="!!errors.password_confirmation"
                        required
                    />
                    <p
                        v-if="errors.password_confirmation"
                        class="mt-1.5 text-xs text-red-600 dark:text-red-400"
                    >
                        {{ errors.password_confirmation[0] }}
                    </p>
                </div>

                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full rounded-xl bg-cyan-700 px-4 py-2.5 font-semibold text-white transition hover:bg-cyan-800 disabled:opacity-50 dark:bg-cyan-400 dark:text-slate-950 dark:hover:bg-cyan-300"
                >
                    {{ loading ? t('auth.registering') : t('auth.register') }}
                </button>
            </form>

            <div class="mt-6 text-center text-sm text-slate-700 dark:text-slate-300">
                {{ t('auth.alreadyHaveAccount') }}
                <RouterLink
                    to="/login"
                    class="font-semibold text-cyan-700 hover:text-cyan-700 dark:text-cyan-300 dark:hover:text-cyan-200"
                >
                    {{ t('auth.login') }}
                </RouterLink>
            </div>
        </div>
    </div>
</template>
