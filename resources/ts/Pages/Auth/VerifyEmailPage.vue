<script setup lang="ts">
import axios from 'axios';
import { ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';

const router = useRouter();
const csrfToken =
    document.head
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content') ?? '';

const status = ref('');
const error = ref('');
const loading = ref(false);

async function resendVerification() {
    status.value = '';
    error.value = '';
    loading.value = true;

    try {
        await axios.post(
            '/email/verification-notification',
            {},
            {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                },
            },
        );

        status.value =
            'A fresh verification link has been sent. It expires in 15 minutes.';
    } 
    catch {
        error.value = 'We could not resend the email. Please try again.';
    } 
    finally {
        loading.value = false;
    }
}

async function logout() {
    try {
        await axios.post(
            '/logout',
            {},
            {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                },
            },
        );
    } 
    catch {
        // ignore
    }

    document.body.dataset.authenticated = '0';
    router.push('/login');
}
</script>

<template>
    <div
        class="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10 text-slate-900 dark:bg-slate-950 dark:text-slate-100"
    >
        <div
            class="w-full max-w-lg rounded-3xl border border-slate-900/10 bg-slate-900/5 p-6 shadow-2xl shadow-slate-500/50 backdrop-blur-xl dark:border-white/10 dark:bg-white/5 dark:shadow-black/50 sm:p-8"
        >
            <div class="mb-8 text-center">
                <p
                    class="text-xs font-semibold uppercase tracking-[0.35em] text-cyan-700/80 dark:text-cyan-300/80"
                >
                    Email verification
                </p>
                <h1 class="mt-3 text-3xl font-semibold text-slate-900 dark:text-white">
                    Verify your account
                </h1>
            </div>

            <div
                class="rounded-2xl border border-amber-600/25 bg-amber-700/10 p-5 text-sm text-amber-800 dark:border-amber-400/25 dark:bg-amber-500/10 dark:text-amber-100"
            >
                Please check your email and confirm your account before
                continuing. The verification link expires after 15 minutes.
            </div>

            <div
                v-if="status"
                class="mt-5 rounded-xl border border-emerald-600/30 bg-emerald-600/10 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300"
            >
                {{ status }}
            </div>

            <div
                v-if="error"
                class="mt-5 rounded-xl border border-red-600/30 bg-red-600/10 px-4 py-3 text-sm text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300"
            >
                {{ error }}
            </div>

            <div class="mt-6 space-y-3">
                <button
                    type="button"
                    :disabled="loading"
                    @click="resendVerification"
                    class="w-full rounded-xl bg-cyan-700 px-4 py-2.5 font-semibold text-white transition hover:bg-cyan-800 disabled:opacity-50 dark:bg-cyan-400 dark:text-slate-950 dark:hover:bg-cyan-300"
                >
                    {{ loading ? 'Sending...' : 'Resend verification email' }}
                </button>

                <RouterLink
                    to="/login"
                    class="block w-full rounded-xl border border-slate-900/10 bg-white/80 px-4 py-2.5 dark:border-white/10 dark:bg-slate-900/80 text-center font-medium text-slate-800 dark:text-slate-200 transition hover:border-slate-900/20 hover:text-slate-900 dark:hover:border-white/20 dark:hover:text-white"
                >
                    Back to login
                </RouterLink>

                <button
                    type="button"
                    @click="logout"
                    class="w-full rounded-xl border border-slate-900/10 bg-white/80 px-4 py-2.5 dark:border-white/10 dark:bg-slate-900/80 font-medium text-slate-800 dark:text-slate-200 transition hover:border-slate-900/20 hover:text-slate-900 dark:hover:border-white/20 dark:hover:text-white"
                >
                    Log out
                </button>
            </div>
        </div>
    </div>
</template>
