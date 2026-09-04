<script setup lang="ts">
import axios from 'axios'
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

const router = useRouter()
const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''

const status = ref('')
const error = ref('')
const loading = ref(false)

async function resendVerification() {
    status.value = ''
    error.value = ''
    loading.value = true

    try {
        await axios.post('/email/verification-notification', {}, {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
            },
        })

        status.value = 'A fresh verification link has been sent. It expires in 15 minutes.'
    } catch {
        error.value = 'We could not resend the email. Please try again.'
    } finally {
        loading.value = false
    }
}

async function logout() {
    try {
        await axios.post('/logout', {}, {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
            },
        })
    } catch {
        // ignore
    }

    document.body.dataset.authenticated = '0'
    router.push('/login')
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-slate-950 px-4 py-10 text-slate-100">
        <div class="w-full max-w-lg rounded-3xl border border-white/10 bg-white/5 p-6 shadow-2xl shadow-slate-950/50 backdrop-blur-xl sm:p-8">
            <div class="mb-8 text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.35em] text-cyan-300/80">
                    Email verification
                </p>
                <h1 class="mt-3 text-3xl font-semibold text-white">Verify your account</h1>
            </div>

            <div class="rounded-2xl border border-amber-400/25 bg-amber-500/10 p-5 text-sm text-amber-100">
                Please check your email and confirm your account before continuing. The verification link expires after 15 minutes.
            </div>

            <div v-if="status" class="mt-5 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
                {{ status }}
            </div>

            <div v-if="error" class="mt-5 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">
                {{ error }}
            </div>

            <div class="mt-6 space-y-3">
                <button
                    type="button"
                    :disabled="loading"
                    @click="resendVerification"
                    class="w-full rounded-xl bg-cyan-400 px-4 py-2.5 font-semibold text-slate-950 transition hover:bg-cyan-300 disabled:opacity-50"
                >
                    {{ loading ? 'Sending...' : 'Resend verification email' }}
                </button>

                <RouterLink
                    to="/login"
                    class="block w-full rounded-xl border border-white/10 bg-slate-900/80 px-4 py-2.5 text-center font-medium text-slate-200 transition hover:border-white/20 hover:text-white"
                >
                    Back to login
                </RouterLink>

                <button
                    type="button"
                    @click="logout"
                    class="w-full rounded-xl border border-white/10 bg-slate-900/80 px-4 py-2.5 font-medium text-slate-200 transition hover:border-white/20 hover:text-white"
                >
                    Log out
                </button>
            </div>
        </div>
    </div>
</template>
