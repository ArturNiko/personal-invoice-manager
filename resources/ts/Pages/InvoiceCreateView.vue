<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useRoute } from 'vue-router';
import axios from 'axios';

import { formatMoney, normalizeDateValue } from '@/Utils/Helpers';
import { locale, t } from '@/i18n';
import {
    currencyOptions,
    getInvoiceRecurrenceOptions,
    getInvoiceStatusOptions,
    getInvoiceTypeOptions,
    getRecurringPreviewLabel,
    getStatusLabel,
} from '@/Utils/Consts';

import InputText from '@/Components/Form/InputText.vue';
import InputDate from '@/Components/Form/InputDate.vue';
import InputBalance from '@/Components/Form/InputBalance.vue';
import InputSelect from '@/Components/Form/InputSelect.vue';
import Button from '@/Components/Button.vue';
import FileInput from '@/Components/Form/FileInput.vue';

import {
    type InvoiceEvent,
    type InvoiceForm,
    InvoiceRecurrence,
    InvoiceStatuses,
    InvoiceTypes,
} from '@/Types/Invoice';
import { Currency } from '@/Types/Currency';

const router = useRouter();
const route = useRoute();

const createMode = ref<'manual' | 'import'>('manual');
const isSubmitting = ref(false);
const isImporting = ref(false);
const submitError = ref('');
const submitSuccess = ref('');
const importSuccess = ref('');
const importFile = ref<File | null>(null);

const form = reactive<InvoiceForm>({
    title: '',
    type: InvoiceTypes.ONE_TIME,
    status: InvoiceStatuses.PENDING,
    start_date: new Date().toISOString().slice(0, 10),
    end_date: '',
    currency: Currency.EUR,
    recurrence: InvoiceRecurrence.MONTHLY,
    price: '',
});

const isGerman = computed(() => locale.value === 'de');

const invoiceTypeOptions = computed(() => getInvoiceTypeOptions(t));

const invoiceStatusOptions = computed(() => getInvoiceStatusOptions(t));

const invoiceRecurrenceOptions = computed(() => getInvoiceRecurrenceOptions(t));

const isRecurring = computed(() => form.type === InvoiceTypes.RECURRING);
const isImportMode = computed(() => createMode.value === 'import');

const isRecurringRangeInvalid = computed(() => {
    if (!isRecurring.value || !form.start_date || !form.end_date) return false;

    return new Date(form.start_date) > new Date(form.end_date);
});

const recurringPreviewLabel = computed(() => {
    if (!isRecurring.value)
        return `${t('invoices.previewPrice')}: ${formatMoney(Number(form.price || '0'), form.currency)}`;

    return getRecurringPreviewLabel(
        form.recurrence,
        t,
        form.start_date,
        form.end_date,
    );
});

const getTypeLabel = (type: InvoiceTypes) =>
    type === InvoiceTypes.RECURRING ? t('invoices.recurring') : t('invoices.oneTime');

const getCurrencyLabel = (currency: Currency) => currency;

const dateInputLabel = computed(() => isRecurring.value ? (isGerman.value ? 'Startdatum' : 'Start date') : (isGerman.value ? 'Datum' : 'Date'),);
const priceInputLabel = computed(() => isRecurring.value ? t('invoices.occurrencePrice') : t('invoices.price'),);

const setCreateMode = (mode: 'manual' | 'import') => {
    createMode.value = mode;
    submitError.value = '';
    submitSuccess.value = '';
    importSuccess.value = '';
};

watch(
    () => route.query.date,
    (date) => {
        const normalizedDate =
            typeof date === 'string' ? normalizeDateValue(date) : '';

        if (normalizedDate) form.start_date = normalizedDate;
    },
    { immediate: true },
);

const resetForm = () => {
    form.title = '';
    form.type = InvoiceTypes.ONE_TIME;
    form.status = InvoiceStatuses.PENDING;
    form.start_date = new Date().toISOString().slice(0, 10);
    form.end_date = '';
    form.currency = Currency.EUR;
    form.recurrence = InvoiceRecurrence.MONTHLY;
    form.price = '';
    importFile.value = null;
    importSuccess.value = '';
};

