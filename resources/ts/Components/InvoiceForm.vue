<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'

import type { InvoiceEvent } from '@/Types/Invoice'
import InputText from '@/Components/Form/InputText.vue'
import InputDate from '@/Components/Form/InputDate.vue'
import InputBalance from '@/Components/Form/InputBalance.vue'
import InputSelect from '@/Components/Form/InputSelect.vue'


const router = useRouter()

const isSubmitting = ref(false)
const submitError = ref('')
const submitSuccess = ref('')

const form = reactive({
    title: '',
    type: 'one-time' as 'one-time' | 'recurring',
    status: 'pending',
    start_date: '',
    end_date: '',
    currency: 'EUR',
    recurrence: 'monthly',
    price_total: '',
    price_occurrence: '',
})

const typeSelectOptions = ['one-time', 'recurring'].map((value) => ({
    label: value === 'one-time' ? 'One-time' : 'Recurring',
    value,
}))

const recurrenceSelectOptions = ['weekly', 'biweekly', 'monthly', 'quarterly', 'semiannual', 'yearly'].map((value) => ({
    label: value.charAt(0).toUpperCase() + value.slice(1),
    value,
}))
const currencySelectOptions = ['EUR', 'USD', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'CNY', 'SEK', 'NZD', 'RUB', 'AMD'].map((value) => ({
    label: value,
    value,
}))

const isRecurring = computed(() => form.type === 'recurring')

const resetForm = () => {
    form.title = ''
    form.type = 'one-time'
    form.status = 'pending'
    form.start_date = ''
    form.end_date = ''
    form.currency = 'EUR'
    form.recurrence = 'monthly'
    form.price_total = ''
    form.price_occurrence = ''
}

const submitForm = async () => {
    isSubmitting.value = true
    submitError.value = ''
    submitSuccess.value = ''

    const payload: Partial<InvoiceEvent> & Record<string, string | number | undefined> = {
        title: form.title,
        type: form.type,
        status: form.status,
        start_date: form.start_date,
        currency: form.currency,
        recurrence: isRecurring.value ? form.recurrence : undefined,
        end_date: isRecurring.value ? form.end_date : undefined,
        price_total: !isRecurring.value ? Number(form.price_total) : undefined,
        price_occurrence: isRecurring.value ? Number(form.price_occurrence) : undefined,
    }

    try {
        await axios.post('/invoices', payload)
        submitSuccess.value = 'Invoice created successfully.'
        resetForm()
        await router.push({ path: '/list', query: { created: '1' } })
    } catch (error: any) {
        submitError.value = error?.response?.data?.message ?? 'Failed to create invoice.'
    } finally {
        isSubmitting.value = false
    }
}
</script>

<template>
    <section class="flex h-full min-h-[36rem] flex-col overflow-hidden rounded-[2rem] border border-white/10 bg-white/5 shadow-2xl shadow-slate-950/40 backdrop-blur-xl">
        <div class="border-b border-white/10 px-5 py-4 sm:px-6">
            <h2 class="text-lg font-semibold text-white">Create invoice</h2>
            <p class="text-sm text-slate-400">Add a new invoice and save it to the backend.</p>
        </div>

        <form class="grid gap-6 p-4 sm:p-6 lg:grid-cols-[1.2fr_0.8fr]" @submit.prevent="submitForm">
            <div class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <InputText v-model="form.title" label="Title" required placeholder="Subscription" />
                    <InputBalance
                        v-model="form.price_total"
                        v-model:currency="form.currency"
                        label="One-time price"
                        :currency-options="currencySelectOptions"
                        :disabled="isRecurring"
                        placeholder="100.00"
                    />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <InputSelect v-model="form.type" label="Type" :options="typeSelectOptions" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <InputDate v-model="form.start_date" label="Start date" required />
                    <InputDate v-model="form.end_date" label="End date" :required="isRecurring" />
                </div>

                <InputSelect v-model="form.recurrence" label="Recurrence" :options="recurrenceSelectOptions" :disabled="!isRecurring" />
            </div>

            <aside class="rounded-2xl border border-dashed border-white/10 bg-slate-950/30 p-5">
                <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Preview</p>
                <h3 class="mt-2 text-xl font-semibold text-white">New invoice</h3>
                <p class="mt-2 text-sm leading-6 text-slate-400">
                    The form adjusts automatically for one-time and recurring invoices.
                </p>

                <div class="mt-6 space-y-3 text-sm text-slate-300">
                    <div class="rounded-xl border border-white/10 bg-white/5 p-3">Type: {{ form.type }}</div>
                    <div class="rounded-xl border border-white/10 bg-white/5 p-3">Status: {{ form.status }}</div>
                    <div class="rounded-xl border border-white/10 bg-white/5 p-3">Currency: {{ form.currency }}</div>
                </div>

                <p v-if="submitError" class="mt-6 rounded-xl border border-red-400/30 bg-red-500/10 p-3 text-sm text-red-100">{{ submitError }}</p>
                <p v-if="submitSuccess" class="mt-6 rounded-xl border border-emerald-400/30 bg-emerald-500/10 p-3 text-sm text-emerald-100">{{ submitSuccess }}</p>

                <button
                    type="submit"
                    :disabled="isSubmitting"
                    class="mt-6 w-full rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {{ isSubmitting ? 'Creating...' : 'Create invoice' }}
                </button>
            </aside>
        </form>
    </section>
</template>
