<script setup lang="ts">
import axios from 'axios';
import { computed, ref, watch } from 'vue';
import { getCurrencySymbol } from '@/Utils/Currency';
import { useRoute, useRouter } from 'vue-router';
import { buildInvoiceQuery, useInvoices } from '@/Composables/useInvoices';
import { t } from '@/i18n';

import { InvoiceTypes, type InvoiceEvent } from '@/Types/Invoice';
import Badge from '@/Components/Badge.vue';
import Button from '@/Components/Button.vue';
import ConfirmationDialog from '@/Components/ConfirmationDialog.vue';


const route = useRoute();
const router = useRouter();
const deletingInvoiceId = ref<number | null>(null);
const pendingDeleteInvoice = ref<InvoiceEvent | null>(null);
const { invoices, fetchInvoices } = useInvoices();

const searchQuery = computed({
    get: () => (typeof route.query.q === 'string' ? route.query.q : ''),
    set: (value: string) => {
        updateQuery({ q: value || undefined });
    },
});

const typeFilter = computed({
    get: () => {
        const value =
            typeof route.query.type === 'string' ? route.query.type : 'all';
        return value === InvoiceTypes.ONE_TIME ||
            value === InvoiceTypes.RECURRING
            ? value
            : 'all';
    },
    set: (value: 'all' | InvoiceTypes.ONE_TIME | InvoiceTypes.RECURRING) => {
        updateQuery({ type: value === 'all' ? undefined : value });
    },
});

const sortDirection = computed({
    get: () => (route.query.direction === 'desc' ? 'descending' : 'ascending'),
    set: (value: 'ascending' | 'descending') => {
        updateQuery({
            sort: 'start_date',
            direction: value === 'ascending' ? 'asc' : 'desc',
        });
    },
});

const updateQuery = (updates: Record<string, string | undefined>) => {
    router.replace({
        query: {
            ...route.query,
            ...updates,
        },
    });
};

const invoiceQuery = computed(() => buildInvoiceQuery(route.query));
const visibleInvoices = computed(() => invoices.value);
const pendingDeleteMessage = computed(() => {
    if (!pendingDeleteInvoice.value) {
        return t('invoices.deleteWarning');
    }

    return `${t('invoices.deleteConfirmation')} "${pendingDeleteInvoice.value.title}"? ${t('invoices.deleteConfirmationTail')}`;
});

const filteredRecurringCount = computed(() => {
    return visibleInvoices.value.filter(
        (invoice) => invoice.type === InvoiceTypes.RECURRING,
    ).length;
});

const filteredOneTimeCount = computed(() => {
    return visibleInvoices.value.filter(
        (invoice) => invoice.type === InvoiceTypes.ONE_TIME,
    ).length;
});

const toggleSortDirection = () => {
    sortDirection.value =
        sortDirection.value === 'ascending' ? 'descending' : 'ascending';
};

const getTypeCountLabel = (count: number) => {
    return count === 1 ? t('invoices.item') : t('invoices.items');
};

const formatDate = (dateValue?: string) => {
    if (!dateValue) return t('invoices.noDueDate');

    const parsedDate = new Date(dateValue);

    if (Number.isNaN(parsedDate.getTime())) return dateValue;

    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(parsedDate);
};

const formatAmount = (invoice: InvoiceEvent) => {
    const amount = invoice.price;
    const symbol = getCurrencySymbol(invoice.currency);

    return `${symbol}${amount}`;
};

const getTypeVariant = (type: string) => {
    return type === 'recurring' ? 'teal' : 'sky';
};

const getTypeLabel = (type: string) => {
    return type === InvoiceTypes.RECURRING
        ? t('invoices.recurringShort')
        : t('invoices.oneTimeShort');
};

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'paid':
            return 'emerald';
        case 'overdue':
            return 'rose';
        default:
            return 'amber';
    }
};

const getStatusLabel = (status: string) => {
    switch (status) {
        case 'paid':
            return 'Paid';
        case 'overdue':
            return 'Overdue';
        default:
            return 'Pending';
    }
};

const editInvoice = (id: number) => {
    router.push({ name: 'invoice-edit', params: { id: id.toString() } });
};

const promptDeleteInvoice = (invoice: InvoiceEvent) => {
    pendingDeleteInvoice.value = invoice;
};

const closeDeleteDialog = () => {
    if (deletingInvoiceId.value !== null) return;

    pendingDeleteInvoice.value = null;
};

