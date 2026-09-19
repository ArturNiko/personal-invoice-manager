<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { useRoute, useRouter } from 'vue-router';

import { normalizeDateValue, formatMoney } from '@/Utils/Helpers';
import { locale, t } from '@/i18n';
import {
    currencyOptions,
    getInvoiceStatusOptions,
    getRecurringPreviewLabel,
    getStatusLabel,
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
    type InvoiceOccurrence,
    InvoiceTypes,
    InvoiceStatuses,
    InvoiceRecurrence,
} from '@/Types/Invoice';
import { Currency } from '@/Types/Currency';
import { f } from 'vue-router/dist/router-CWoNjPRp.mjs';

const router = useRouter();
const route = useRoute();
const invoice = ref<InvoiceEvent | null>(null);
const occurrences = ref<InvoiceOccurrence[]>([]);
const loading = ref(true);
const loadError = ref('');
const isSubmitting = ref(false);
const isDeleting = ref(false);
const updatingOccurrenceId = ref<number | null>(null);
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

const invoiceStatusOptions = computed(() => getInvoiceStatusOptions(t));

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

const getOccurrenceStatusSummary = (items: InvoiceOccurrence[]) => {
    if (!items.length) {
        return InvoiceStatuses.PENDING;
    }

    if (items.every((occurrence) => occurrence.status === InvoiceStatuses.PAID)) {
        return InvoiceStatuses.PAID;
    }

    if (items.some((occurrence) => occurrence.status === InvoiceStatuses.OVERDUE)) {
        return InvoiceStatuses.OVERDUE;
    }

    return InvoiceStatuses.PENDING;
};

const formatOccurrenceDate = (value?: string | null) => {
    if (!value) {
        return '—';
    }

    const trimmed = String(value).trim();
    const parsedDate =
        /^\d{4}-\d{2}-\d{2}$/.test(trimmed)
            ? new Date(`${trimmed}T12:00:00`)
            : new Date(trimmed);

    if (Number.isNaN(parsedDate.getTime())) {
        return trimmed;
    }

    return parsedDate.toLocaleDateString(
        locale.value === 'de' ? 'de-DE' : 'en-US',
        { dateStyle: 'medium' },
    );
};

const loadOccurrences = async () => {
    const invoiceId = route.params.id;

    if (typeof invoiceId !== 'string') {
        return;
    }

    try {
        const response = await axios.get<InvoiceOccurrence[]>(
            `/invoices/${invoiceId}/occurrences`,
        );
        occurrences.value = response.data;
    } catch (error: any) {
        occurrences.value = [];
    }
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
        await loadOccurrences();

        if (response.data.type === InvoiceTypes.RECURRING) {
            form.status = getOccurrenceStatusSummary(occurrences.value);
        }
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

    return getRecurringPreviewLabel(
        form.recurrence,
        t,
        form.start_date,
        form.end_date,
    );
});

const getTypeLabel = (type: InvoiceTypes) =>
    type === InvoiceTypes.RECURRING ? t('invoices.recurring') : t('invoices.oneTime');

const recurringOccurrenceStatusOptions = computed(() => [
    { label: t('invoices.pending'), value: InvoiceStatuses.PENDING },
    { label: t('invoices.paid'), value: InvoiceStatuses.PAID },
    { label: t('invoices.overdue'), value: InvoiceStatuses.OVERDUE },
]);

const updateOccurrenceStatus = async (
    occurrenceId: number,
    nextStatus: InvoiceStatuses,
) => {
    const invoiceId = route.params.id;

    if (typeof invoiceId !== 'string') return;

    updatingOccurrenceId.value = occurrenceId;

    try {
        const response = await axios.put<InvoiceOccurrence>(
            `/invoices/${invoiceId}/occurrences/${occurrenceId}`,
            { status: nextStatus },
        );

        const index = occurrences.value.findIndex(
            (occurrence) => occurrence.id === occurrenceId,
        );

        if (index >= 0) {
            occurrences.value[index] = response.data;
        }
    } 
    catch (error: any) {
        submitError.value =
            error?.response?.data?.message ?? t('invoices.failedToUpdateInvoice');
    } 
    finally {
        updatingOccurrenceId.value = null;
    }
};

const getCurrencyLabel = (currency: Currency) => currency;

