<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { useRoute, useRouter } from 'vue-router';

import { normalizeDateValue, formatMoney } from '@/Utils/Helpers';
import { locale, t } from '@/i18n';
import {
    currencyOptions,
} from '@/Utils/Consts';

import InputText from '@/Components/Form/InputText.vue';
import InputDate from '@/Components/Form/InputDate.vue';
import InputBalance from '@/Components/Form/InputBalance.vue';
import InputSelect from '@/Components/Form/InputSelect.vue';
import Button from '@/Components/Button.vue';
import ConfirmationDialog from '@/Components/ConfirmationDialog.vue';

import {
    type InvoiceEvent,
    type InvoiceForm,
    InvoiceTypes,
    InvoiceStatuses,
    InvoiceRecurrence,
} from '@/Types/Invoice';
import { Currency } from '@/Types/Currency';

const router = useRouter();
const route = useRoute();
const invoice = ref<InvoiceEvent | null>(null);
const loading = ref(true);
const loadError = ref('');
const isSubmitting = ref(false);
const isDeleting = ref(false);
const isDeleteDialogOpen = ref(false);
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

const invoiceTypeOptions = computed(() => [
    { label: t('invoices.oneTime'), value: InvoiceTypes.ONE_TIME },
    { label: t('invoices.recurring'), value: InvoiceTypes.RECURRING },
]);

const invoiceStatusOptions = computed(() => [
    { label: t('invoices.pending'), value: InvoiceStatuses.PENDING },
    { label: t('invoices.paid'), value: InvoiceStatuses.PAID },
    { label: t('invoices.overdue'), value: InvoiceStatuses.OVERDUE },
]);

const invoiceRecurrenceOptions = computed(() => [
    { label: t('invoices.none'), value: InvoiceRecurrence.NONE },
    { label: t('invoices.weekly'), value: InvoiceRecurrence.WEEKLY },
    { label: t('invoices.biweekly'), value: InvoiceRecurrence.BIWEEKLY },
    { label: t('invoices.monthly'), value: InvoiceRecurrence.MONTHLY },
    { label: t('invoices.quarterly'), value: InvoiceRecurrence.QUARTERLY },
    { label: t('invoices.semiannual'), value: InvoiceRecurrence.SEMIANNUAL },
    { label: t('invoices.yearly'), value: InvoiceRecurrence.YEARLY },
]);

const syncFormFromInvoice = (currentInvoice: InvoiceEvent) => {
    form.title = currentInvoice.title;
    form.type = currentInvoice.type;
    form.status = currentInvoice.status;
    form.start_date =
        normalizeDateValue(currentInvoice.start_date) ||
        new Date().toISOString().slice(0, 10);
    form.end_date = normalizeDateValue(currentInvoice.end_date);
    form.currency = currentInvoice.currency;
    form.recurrence = currentInvoice.recurrence ?? InvoiceRecurrence.MONTHLY;
    form.price = String(currentInvoice.price ?? '');
};

const loadInvoice = async () => {
    const invoiceId = route.params.id;

    if (typeof invoiceId !== 'string') {
        loadError.value = t('invoices.missingInvoiceId');
        loading.value = false;
        return;
    }

    try {
        const response = await axios.get<InvoiceEvent>(
            `/invoices/${invoiceId}`,
        );
        invoice.value = response.data;
        syncFormFromInvoice(response.data);
    } catch (error: any) {
        loadError.value =
            error?.response?.data?.message ?? t('invoices.failedToLoadInvoice');
    } finally {
        loading.value = false;
    }
};

const isRecurring = computed(() => form.type === InvoiceTypes.RECURRING);

const isRecurringRangeInvalid = computed(() => {
    if (!isRecurring.value || !form.start_date || !form.end_date) return false;

    return new Date(form.start_date) > new Date(form.end_date);
});

