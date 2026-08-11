<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { useRoute, useRouter } from 'vue-router';

import { normalizeDateValue, formatMoney } from '@/Utils/Helpers';
import { calculateOccurrencesCount } from '@/Utils/Occurrences';
import {
    currencyOptions,
    invoiceRecurrenceOptions,
    invoiceStatusOptions,
    invoiceTypeOptions,
} from '@/Utils/Consts';

import InputText from '@/Components/Form/InputText.vue';
import InputDate from '@/Components/Form/InputDate.vue';
import InputBalance from '@/Components/Form/InputBalance.vue';
import InputSelect from '@/Components/Form/InputSelect.vue';
import Button from '@/Components/Button.vue';

import { 
    type InvoiceEvent, 
    type InvoiceForm, 
    InvoiceTypes, 
    InvoiceStatuses, 
    InvoiceRecurrence 
} from '@/Types/Invoice';
import { Currency } from '@/Types/Currency';


const router = useRouter();
const route = useRoute();
const invoice = ref<InvoiceEvent | null>(null);
const loading = ref(true);
const loadError = ref('');
const isSubmitting = ref(false);
const isDeleting = ref(false);
const submitError = ref('');
const submitSuccess = ref('');

const createEmptyForm = (): InvoiceForm => ({
    title: '',
    type: InvoiceTypes.ONE_TIME,
    status: InvoiceStatuses.PENDING,
    start_date: new Date().toISOString().slice(0, 10),
    end_date: '',
    currency: Currency.EUR,
    recurrence: InvoiceRecurrence.MONTHLY,
    price: '',
});

const form = reactive<InvoiceForm>(createEmptyForm());

const syncFormFromInvoice = (currentInvoice: InvoiceEvent) => {
    form.title = currentInvoice.title
    form.type = currentInvoice.type
    form.status = currentInvoice.status
    form.start_date = normalizeDateValue(currentInvoice.start_date) || new Date().toISOString().slice(0, 10)
    form.end_date = normalizeDateValue(currentInvoice.end_date)
    form.currency = currentInvoice.currency
    form.recurrence = currentInvoice.recurrence ?? InvoiceRecurrence.MONTHLY
    form.price = String(currentInvoice.price ?? '')
};

const loadInvoice = async () => {
    const invoiceId = route.params.id;

    if (typeof invoiceId !== 'string') {
        loadError.value = 'Missing invoice id.';
        loading.value = false;
        return;
    }

    try {
        const response = await axios.get<InvoiceEvent>(`/invoices/${invoiceId}`);
        invoice.value = response.data;
        syncFormFromInvoice(response.data);
    } 
    catch (error: any) {
        loadError.value = error?.response?.data?.message ?? 'Failed to load invoice.';
    } 
    finally {
        loading.value = false;
    }
};

const isRecurring = computed(() => form.type === InvoiceTypes.RECURRING);

const isRecurringRangeInvalid = computed(() => {
    if (!isRecurring.value || !form.start_date || !form.end_date) return false;

    return new Date(form.start_date) > new Date(form.end_date);
});

const recurringPreviewLabel = computed(() => {
    if (!isRecurring.value) return `Price: ${formatMoney(Number(form.price || '0'), form.currency)}`;

    if (form.start_date && !form.end_date) return 'Recurring schedule: Endless';
    if (!form.start_date || !form.end_date) return 'Recurring schedule: not set';

    return `Occurrences: ${calculateOccurrencesCount(form.start_date, form.end_date, form.recurrence)}`;
});

const priceInputLabel = computed(() => (isRecurring.value ? 'Occurrence price' : 'Price'));
const dateInputLabel = computed(() => (isRecurring.value ? 'Start date' : 'Date'));

const submitForm = async () => {
    if (!invoice.value?.id) {
        submitError.value = 'Missing invoice id.';
        return;
    }

    if (isRecurringRangeInvalid.value) {
        submitError.value = 'Recurring end date must be on or after the start date.';
        return;
    }

    isSubmitting.value = true;
    submitError.value = '';
    submitSuccess.value = '';

    const payload: Partial<InvoiceEvent> & Record<string, string | number | undefined> = {
        title: form.title,
        type: form.type,
        status: form.status,
        start_date: form.start_date,
        currency: form.currency,
        recurrence: isRecurring.value ? form.recurrence : undefined,
        end_date: isRecurring.value ? form.end_date : undefined,
        price: Number(form.price),
    };

    try {
        await axios.put(`/invoices/${invoice.value.id}`, payload);
        submitSuccess.value = 'Invoice updated successfully.';
        await router.push({ path: '/list', query: { updated: '1' } });
    } 
    catch (error: any) {
        submitError.value = error?.response?.data?.message ?? 'Failed to update invoice.';
    } 
    finally {
        isSubmitting.value = false;
    }
};