const dateInputLabel = computed(() =>
    isRecurring.value ? t('invoices.startDate') : t('invoices.date'),
);

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
        currency: form.currency,
        end_date: isRecurring.value ? form.end_date : undefined,
        price: Number(form.price),
    };

    if (!isRecurring.value) {
        payload.start_date = form.start_date;
        payload.status = form.status;
    }

    try {
        await axios.put(`/invoices/${invoice.value.id}`, payload);
        submitSuccess.value = t('invoices.invoiceUpdated');
        await router.push({ path: '/list', query: { updated: '1' } });
    } 
    catch (error: any) {
        submitError.value =
            error?.response?.data?.message ?? t('invoices.failedToUpdateInvoice');
    } 
    finally {
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
    <section class="flex h-full min-h-[36rem] flex-col overflow-hidden rounded-[2rem] border border-slate-900/10 bg-slate-900/5 shadow-2xl shadow-slate-500/40 backdrop-blur-xl dark:border-white/10 dark:bg-white/5 dark:shadow-black/40">
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
                            <div class="rounded-xl border border-slate-900/10 bg-slate-100/50 p-3 dark:border-white/10 dark:bg-slate-900/40">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-600 dark:text-slate-400">{{ t('invoices.type') }}</p>
                                <p class="mt-2 text-sm font-medium text-slate-900 dark:text-white">{{ getTypeLabel(form.type) }}</p>
                            </div>
                            <div v-if="!isRecurring">
                                <InputSelect
                                    v-model="form.status"
                                    :label="t('invoices.status')"
                                    :options="invoiceStatusOptions"
                                />
                            </div>
                            <div
                                v-else
                                class="rounded-xl border border-slate-900/10 bg-slate-100/50 p-3 dark:border-white/10 dark:bg-slate-900/40"
                            >
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-600 dark:text-slate-400">
                                    {{ t('invoices.status') }}
                                </p>
                                <p class="mt-2 text-sm font-medium text-slate-900 dark:text-white">
                                    {{ getStatusLabel(getOccurrenceStatusSummary(occurrences)) }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ t('invoices.basedOnPaymentSchedule') }}
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div
                                v-if="isRecurring && invoice?.id"
                                class="rounded-xl border border-slate-900/10 bg-slate-100/50 p-3 dark:border-white/10 dark:bg-slate-900/40"
                            >
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-600 dark:text-slate-400">
                                    {{ dateInputLabel }}
                                </p>
                                <p class="mt-2 text-sm font-medium text-slate-900 dark:text-white">
                                    {{ formatOccurrenceDate(form.start_date) }}
                                </p>
                            </div>
                            <InputDate
                                v-else
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

                    </div>

                    <div
                        v-if="isRecurring && invoice?.id"
                        class="rounded-2xl border border-slate-900/10 bg-slate-100/30 p-4 dark:border-white/10 dark:bg-slate-950/30"
                    >
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs uppercase tracking-[0.25em] text-slate-600 dark:text-slate-400">
                                    {{ t('invoices.paymentSchedule') }}
                                </p>
                                <h3 class="mt-2 text-base font-semibold text-slate-900 dark:text-white">
                                    {{ t('invoices.recurring') }}
                                </h3>
                            </div>
                        </div>

                        <div v-if="occurrences.length" class="space-y-3">
                            <div
                                v-for="occurrence in occurrences"
                                :key="occurrence.id"
                                class="flex flex-row items-start justify-between gap-3 rounded-xl border border-slate-900/10 bg-white/60 p-3 text-sm dark:border-white/10 dark:bg-slate-900/40"
                            >
                                <div class="min-w-0 flex-1 text-left">
                                    <p class="font-medium text-slate-900 dark:text-white">
                                        {{ formatOccurrenceDate(occurrence.due_date) }}
                                    </p>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">
                                        {{ formatMoney(occurrence.amount, occurrence.currency) }}
                                    </p>
                                </div>

                                <div class="ml-4 w-36">
                                    <InputSelect
                                        :model-value="String(occurrence.status)"
                                        :label="t('invoices.status')"
                                        :options="recurringOccurrenceStatusOptions"
                                        :disabled="updatingOccurrenceId === occurrence.id"
                                        @update:model-value="updateOccurrenceStatus(occurrence.id, $event as InvoiceStatuses)"
                                    />
                                </div>
                            </div>
                        </div>

                        <p v-else class="text-sm text-slate-600 dark:text-slate-400">
                            {{ t('invoices.noScheduledPayments') }}
                        </p>
                    </div>
                </div>

                <aside class="rounded-2xl border border-dashed border-slate-900/10 bg-slate-100/30 p-5 dark:border-white/10 dark:bg-slate-950/30">
                    <p class="text-xs uppercase tracking-[0.25em] text-slate-600 dark:text-slate-400">
                        {{ t('invoices.previewSummary') }}
                    </p>
                    <h3 class="mt-2 text-xl font-semibold text-slate-900 dark:text-white">
                        {{ t('invoices.editInvoice') }}
                    </h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-400">
                        {{ t('invoices.invoiceManualDescription') }}
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