const recurringPreviewLabel = computed(() => {
    if (!isRecurring.value)
        return `${t('invoices.previewPrice')}: ${formatMoney(Number(form.price || '0'), form.currency)}`;

    if (form.start_date && !form.end_date)
        return t('invoices.recurringScheduleEndless');
    if (!form.start_date || !form.end_date)
        return t('invoices.recurringScheduleUnset');

    return `${t('invoices.previewSchedule')}: ${getRecurrenceLabel(form.recurrence)}`;
});

const getTypeLabel = (type: InvoiceTypes) =>
    type === InvoiceTypes.RECURRING ? t('invoices.recurring') : t('invoices.oneTime');

const getStatusLabel = (status: InvoiceStatuses) => {
    switch (status) {
        case InvoiceStatuses.PAID:
            return t('invoices.paid');
        case InvoiceStatuses.OVERDUE:
            return t('invoices.overdue');
        default:
            return t('invoices.pending');
    }
};

const getCurrencyLabel = (currency: Currency) => currency;

const dateInputLabel = computed(() =>
    isRecurring.value ? t('invoices.startDate') : t('invoices.date'),
);

const getRecurrenceLabel = (recurrence: InvoiceRecurrence) => {
    switch (recurrence) {
        case InvoiceRecurrence.WEEKLY:
            return t('invoices.weekly');
        case InvoiceRecurrence.BIWEEKLY:
            return t('invoices.biweekly');
        case InvoiceRecurrence.MONTHLY:
            return t('invoices.monthly');
        case InvoiceRecurrence.QUARTERLY:
            return t('invoices.quarterly');
        case InvoiceRecurrence.SEMIANNUAL:
            return t('invoices.semiannual');
        case InvoiceRecurrence.YEARLY:
            return t('invoices.yearly');
        default:
            return t('invoices.none');
    }
};

const saveButtonLabel = computed(() =>
    locale.value === 'de' ? 'Rechnung speichern' : 'Save invoice',
);

const priceInputLabel = computed(() =>
    isRecurring.value ? t('invoices.occurrencePrice') : t('invoices.price'),
);

const submitForm = async () => {
    if (!invoice.value?.id) {
        submitError.value = t('invoices.missingInvoiceId');
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
        await axios.put(`/invoices/${invoice.value.id}`, payload);
        submitSuccess.value = t('invoices.invoiceUpdated');
        await router.push({ path: '/list', query: { updated: '1' } });
    } catch (error: any) {
        submitError.value =
            error?.response?.data?.message ?? t('invoices.failedToUpdateInvoice');
    } finally {
        isSubmitting.value = false;
    }
};

const deleteInvoice = async () => {
    if (!invoice.value?.id) return;

    isDeleteDialogOpen.value = true;
};

const closeDeleteDialog = () => {
    if (isDeleting.value) return;

    isDeleteDialogOpen.value = false;
};

const confirmDeleteInvoice = async () => {
    if (!invoice.value?.id) return;

    isDeleting.value = true;
    submitError.value = '';

    try {
        await axios.delete(`/invoices/${invoice.value.id}`);
        invoice.value = null;
        isDeleteDialogOpen.value = false;
        await router.push({ path: '/list', query: { deleted: '1' } });
    } catch (error: any) {
        submitError.value =
            error?.response?.data?.message ?? t('invoices.failedToDeleteInvoice');
    } finally {
        isDeleting.value = false;
    }
};

onMounted(loadInvoice);
</script>

