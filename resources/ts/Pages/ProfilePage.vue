<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''

const user = ref({ name: '', email: '' })

const name = ref('')
const email = ref('')
const currentPassword = ref('')
const newPassword = ref('')
const newPasswordConfirmation = ref('')
const deletePassword = ref('')

const profileErrors = ref<Record<string, string[]>>({})
const profileSuccess = ref('')
const profileLoading = ref(false)

const passwordErrors = ref<Record<string, string[]>>({})
const passwordSuccess = ref('')
const passwordLoading = ref(false)

const deleteErrors = ref<Record<string, string[]>>({})
const deleteLoading = ref(false)

const csrf = document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''

async function fetchProfile() {
    try {
        const res = await fetch('/profile', { headers: { Accept: 'application/json' } })
        if (res.status === 401) {
            router.push('/login')
            return
        }
        const data = await res.json()
        user.value = data.user
        name.value = data.user.name
        email.value = data.user.email
    } catch {
        router.push('/login')
    }
}

async function updateProfile() {
    profileErrors.value = {}
    profileSuccess.value = ''
    profileLoading.value = true
    try {
        const res = await fetch('/profile', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ name: name.value, email: email.value }),
        })
        if (res.status === 422) {
            const data = await res.json()
            profileErrors.value = data.errors ?? {}
            return
        }
        if (!res.ok) { profileErrors.value = { email: ['Failed to update profile.'] }; return }
        const data = await res.json()
        user.value = data.user
        profileSuccess.value = 'Profile updated.'
    } catch {
        profileErrors.value = { email: ['Network error.'] }
    } finally {
        profileLoading.value = false
    }
}

async function updatePassword() {
    passwordErrors.value = {}
    passwordSuccess.value = ''
    passwordLoading.value = true
    try {
        const res = await fetch('/profile/password', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({
                current_password: currentPassword.value,
                password: newPassword.value,
                password_confirmation: newPasswordConfirmation.value,
            }),
        })
        if (res.status === 422) {
            const data = await res.json()
            passwordErrors.value = data.errors ?? {}
            return
        }
        if (!res.ok) { passwordErrors.value = { current_password: ['Failed to update password.'] }; return }
        passwordSuccess.value = 'Password updated.'
        currentPassword.value = ''
        newPassword.value = ''
        newPasswordConfirmation.value = ''
    } catch {
        passwordErrors.value = { current_password: ['Network error.'] }
    } finally {
        passwordLoading.value = false
    }
}

async function deleteAccount() {
    if (!confirm('This action is irreversible. Delete your account?')) return
    deleteErrors.value = {}
    deleteLoading.value = true
    try {
        const res = await fetch('/profile', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ password: deletePassword.value }),
        })
        if (res.status === 422) {
            const data = await res.json()
            deleteErrors.value = data.errors ?? {}
            return
        }
        if (!res.ok) { deleteErrors.value = { password: ['Failed to delete account.'] }; return }
        document.body.dataset.authenticated = '0'
        router.push('/login')
    } catch {
        deleteErrors.value = { password: ['Network error.'] }
    } finally {
        deleteLoading.value = false
    }
}

async function logout() {
    try {
        await fetch('/logout', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } })
    } catch { /* ok */ }
    document.body.dataset.authenticated = '0'
    router.push('/login')
}

onMounted(fetchProfile)
</script>

