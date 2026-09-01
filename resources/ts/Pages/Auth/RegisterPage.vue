<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

const router = useRouter()
const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const errors = ref<Record<string, string[]>>({})
const generalError = ref('')
const loading = ref(false)

async function submit() {
    errors.value = {}
    generalError.value = ''
    loading.value = true

    try {
        const res = await fetch('/register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                name: name.value,
                email: email.value,
                password: password.value,
                password_confirmation: passwordConfirmation.value,
            }),
        })

        if (res.status === 422) {
            const data = await res.json()
            errors.value = data.errors ?? {}
            if (!Object.keys(errors.value).length) {
                generalError.value = data.message ?? 'Registration failed.'
            }
            return
        }

        if (!res.ok) {
            generalError.value = 'Something went wrong. Please try again.'
            return
        }

        document.body.dataset.authenticated = '1'
        router.push('/calendar')
    } catch {
        generalError.value = 'Network error. Please try again.'
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
                    Create account
                </p>
                <h1 class="mt-3 text-3xl font-semibold text-white">Register</h1>
            </div>

            <div
                v-if="generalError"
                class="mb-5 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300"
            >
                {{ generalError }}
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <label for="name" class="mb-2 block text-sm font-medium text-slate-200">Name</label>
                    <input
                        id="name"
                        v-model="name"
                        type="text"
                        required
                        autocomplete="name"
                        class="w-full rounded-xl border bg-slate-900/80 px-3 py-2.5 text-slate-100 outline-none transition focus:ring-2"
                        :class="errors.name ? 'border-red-500/60 focus:border-red-400 focus:ring-red-500/40' : 'border-white/10 focus:border-cyan-400 focus:ring-cyan-500/40'"
                    />
                    <p v-if="errors.name" class="mt-1.5 text-xs text-red-400">{{ errors.name[0] }}</p>
                </div>

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
                    <label for="password" class="mb-2 block text-sm font-medium text-slate-200">Password</label>
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
                    <label for="password_confirmation" class="mb-2 block text-sm font-medium text-slate-200">Confirm password</label>
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
                    {{ loading ? 'Creating account...' : 'Register' }}
                </button>
            </form>

            <div class="mt-6 text-center text-sm text-slate-300">
                Already have an account?
                <RouterLink to="/login" class="font-semibold text-cyan-300 hover:text-cyan-200">
                    Log in
                </RouterLink>
            </div>
        </div>
    </div>
</template>