<template>
    <section
        class="flex h-full min-h-[36rem] flex-col overflow-hidden rounded-[2rem] border border-slate-900/10 bg-slate-900/5 shadow-2xl shadow-slate-500/40 backdrop-blur-xl dark:border-white/10 dark:bg-white/5 dark:shadow-black/40"
    >
        <div
            v-if="loading"
            class="rounded-[2rem] border border-slate-900/10 bg-slate-900/5 p-6 text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-slate-300"
        >
            {{ t('invoices.loadingInvoice') }}
        </div>

        <div
            v-else-if="loadError"
            class="rounded-[2rem] border border-red-600/30 bg-red-600/10 p-6 text-red-800 dark:border-red-400/30 dark:bg-red-500/10 dark:text-red-100"
        >
            {{ loadError }}
        </div>
        <div v-else>
            <div class="border-b border-slate-900/10 px-5 py-4 sm:px-6 dark:border-white/10">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ t('invoices.editInvoice') }}</h2>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    {{ t('invoices.editInvoiceSubtitle') }}
                </p>
            </div>

            <form
                class="grid gap-6 p-4 sm:p-6 lg:grid-cols-[1.2fr_0.8fr]"
                @submit.prevent="submitForm"
            >
                <div class="space-y-6">
                    <div class="space-y-4">
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
                </div>

                <aside
                    class="rounded-2xl border border-dashed border-slate-900/10 bg-slate-100/30 p-5 dark:border-white/10 dark:bg-slate-950/30"
                >
                    <p
                        class="text-xs uppercase tracking-[0.25em] text-slate-600 dark:text-slate-400"
                    >
                        {{ t('invoices.previewSummary') }}
                    </p>
                    <h3 class="mt-2 text-xl font-semibold text-slate-900 dark:text-white">
                        {{ t('invoices.editInvoice') }}
                    </h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-400">
                        {{ t('invoices.invoiceManualDescription') }}
                    </p>

                    <div class="mt-6 space-y-3 text-sm text-slate-700 dark:text-slate-300">
                        <div
                            class="rounded-xl border border-slate-900/10 bg-slate-900/5 p-3 dark:border-white/10 dark:bg-white/5"
                        >
                            {{ t('invoices.previewType') }}: {{ getTypeLabel(form.type) }}
                        </div>
                        <div
                            class="rounded-xl border border-slate-900/10 bg-slate-900/5 p-3 dark:border-white/10 dark:bg-white/5"
                        >
                            {{ t('invoices.previewStatus') }}: {{ getStatusLabel(form.status) }}
                        </div>
                        <div
                            class="rounded-xl border border-slate-900/10 bg-slate-900/5 p-3 dark:border-white/10 dark:bg-white/5"
                        >
                            {{ t('invoices.previewCurrency') }}: {{ getCurrencyLabel(form.currency) }}
                        </div>
                        <div
                            class="rounded-xl border border-slate-900/10 bg-slate-900/5 p-3 dark:border-white/10 dark:bg-white/5"
                        >
                            {{ recurringPreviewLabel }}
                        </div>
                    </div>

                    <p
                        v-if="submitError"
                        class="mt-6 rounded-xl border border-red-600/30 bg-red-600/10 p-3 text-sm text-red-800 dark:border-red-400/30 dark:bg-red-500/10 dark:text-red-100"
                    >
                        {{ submitError }}
                    </p>
                    <p
                        v-if="submitSuccess"
                        class="mt-6 rounded-xl border border-emerald-600/30 bg-emerald-600/10 p-3 text-sm text-emerald-800 dark:border-emerald-400/30 dark:bg-emerald-500/10 dark:text-emerald-100"
                    >
                        {{ submitSuccess }}
                    </p>

                    <Button
                        type="submit"
                        :disabled="isSubmitting || isRecurringRangeInvalid"
                        variant="solid"
                        block
                        class="mt-6"
                    >
                        {{ isSubmitting ? t('invoices.saving') : saveButtonLabel }}
                    </Button>

                    <Button
                        v-if="invoice?.id"
                        type="button"
                        :disabled="isDeleting"
                        variant="danger"
                        block
                        class="mt-3"
                        @click="deleteInvoice"
                    >
                        {{ isDeleting ? t('invoices.deleting') : t('invoices.deleteInvoiceButton') }}
                    </Button>
                </aside>
            </form>
            <ConfirmationDialog
                :open="isDeleteDialogOpen"
                :busy="isDeleting"
                :title="t('invoices.deleteInvoice')"
                :message="t('invoices.deleteWarning')"
                :confirm-label="t('invoices.delete')"
                :cancel-label="t('invoices.keepIt')"
                @close="closeDeleteDialog"
                @confirm="confirmDeleteInvoice"
            />
        </div>
    </section>
</template>