<template>
    <div class="mx-auto w-full max-w-7xl space-y-8 py-2">
        <h2 class="text-xl font-semibold text-white">Profile Settings</h2>

        <div class="rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur-xl">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-[0.2em] text-cyan-300/80">Account Information</h3>

            <div v-if="profileSuccess" class="mb-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">{{ profileSuccess }}</div>

            <form @submit.prevent="updateProfile" class="space-y-4">
                <div>
                    <label for="name" class="mb-1.5 block text-sm font-medium text-slate-200">Name</label>
                    <input
                        id="name"
                        v-model="name"
                        type="text"
                        required
                        class="w-full rounded-xl border bg-slate-900/80 px-3 py-2.5 text-slate-100 outline-none transition focus:ring-2"
                        :class="profileErrors.name ? 'border-red-500/60 focus:border-red-400 focus:ring-red-500/40' : 'border-white/10 focus:border-cyan-400 focus:ring-cyan-500/40'"
                    />
                    <p v-if="profileErrors.name" class="mt-1.5 text-xs text-red-400">{{ profileErrors.name[0] }}</p>
                </div>

                <div>
                    <label for="profile-email" class="mb-1.5 block text-sm font-medium text-slate-200">Email</label>
                    <input
                        id="profile-email"
                        v-model="email"
                        type="email"
                        required
                        class="w-full rounded-xl border bg-slate-900/80 px-3 py-2.5 text-slate-100 outline-none transition focus:ring-2"
                        :class="profileErrors.email ? 'border-red-500/60 focus:border-red-400 focus:ring-red-500/40' : 'border-white/10 focus:border-cyan-400 focus:ring-cyan-500/40'"
                    />
                    <p v-if="profileErrors.email" class="mt-1.5 text-xs text-red-400">{{ profileErrors.email[0] }}</p>
                </div>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        :disabled="profileLoading"
                        class="rounded-xl bg-cyan-400 px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300 disabled:opacity-50"
                    >
                        {{ profileLoading ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </form>
        </div>

        <div class="rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur-xl">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-[0.2em] text-cyan-300/80">Change Password</h3>

            <div v-if="passwordSuccess" class="mb-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">{{ passwordSuccess }}</div>

            <form @submit.prevent="updatePassword" class="space-y-4">
                <div>
                    <label for="current-password" class="mb-1.5 block text-sm font-medium text-slate-200">Current Password</label>
                    <input
                        id="current-password"
                        v-model="currentPassword"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="w-full rounded-xl border bg-slate-900/80 px-3 py-2.5 text-slate-100 outline-none transition focus:ring-2"
                        :class="passwordErrors.current_password ? 'border-red-500/60 focus:border-red-400 focus:ring-red-500/40' : 'border-white/10 focus:border-cyan-400 focus:ring-cyan-500/40'"
                    />
                    <p v-if="passwordErrors.current_password" class="mt-1.5 text-xs text-red-400">{{ passwordErrors.current_password[0] }}</p>
                </div>

                <div>
                    <label for="new-password" class="mb-1.5 block text-sm font-medium text-slate-200">New Password</label>
                    <input
                        id="new-password"
                        v-model="newPassword"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="w-full rounded-xl border bg-slate-900/80 px-3 py-2.5 text-slate-100 outline-none transition focus:ring-2"
                        :class="passwordErrors.password ? 'border-red-500/60 focus:border-red-400 focus:ring-red-500/40' : 'border-white/10 focus:border-cyan-400 focus:ring-cyan-500/40'"
                    />
                    <p v-if="passwordErrors.password" class="mt-1.5 text-xs text-red-400">{{ passwordErrors.password[0] }}</p>
                </div>

                <div>
                    <label for="new-password-confirmation" class="mb-1.5 block text-sm font-medium text-slate-200">Confirm New Password</label>
                    <input
                        id="new-password-confirmation"
                        v-model="newPasswordConfirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="w-full rounded-xl border border-white/10 bg-slate-900/80 px-3 py-2.5 text-slate-100 outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-500/40"
                    />
                </div>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        :disabled="passwordLoading"
                        class="rounded-xl bg-cyan-400 px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300 disabled:opacity-50"
                    >
                        {{ passwordLoading ? 'Updating...' : 'Update Password' }}
                    </button>
                </div>
            </form>
        </div>

        <div class="rounded-2xl border border-red-500/20 bg-red-500/5 p-6 backdrop-blur-xl">
            <h3 class="mb-2 text-sm font-semibold uppercase tracking-[0.2em] text-red-400">Danger Zone</h3>
            <p class="mb-4 text-sm text-slate-400">Permanently delete your account and all associated data.</p>

            <form @submit.prevent="deleteAccount" class="space-y-4">
                <div>
                    <label for="delete-password" class="mb-1.5 block text-sm font-medium text-slate-200">Confirm your password</label>
                    <input
                        id="delete-password"
                        v-model="deletePassword"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="w-full rounded-xl border bg-slate-900/80 px-3 py-2.5 text-slate-100 outline-none transition focus:ring-2"
                        :class="deleteErrors.password ? 'border-red-500/60 focus:border-red-400 focus:ring-red-500/40' : 'border-white/10 focus:border-cyan-400 focus:ring-cyan-500/40'"
                    />
                    <p v-if="deleteErrors.password" class="mt-1.5 text-xs text-red-400">{{ deleteErrors.password[0] }}</p>
                </div>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        :disabled="deleteLoading"
                        class="rounded-xl bg-red-500 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-red-400 disabled:opacity-50"
                    >
                        {{ deleteLoading ? 'Deleting...' : 'Delete Account' }}
                    </button>
                </div>
            </form>
        </div>

        <div class="flex justify-center pb-8">
            <button
                @click="logout"
                class="rounded-xl border border-white/10 px-5 py-2.5 text-sm font-medium text-slate-300 transition hover:border-white/20 hover:text-white"
            >
                Log out
            </button>
        </div>
    </div>
</template>