const confirmDeleteInvoice = async () => {
    if (!pendingDeleteInvoice.value?.id) return;

    const invoiceId = pendingDeleteInvoice.value.id;

    deletingInvoiceId.value = invoiceId;

    try {
        await axios.delete(`/invoices/${invoiceId}`);
        pendingDeleteInvoice.value = null;
        await router.replace({
            query: {
                ...route.query,
                deleted: String(Date.now()),
            },
        });
    } 
    catch (error) {
        console.error('Failed to delete invoice:', error);
    } 
    finally {
        deletingInvoiceId.value = null;
    }
};

watch(
    () => route.query,
    async () => {
        await fetchInvoices(invoiceQuery.value);
    },
    { deep: true, immediate: true },
);
</script>

<template>
    <section
        class="flex h-full min-h-[36rem] flex-col overflow-hidden rounded-[2rem] border border-slate-900/10 bg-slate-900/5 shadow-2xl shadow-slate-500/40 backdrop-blur-xl dark:border-white/10 dark:bg-white/5 dark:shadow-black/40"
    >
        <div
            class="flex items-center justify-between border-b border-slate-900/10 px-5 py-4 sm:px-6 dark:border-white/10"
        >
            <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ t('invoices.listTitle') }}</h2>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    {{ t('invoices.listSubtitle') }}
                </p>
            </div>
            <Badge variant="sky" size="md"
                >{{ visibleInvoices.length }} {{ t('invoices.items') }}</Badge
            >
        </div>

        <div class="border-b border-slate-900/10 p-4 sm:p-6 dark:border-white/10">
            <div
                class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
            >
                <label class="flex-1">
                    <span class="sr-only">Search invoices</span>
                    <input
                        v-model="searchQuery"
                        type="search"
                        :placeholder="t('invoices.searchPlaceholder')"
                        class="w-full rounded-2xl border border-slate-900/10 bg-slate-100/50 px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-500 focus:border-cyan-700/50 focus:bg-slate-100/70 dark:border-white/10 dark:bg-slate-950/50 dark:text-white dark:focus:border-cyan-400/50 dark:focus:bg-slate-950/70"
                    />
                </label>

                <div class="flex gap-2 overflow-x-auto pb-1 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden lg:flex-wrap lg:overflow-visible lg:pb-0">
                    <Button
                        :variant="typeFilter === 'all' ? 'solid' : 'outline'"
                        size="md"
                        class="whitespace-nowrap"
                        @click="typeFilter = 'all'"
                    >
                        {{ t('invoices.all') }}
                    </Button>
                    <Button
                        :variant="
                            typeFilter === InvoiceTypes.ONE_TIME
                                ? 'sky'
                                : 'outline'
                        "
                        size="md"
                        class="whitespace-nowrap"
                        @click="typeFilter = InvoiceTypes.ONE_TIME"
                    >
                        {{ t('invoices.oneTime') }}
                    </Button>
                    <Button
                        :variant="
                            typeFilter === InvoiceTypes.RECURRING
                                ? 'teal'
                                : 'outline'
                        "
                        size="md"
                        class="whitespace-nowrap"
                        @click="typeFilter = InvoiceTypes.RECURRING"
                    >
                        {{ t('invoices.recurring') }}
                    </Button>
                    <Button
                        variant="outline"
                        size="md"
                        class="whitespace-nowrap"
                        @click="toggleSortDirection"
                    >
                        {{
                            sortDirection === 'ascending'
                                ? t('invoices.soonest')
                                : t('invoices.latest')
                        }}
                    </Button>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-auto p-4 sm:p-6">
            <div
                v-if="visibleInvoices.length"
                class="grid gap-4 lg:grid-cols-[1.3fr_0.7fr]"
            >
                <div class="space-y-3">
                    <article
                        v-for="invoice in visibleInvoices"
                        :key="invoice.id"
                        class="overflow-hidden rounded-[1.5rem] border border-slate-900/10 bg-slate-100/40 p-3 shadow-[0_8px_18px] shadow-slate-500/12 transition hover:border-slate-900/20 hover:bg-slate-100/55 dark:border-white/10 dark:bg-slate-950/40 dark:shadow-black/12 dark:hover:border-white/20 dark:hover:bg-slate-950/55 sm:p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-900 dark:text-white sm:text-base">
                                    {{ invoice.title }}
                                </p>
                                <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px] text-slate-600 dark:text-slate-400 sm:text-xs">
                                    <span>{{ t('invoices.due') }} {{ formatDate(invoice.start_date) }}</span>
                                    <span class="text-slate-400 dark:text-slate-600">•</span>
                                    <span>{{ t('invoices.id') }} #{{ invoice.id }}</span>
                                </div>
                            </div>

                            <div class="text-right">
                                <p class="text-base font-semibold text-slate-900 dark:text-white sm:text-lg">
                                    {{ formatAmount(invoice) }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <Badge :variant="getTypeVariant(invoice.type)">
                                    {{ getTypeLabel(invoice.type) }}
                                </Badge>
                                <Badge :variant="getStatusVariant(invoice.status)">
                                    {{ getStatusLabel(invoice.status) }}
                                </Badge>
                            </div>

                            <div class="ml-auto flex items-center gap-2">
                                <button
                                    type="button"
                                    class="rounded-xl border border-slate-400 bg-slate-200/80 px-2.5 py-1.5 text-[11px] font-medium text-slate-900 transition hover:border-slate-500 hover:bg-slate-300 dark:border-slate-600 dark:bg-slate-800/80 dark:text-slate-100 dark:hover:bg-slate-700 sm:px-3 sm:text-xs"
                                    @click="editInvoice(invoice.id)"
                                >
                                    {{ t('invoices.edit') }}
                                </button>
                                <button
                                    type="button"
                                    class="rounded-xl border border-rose-700/40 bg-rose-700/10 px-2.5 py-1.5 text-[11px] font-medium text-rose-800 transition hover:bg-rose-700/20 dark:border-rose-500/40 dark:bg-rose-500/10 dark:text-rose-100 dark:hover:bg-rose-500/20 sm:px-3 sm:text-xs"
                                    @click="promptDeleteInvoice(invoice)"
                                >
                                    {{ t('invoices.delete') }}
                                </button>
                            </div>
                        </div>
                    </article>
                </div>

                <aside
                    class="hidden rounded-2xl border border-dashed border-slate-900/10 bg-slate-100/30 p-5 dark:border-white/10 dark:bg-slate-950/30 lg:block"
                >
                    <p
                        class="text-xs uppercase tracking-[0.25em] text-slate-600 dark:text-slate-400"
                    >
                        {{ t('invoices.summary') }}
                    </p>
                    <h3 class="mt-2 text-xl font-semibold text-slate-900 dark:text-white">
                        {{ t('invoices.insights') }}
                    </h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-400">
                        {{ t('invoices.insightsDescription') }}
                    </p>
                    <div class="mt-6 space-y-3 text-sm text-slate-700 dark:text-slate-300">
                        <div
                            class="rounded-xl border border-slate-900/10 bg-slate-900/5 p-3 dark:border-white/10 dark:bg-white/5"
                        >
                            {{ t('invoices.visibleInvoices') }}: {{ visibleInvoices.length }}
                        </div>
                        <div
                            class="rounded-xl border border-slate-900/10 bg-slate-900/5 p-3 dark:border-white/10 dark:bg-white/5"
                        >
                            {{ t('invoices.recurringShort') }}: {{ filteredRecurringCount }}
                            {{ getTypeCountLabel(filteredRecurringCount) }}
                        </div>
                        <div
                            class="rounded-xl border border-slate-900/10 bg-slate-900/5 p-3 dark:border-white/10 dark:bg-white/5"
                        >
                            {{ t('invoices.oneTimeShort') }}: {{ filteredOneTimeCount }}
                            {{ getTypeCountLabel(filteredOneTimeCount) }}
                        </div>
                    </div>
                </aside>
            </div>

            <div
                v-else
                class="flex h-full min-h-[20rem] items-center justify-center rounded-2xl border border-dashed border-slate-900/10 bg-slate-100/20 p-6 text-center dark:border-white/10 dark:bg-slate-950/20"
            >
                <div>
                    <p
                        class="text-sm uppercase tracking-[0.25em] text-slate-600 dark:text-slate-400"
                    >
                        {{ t('invoices.noData') }}
                    </p>
                    <h3 class="mt-2 text-xl font-semibold text-slate-900 dark:text-white">
                        {{ t('invoices.noResultsTitle') }}
                    </h3>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                        {{ t('invoices.noResultsDescription') }}
                    </p>
                </div>
            </div>

            <ConfirmationDialog
                :open="pendingDeleteInvoice !== null"
                :busy="deletingInvoiceId === pendingDeleteInvoice?.id"
                :title="t('invoices.deleteInvoice')"
                :message="pendingDeleteMessage"
                :confirm-label="t('invoices.delete')"
                :cancel-label="t('invoices.keepIt')"
                @close="closeDeleteDialog"
                @confirm="confirmDeleteInvoice"
            />
        </div>
    </section>
</template>
