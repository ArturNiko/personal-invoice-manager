<script setup lang="ts">
import { computed, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";

import { formatMoney } from "@/Utils/Helpers";
import { calculateOccurrencesCount } from "@/Utils/Occurrences";

import InputText from "@/Components/Form/InputText.vue";
import InputDate from "@/Components/Form/InputDate.vue";
import InputBalance from "@/Components/Form/InputBalance.vue";
import InputSelect from "@/Components/Form/InputSelect.vue";
import Button from "@/Components/Button.vue";

import type { InvoiceEvent } from "@/Types/Invoice";


const props = defineProps<{
    invoice: Partial<InvoiceEvent> | null;
}>();

const emit = defineEmits<{
    deleted: []; 
}>();

const router = useRouter();

const isSubmitting = ref(false);
const isDeleting = ref(false);
const submitError = ref("");
const submitSuccess = ref("");

const initialInvoice = props.invoice ?? null;

const normalizeDateValue = (value?: string | null) => {
    if (!value) return "";

    return value.split("T")[0].split(" ")[0];
};

const getInitialStartDate = () => {
    return normalizeDateValue(initialInvoice?.start_date) || new Date().toISOString().slice(0, 10);
};

const form = reactive({
    title: initialInvoice?.title ?? "",
    type: (initialInvoice?.type === "recurring" ? "recurring" : "one-time") as "one-time" | "recurring",
    status: initialInvoice?.status ?? "pending",
    start_date: getInitialStartDate(),
    end_date: normalizeDateValue(initialInvoice?.end_date),
    currency: initialInvoice?.currency ?? "EUR",
    recurrence:
        (initialInvoice?.recurrence as
            | "weekly"
            | "biweekly"
            | "monthly"
            | "quarterly"
            | "semiannual"
            | "yearly"
            | undefined) ?? "monthly",
    price: String(initialInvoice?.price ?? ""),
});

const typeSelectOptions = ["one-time", "recurring"].map((value) => ({
    label: value === "one-time" ? "One-time" : "Recurring",
    value,
}));

const recurrenceSelectOptions = [
    "weekly",
    "biweekly",
    "monthly",
    "quarterly",
    "semiannual",
    "yearly",
].map((value) => ({
    label: value.charAt(0).toUpperCase() + value.slice(1),
    value,
}));

const statusSelectOptions = [
    { label: "Pending", value: "pending" },
    { label: "Paid", value: "paid" },
    { label: "Overdue", value: "overdue" },
];

const currencySelectOptions = [
    "EUR",
    "USD",
    "GBP",
    "JPY",
    "AUD",
    "CAD",
    "CHF",
    "CNY",
    "SEK",
    "NZD",
    "RUB",
    "AMD",
].map((value) => ({
    label: value,
    value,
}));

const isRecurring = computed(() => form.type === "recurring");

const isRecurringRangeInvalid = computed(() => {
    if (!isRecurring.value || !form.start_date || !form.end_date) {
        return false;
    }

    return new Date(form.start_date) > new Date(form.end_date);
});

const recurringPreviewLabel = computed(() => {
    if (!isRecurring.value) {
        return `Price: ${formatMoney(Number(form.price || "0"), form.currency)}`;
    }

    if (form.start_date && !form.end_date) {
        return "Recurring schedule: Endless";
    }

    if (!form.start_date || !form.end_date) {
        return "Recurring schedule: not set";
    }

    return `Occurrences: ${calculateOccurrencesCount(form.start_date, form.end_date, form.recurrence)}`;
});

const priceInputLabel = computed(() =>
    isRecurring.value ? "Occurrence price" : "Price",
);

const dateInputLabel = computed(() =>
    isRecurring.value ? "Start date" : "Date",
);

const submitForm = async () => {
    if (isRecurringRangeInvalid.value) {
        submitError.value = "Recurring end date must be on or after the start date.";
        return;
    }

    isSubmitting.value = true;
    submitError.value = "";
    submitSuccess.value = "";

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
        if (props.invoice?.id) {
            await axios.put(`/invoices/${props.invoice.id}`, payload);
            submitSuccess.value = "Invoice updated successfully.";
            await router.push({ path: "/list", query: { updated: "1" } });
        }
    } catch (error: any) {
        submitError.value = error?.response?.data?.message ?? "Failed to update invoice.";
    } finally {
        isSubmitting.value = false;
    }
};

const deleteInvoice = async () => {
    if (!initialInvoice?.id) return;

    const confirmed = window.confirm("Delete this invoice? This cannot be undone.");

    if (!confirmed) return;

    isDeleting.value = true;
    submitError.value = "";

    try {
        await axios.delete(`/invoices/${initialInvoice.id}`);
        emit("deleted");
        await router.push({ path: "/list", query: { deleted: "1" } });
    } catch (error: any) {
        submitError.value = error?.response?.data?.message ?? "Failed to delete invoice.";
    } finally {
        isDeleting.value = false;
    }
};
</script>

<template>
    <section class="flex h-full min-h-[36rem] flex-col overflow-hidden rounded-[2rem] border border-white/10 bg-white/5 shadow-2xl shadow-slate-950/40 backdrop-blur-xl">
        <div class="border-b border-white/10 px-5 py-4 sm:px-6">
            <h2 class="text-lg font-semibold text-white">Edit invoice</h2>
            <p class="text-sm text-slate-400">Update invoice details, status, or delete the record.</p>
        </div>

        <form class="grid gap-6 p-4 sm:p-6 lg:grid-cols-[1.2fr_0.8fr]" @submit.prevent="submitForm">
            <div class="space-y-6">
                <div class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <InputText v-model="form.title" label="Title" required placeholder="Subscription" />
                        <InputBalance v-model="form.price" v-model:currency="form.currency" :label="priceInputLabel" :currency-options="currencySelectOptions" placeholder="100.00" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <InputSelect v-model="form.type" label="Type" :options="typeSelectOptions" />
                        <InputSelect v-model="form.status" label="Status" :options="statusSelectOptions" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <InputDate v-model="form.start_date" :label="dateInputLabel" required />
                        <InputDate v-if="isRecurring" v-model="form.end_date" label="End date" />
                    </div>

                    <p v-if="isRecurringRangeInvalid" class="rounded-xl border border-red-400/30 bg-red-500/10 p-3 text-sm text-red-100">
                        Recurring end date must be on or after the start date.
                    </p>

                    <InputSelect v-model="form.recurrence" label="Recurrence" :options="recurrenceSelectOptions" :disabled="!isRecurring" />
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

                <Button v-if="initialInvoice?.id" type="button" :disabled="isDeleting" variant="danger" block class="mt-3" @click="deleteInvoice">
                    {{ isDeleting ? 'Deleting...' : 'Delete invoice' }}
                </Button>
            </aside>
        </form>
    </section>
</template>