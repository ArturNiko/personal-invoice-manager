<script setup lang="ts">
import axios from 'axios'
import { ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()
const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''

const email = ref((route.query.email as string) ?? '')
const password = ref('')
const passwordConfirmation = ref('')
const errors = ref<Record<string, string[]>>({})
const generalError = ref('')
const loading = ref(false)

const token = (route.params.token as string) || ''

async function submit() {
    errors.value = {}
    generalError.value = ''
    loading.value = true

    try {
        await axios.post('/reset-password', {
            token,
            email: email.value,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
        }, {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
            },
        })

        router.push('/login')
    } catch (error) {
        if (axios.isAxiosError(error)) {
            if (error.response?.status === 422) {
                errors.value = error.response.data.errors ?? {}

                if (!Object.keys(errors.value).length) {
                    generalError.value = error.response.data.message ?? 'The password could not be reset.'
                }

                return
            }

            if (error.response?.status === 419) {
                generalError.value = 'Your session expired. Please refresh the page and try again.'
                return
            }
        }

        generalError.value = 'The reset link is invalid or expired. Please request a new one.'
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
                    Secure access
                </p>
                <h1 class="mt-3 text-3xl font-semibold text-white">Set a new password</h1>
            </div>

            <div v-if="generalError" class="mb-5 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">
                {{ generalError }}
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

                <div>
                    <label for="password" class="mb-2 block text-sm font-medium text-slate-200">New password</label>
                    <input
                        id="password"
                        v-model="password"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="w-full rounded-xl border bg-slate-900/80 px-3 py-2.5 text-slate-100 outline-none transition focus:ring-2"
                        :class="errors.password ? 'border-red-500/60 focus:border-red-400 focus:ring-red-500/40' : 'border-white/10 focus:border-cyan-400 focus:ring-cyan-500/40'"
                    />
                    <p v-if="errors.password" class="mt-1.5 text-xs text-red-400">{{ errors.password[0] }}</p>
                </div>

                <div>
                    <label for="password_confirmation" class="mb-2 block text-sm font-medium text-slate-200">Confirm new password</label>
                    <input
                        id="password_confirmation"
                        v-model="passwordConfirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="w-full rounded-xl border border-white/10 bg-slate-900/80 px-3 py-2.5 text-slate-100 outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-500/40"
                    />
                </div>

                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full rounded-xl bg-cyan-400 px-4 py-2.5 font-semibold text-slate-950 transition hover:bg-cyan-300 disabled:opacity-50"
                >
                    {{ loading ? 'Saving...' : 'Save new password' }}
                </button>
            </form>

            <div class="mt-6 space-y-2 text-center text-sm text-slate-300">
                <div>
                    <RouterLink to="/login" class="font-semibold text-cyan-300 hover:text-cyan-200">
                        Back to login
                    </RouterLink>
                </div>
                <div>
                    Need a new reset link?
                    <RouterLink to="/forgot-password" class="font-semibold text-cyan-300 hover:text-cyan-200">
                        Request another
                    </RouterLink>
                </div>
            </div>
        </div>
    </div>
</template>
