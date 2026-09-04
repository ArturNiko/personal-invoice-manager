<script setup lang="ts">
import axios from 'axios'
import { ref } from 'vue'
import { RouterLink } from 'vue-router'

const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''

const email = ref('')
const errors = ref<Record<string, string[]>>({})
const generalError = ref('')
const status = ref('')
const loading = ref(false)

async function submit() {
    errors.value = {}
    generalError.value = ''
    status.value = ''
    loading.value = true

    try {
        await axios.post('/forgot-password', {
            email: email.value,
        }, {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
            },
        })

        status.value = 'If that account exists, a password reset link has been sent.'
    } catch (error) {
        if (axios.isAxiosError(error)) {
            if (error.response?.status === 422) {
                errors.value = error.response.data.errors ?? {}

                if (!Object.keys(errors.value).length) {
                    generalError.value = error.response.data.message ?? 'The email address is invalid.'
                }

                return
            }
        }

        generalError.value = 'We could not send the reset link. Please try again.'
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-slate-950 px-4 py-10 text-slate-100">
        <div class="w-full max-w-md rounded-3xl border border-white/10 bg-white/5 p-6 shadow-2xl shadow-slate-950/50 backdrop-blur-xl sm:p-8">
            <div class="mb-8 text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.35em] text-cyan-300/80">
                    Password reset
                </p>
                <h1 class="mt-3 text-3xl font-semibold text-white">Forgot password</h1>
            </div>

            <div v-if="generalError" class="mb-5 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">
                {{ generalError }}
            </div>

            <div v-if="status" class="mb-5 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-slate-200">Email</label>
                    <input
                        id="email"
                        v-model="email"
                        type="email"
                        required
                        autocomplete="email"
                        class="w-full rounded-xl border bg-slate-900/80 px-3 py-2.5 text-slate-100 outline-none transition focus:ring-2"
                        :class="errors.email ? 'border-red-500/60 focus:border-red-400 focus:ring-red-500/40' : 'border-white/10 focus:border-cyan-400 focus:ring-cyan-500/40'"
                    />
                    <p v-if="errors.email" class="mt-1.5 text-xs text-red-400">{{ errors.email[0] }}</p>
                </div>

                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full rounded-xl bg-cyan-400 px-4 py-2.5 font-semibold text-slate-950 transition hover:bg-cyan-300 disabled:opacity-50"
                >
                    {{ loading ? 'Sending...' : 'Send reset link' }}
                </button>
            </form>

            <div class="mt-6 text-center text-sm text-slate-300">
                Remember your password?
                <RouterLink to="/login" class="font-semibold text-cyan-300 hover:text-cyan-200">
                    Back to login
                </RouterLink>
            </div>
        </div>
    </div>
</template>