const submitImport = async () => {
    if (!importFile.value) {
        submitError.value = t('invoices.choosePdfPrompt');
        return;
    }

    isImporting.value = true;
    submitError.value = '';
    importSuccess.value = '';

    const formData = new FormData();
    formData.append('invoice', importFile.value);

    try {
        const response = await axios.post('/invoices/import', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        importSuccess.value =
            response.data?.message ?? t('invoices.invoiceProcessing');
        importFile.value = null;
    } catch (error: any) {
        submitError.value =
            error?.response?.data?.message ?? t('invoices.importFailed');
    } finally {
        isImporting.value = false;
    }
};

const submitForm = async () => {
    if (isImportMode.value) {
        await submitImport();
        return;
    }

    if (isRecurringRangeInvalid.value) {
        submitError.value = t('invoices.recurringRangeError');
        return;
    }

    isSubmitting.value = true;
    submitError.value = '';
    submitSuccess.value = '';

    const payload: Partial<InvoiceEvent> &
        Record<string, string | number | undefined> = {
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
        await axios.post('/invoices', payload);
        submitSuccess.value = t('invoices.invoiceCreated');
        resetForm();
        await router.push({ path: '/list', query: { updated: '1' } });
    } catch (error: any) {
        submitError.value =
            error?.response?.data?.message ?? t('invoices.failedToCreateInvoice');
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <section class="flex h-full min-h-[36rem] flex-col overflow-hidden rounded-[2rem] border border-slate-900/10 bg-slate-900/5 shadow-2xl shadow-slate-500/40 backdrop-blur-xl dark:border-white/10 dark:bg-white/5 dark:shadow-black/40">
        <div class="border-b border-slate-900/10 px-5 py-4 sm:px-6 dark:border-white/10">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ t('invoices.createInvoice') }}</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">
                {{ t('invoices.createInvoiceSubtitle') }}
            </p>
        </div>

        <form
            class="grid gap-6 p-4 sm:p-6 lg:grid-cols-[1.2fr_0.8fr]"
            @submit.prevent="submitForm"
        >
            <div class="space-y-6">
                <div class="rounded-2xl border border-slate-900/10 bg-white/70 p-1 shadow-lg shadow-slate-500/30 dark:border-white/10 dark:bg-slate-900/70 dark:shadow-black/30">
                    <div class="grid grid-cols-2 gap-1">
                        <button
                            type="button"
                            class="rounded-xl px-4 py-2 text-sm font-medium transition"
                            :class="
                                createMode === 'manual'
                                    ? 'bg-slate-900 text-white shadow dark:bg-white dark:text-slate-950'
                                    : 'text-slate-700 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white'
                            "
                            @click="setCreateMode('manual')"
                        >
                            {{ t('invoices.manualEntry') }}
                        </button>
                        <button
                            type="button"
                            class="rounded-xl px-4 py-2 text-sm font-medium transition"
                            :class="
                                createMode === 'import'
                                    ? 'bg-slate-900 text-white shadow dark:bg-white dark:text-slate-950'
                                    : 'text-slate-700 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white'
                            "
                            @click="setCreateMode('import')"
                        >
                            {{ t('invoices.importPdf') }}
                        </button>
                    </div>
                </div>

                <div v-if="createMode === 'manual'" class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <InputText
                            v-model="form.title"
                            :label="t('invoices.title')"
                            required
                            :placeholder="t('invoices.titlePlaceholder')"
                        />
                        <InputBalance
                            v-model="form.price"
                            v-model:currency="form.currency"
                            :label="priceInputLabel"
                            :currency-options="currencyOptions"
                            placeholder="100.00"
                        />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <InputSelect
                            v-model="form.type"
                            :label="t('invoices.type')"
                            :options="invoiceTypeOptions"
                        />
                        <InputSelect
                            v-model="form.status"
                            :label="t('invoices.status')"
                            :options="invoiceStatusOptions"
                        />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <InputDate
                            v-model="form.start_date"
                            :label="dateInputLabel"
                            required
                        />
                        <InputDate
                            v-if="isRecurring"
                            v-model="form.end_date"
                            :label="t('invoices.endDate')"
                        />
                    </div>

                    <p
                        v-if="isRecurringRangeInvalid"
                        class="rounded-xl border border-red-600/30 bg-red-600/10 p-3 text-sm text-red-800 dark:border-red-400/30 dark:bg-red-500/10 dark:text-red-100"
                    >
                        {{ t('invoices.recurringRangeError') }}
                    </p>

                    <InputSelect
                        v-model="form.recurrence"
                        :label="t('invoices.recurrence')"
                        :options="invoiceRecurrenceOptions"
                        :disabled="!isRecurring"
                    />
                </div>

                <div v-else class="space-y-4">
                    <FileInput
                        v-model="importFile"
                        :label="t('invoices.invoicePdf')"
                        :button-label="t('invoices.choosePdf')"
                        :hint="t('invoices.pdfHint')"
                    />

                    <p
                        v-if="submitError"
                        class="rounded-xl border border-red-600/30 bg-red-600/10 p-3 text-sm text-red-800 dark:border-red-400/30 dark:bg-red-500/10 dark:text-red-100"
                    >
                        {{ submitError }}
                    </p>
                    <p
                        v-if="importSuccess"
                        class="rounded-xl border border-emerald-600/30 bg-emerald-600/10 p-3 text-sm text-emerald-800 dark:border-emerald-400/30 dark:bg-emerald-500/10 dark:text-emerald-100"
                    >
                        {{ importSuccess }}
                    </p>
                </div>
            </div>

            <aside class="rounded-2xl border border-dashed border-slate-900/10 bg-slate-100/30 p-5 dark:border-white/10 dark:bg-slate-950/30">
                <p class="text-xs uppercase tracking-[0.25em] text-slate-600 dark:text-slate-400">
                    {{ t('invoices.previewSummary') }}
                </p>
                <h3 class="mt-2 text-xl font-semibold text-slate-900 dark:text-white">
                    {{
                        createMode === 'import'
                            ? t('invoices.invoiceImportPreview')
                            : t('invoices.invoicePreview')
                    }}
                </h3>
                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-400">
                    {{
                        createMode === 'import'
                            ? t('invoices.invoiceImportDescription')
                            : t('invoices.invoiceManualDescription')
                    }}
                </p>

                <div class="mt-6 space-y-3 text-sm text-slate-700 dark:text-slate-300">
                    <div class="rounded-xl border border-slate-900/10 bg-slate-900/5 p-3 dark:border-white/10 dark:bg-white/5">
                        {{ t('invoices.previewType') }}: {{ getTypeLabel(form.type) }}
                    </div>
                    <div class="rounded-xl border border-slate-900/10 bg-slate-900/5 p-3 dark:border-white/10 dark:bg-white/5">
                        {{ t('invoices.previewStatus') }}: {{ getStatusLabel(form.status) }}
                    </div>
                    <div class="rounded-xl border border-slate-900/10 bg-slate-900/5 p-3 dark:border-white/10 dark:bg-white/5">
                        {{ t('invoices.previewCurrency') }}: {{ getCurrencyLabel(form.currency) }}
                    </div>
                    <div class="rounded-xl border border-slate-900/10 bg-slate-900/5 p-3 dark:border-white/10 dark:bg-white/5">
                        {{ recurringPreviewLabel }}
                    </div>
                </div>

                <p
                    v-if="submitError && createMode === 'manual'"
                    class="mt-6 rounded-xl border border-red-600/30 bg-red-600/10 p-3 text-sm text-red-800 dark:border-red-400/30 dark:bg-red-500/10 dark:text-red-100"
                >
                    {{ submitError }}
                </p>
                <p
                    v-if="submitSuccess && createMode === 'manual'"
                    class="mt-6 rounded-xl border border-emerald-600/30 bg-emerald-600/10 p-3 text-sm text-emerald-800 dark:border-emerald-400/30 dark:bg-emerald-500/10 dark:text-emerald-100"
                >
                    {{ submitSuccess }}
                </p>

                <Button
                    v-if="createMode === 'manual'"
                    type="submit"
                    :disabled="isSubmitting || isRecurringRangeInvalid"
                    variant="solid"
                    block
                    class="mt-6"
                >
                    {{ isSubmitting ? t('invoices.saving') : t('invoices.createInvoice') }}
                </Button>
                <Button
                    v-else
                    type="button"
                    :disabled="isImporting || !importFile"
                    variant="solid"
                    block
                    class="mt-6"
                    @click="submitImport"
                >
                    {{ isImporting ? t('invoices.importing') : t('invoices.importInvoice') }}
                </Button>
            </aside>
        </form>
    </section>
</template>