const deleteInvoice = async () => {
    if (!invoice.value?.id) return;

    const confirmed = window.confirm('Delete this invoice? This cannot be undone.');

    if (!confirmed) return;

    isDeleting.value = true;
    submitError.value = '';

    try {
        await axios.delete(`/invoices/${invoice.value.id}`);
        invoice.value = null;
        await router.push({ path: '/list', query: { deleted: '1' } });
    }
    catch (error: any) {
        submitError.value = error?.response?.data?.message ?? 'Failed to delete invoice.';
    } 
    finally {
        isDeleting.value = false;
    }
};

onMounted(loadInvoice);
</script>

<template>
    <div
        v-if="loading"
        class="rounded-[2rem] border border-white/10 bg-white/5 p-6 text-slate-300"
    >
        Loading invoice...
    </div>

    <div
        v-else-if="loadError"
        class="rounded-[2rem] border border-red-400/30 bg-red-500/10 p-6 text-red-100"
    >
        {{ loadError }}
    </div>

    <section v-else class="flex h-full min-h-[36rem] flex-col overflow-hidden rounded-[2rem] border border-white/10 bg-white/5 shadow-2xl shadow-slate-950/40 backdrop-blur-xl">
        <div class="border-b border-white/10 px-5 py-4 sm:px-6">
            <h2 class="text-lg font-semibold text-white">Edit invoice</h2>
            <p class="text-sm text-slate-400">Update invoice details, status, or delete the record.</p>
        </div>

        <form class="grid gap-6 p-4 sm:p-6 lg:grid-cols-[1.2fr_0.8fr]" @submit.prevent="submitForm">
            <div class="space-y-6">
                <div class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <InputText v-model="form.title" label="Title" required placeholder="Subscription" />
                        <InputBalance v-model="form.price" v-model:currency="form.currency" :label="priceInputLabel" :currency-options="currencyOptions" placeholder="100.00" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <InputSelect v-model="form.type" label="Type" :options="invoiceTypeOptions" />
                        <InputSelect v-model="form.status" label="Status" :options="invoiceStatusOptions" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <InputDate v-model="form.start_date" :label="dateInputLabel" required />
                        <InputDate v-if="isRecurring" v-model="form.end_date" label="End date" />
                    </div>

                    <p v-if="isRecurringRangeInvalid" class="rounded-xl border border-red-400/30 bg-red-500/10 p-3 text-sm text-red-100">
                        Recurring end date must be on or after the start date.
                    </p>

                    <InputSelect v-model="form.recurrence" label="Recurrence" :options="invoiceRecurrenceOptions" :disabled="!isRecurring" />
                </div>
            </div>

            <aside class="rounded-2xl border border-dashed border-white/10 bg-slate-950/30 p-5">
                <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Preview</p>
                <h3 class="mt-2 text-xl font-semibold text-white">Edit invoice</h3>
                <p class="mt-2 text-sm leading-6 text-slate-400">The form adjusts automatically for one-time and recurring invoices.</p>

                <div class="mt-6 space-y-3 text-sm text-slate-300">
                    <div class="rounded-xl border border-white/10 bg-white/5 p-3">Type: {{ form.type }}</div>
                    <div class="rounded-xl border border-white/10 bg-white/5 p-3">Status: {{ form.status }}</div>
                    <div class="rounded-xl border border-white/10 bg-white/5 p-3">Currency: {{ form.currency }}</div>
                    <div class="rounded-xl border border-white/10 bg-white/5 p-3">
                        {{ recurringPreviewLabel }}
                    </div>
                </div>

                <p v-if="submitError" class="mt-6 rounded-xl border border-red-400/30 bg-red-500/10 p-3 text-sm text-red-100">
                    {{ submitError }}
                </p>
                <p v-if="submitSuccess" class="mt-6 rounded-xl border border-emerald-400/30 bg-emerald-500/10 p-3 text-sm text-emerald-100">
                    {{ submitSuccess }}
                </p>

                <Button type="submit" :disabled="isSubmitting || isRecurringRangeInvalid" variant="solid" block class="mt-6">
                    {{ isSubmitting ? 'Saving...' : 'Save invoice' }}
                </Button>

                <Button v-if="invoice?.id" type="button" :disabled="isDeleting" variant="danger" block class="mt-3" @click="deleteInvoice">
                    {{ isDeleting ? 'Deleting...' : 'Delete invoice' }}
                </Button>
            </aside>
        </form>
    </section>
</template>
