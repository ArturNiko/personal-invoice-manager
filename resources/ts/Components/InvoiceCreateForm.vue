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
import FileInput from "./Form/FileInput.vue";

import type { InvoiceEvent } from "@/Types/Invoice";


const router = useRouter();

const createMode = ref<"manual" | "import">("manual");
const isSubmitting = ref(false);
const isImporting = ref(false);
const submitError = ref("");
const submitSuccess = ref("");
const importSuccess = ref("");
const importFile = ref<File | null>(null);

type CreateInvoiceForm = {
    title: string;
    type: "one-time" | "recurring";
    status: "pending" | "paid" | "overdue";
    start_date: string;
    end_date: string;
    currency: string;
    recurrence: "weekly" | "biweekly" | "monthly" | "quarterly" | "semiannual" | "yearly";
    price: string;
};

const form = reactive<CreateInvoiceForm>({
    title: "",
    type: "one-time" as "one-time" | "recurring",
    status: "pending" as "pending" | "paid" | "overdue",
    start_date: new Date().toISOString().slice(0, 10),
    end_date: "",
    currency: "EUR",
    recurrence: "monthly" as
        | "weekly"
        | "biweekly"
        | "monthly"
        | "quarterly"
        | "semiannual"
        | "yearly",
    price: "",
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
const isImportMode = computed(() => createMode.value === "import");

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

const setCreateMode = (mode: "manual" | "import") => {
    createMode.value = mode;
    submitError.value = "";
    submitSuccess.value = "";
    importSuccess.value = "";
};

const resetForm = () => {
    form.title = "";
    form.type = "one-time";
    form.status = "pending";
    form.start_date = new Date().toISOString().slice(0, 10);
    form.end_date = "";
    form.currency = "EUR";
    form.recurrence = "monthly";
    form.price = "";
    importFile.value = null;
    importSuccess.value = "";
};

const submitImport = async () => {
    if (!importFile.value) {
        submitError.value = "Please choose a PDF file to import.";
        return;
    }

    isImporting.value = true;
    submitError.value = "";
    importSuccess.value = "";

    const formData = new FormData();
    formData.append("invoice", importFile.value);

    try {
        const response = await axios.post("/invoices/import", formData, {
            headers: {
                "Content-Type": "multipart/form-data",
            },
        });

        importSuccess.value = response.data?.message ?? "Invoice is being processed.";
        importFile.value = null;
    } catch (error: any) {
        submitError.value = error?.response?.data?.message ?? "Failed to import invoice.";
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
        await axios.post("/invoices", payload);
        submitSuccess.value = "Invoice created successfully.";
        resetForm();
        await router.push({ path: "/list", query: { updated: "1" } });
    } 
    catch (error: any) {
        submitError.value = error?.response?.data?.message ?? "Failed to create invoice.";
    } 
    finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <section
        class="flex h-full min-h-[36rem] flex-col overflow-hidden rounded-[2rem] border border-white/10 bg-white/5 shadow-2xl shadow-slate-950/40 backdrop-blur-xl">
        <div class="border-b border-white/10 px-5 py-4 sm:px-6">
            <h2 class="text-lg font-semibold text-white">Create invoice</h2>
            <p class="text-sm text-slate-400">Add a new invoice by entering the details below or importing an existing PDF.</p>
        </div>

        <form class="grid gap-6 p-4 sm:p-6 lg:grid-cols-[1.2fr_0.8fr]" @submit.prevent="submitForm">
            <div class="space-y-6">
                <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-1 shadow-lg shadow-slate-950/30">
                    <div class="grid grid-cols-2 gap-1">
                        <button type="button" class="rounded-xl px-4 py-2 text-sm font-medium transition"
                            :class="createMode === 'manual' ? 'bg-white text-slate-950 shadow' : 'text-slate-300 hover:text-white'"
                            @click="setCreateMode('manual')">
                            Manual Entry
                        </button>
                        <button type="button" class="rounded-xl px-4 py-2 text-sm font-medium transition"
                            :class="createMode === 'import' ? 'bg-white text-slate-950 shadow' : 'text-slate-300 hover:text-white'"
                            @click="setCreateMode('import')">
                            Import PDF
                        </button>
                    </div>
                </div>

                <div v-if="createMode === 'manual'" class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <InputText v-model="form.title" label="Title" required placeholder="Subscription" />
                        <InputBalance v-model="form.price" v-model:currency="form.currency" :label="priceInputLabel"
                            :currency-options="currencySelectOptions" placeholder="100.00" />
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

                    <InputSelect v-model="form.recurrence" label="Recurrence" :options="recurrenceSelectOptions"
                        :disabled="!isRecurring" />
                </div>

                <div v-else class="space-y-4">
                    <FileInput v-model="importFile" label="Invoice PDF" button-label="Choose PDF" hint="PDF" />

                    <p v-if="submitError"
                        class="rounded-xl border border-red-400/30 bg-red-500/10 p-3 text-sm text-red-100">
                        {{ submitError }}
                    </p>
                    <p v-if="importSuccess"
                        class="rounded-xl border border-emerald-400/30 bg-emerald-500/10 p-3 text-sm text-emerald-100">
                        {{ importSuccess }}
                    </p>
                </div>
            </div>

            <aside class="rounded-2xl border border-dashed border-white/10 bg-slate-950/30 p-5">
                <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Preview</p>
                <h3 class="mt-2 text-xl font-semibold text-white">
                    {{ createMode === 'import' ? 'Import invoice' : 'New invoice' }}
                </h3>
                <p class="mt-2 text-sm leading-6 text-slate-400">
                    {{
                        createMode === 'import'
                            ? 'Choose a PDF and hand it off to the invoice reader.'
                            : 'The form adjusts automatically for one-time and recurring invoices.'
                    }}
                </p>

                <div class="mt-6 space-y-3 text-sm text-slate-300">
                    <div class="rounded-xl border border-white/10 bg-white/5 p-3">Type: {{ form.type }}</div>
                    <div class="rounded-xl border border-white/10 bg-white/5 p-3">Status: {{ form.status }}</div>
                    <div class="rounded-xl border border-white/10 bg-white/5 p-3">Currency: {{ form.currency }}</div>
                    <div class="rounded-xl border border-white/10 bg-white/5 p-3">
                        {{ recurringPreviewLabel }}
                    </div>
                </div>

                <p v-if="submitError && createMode === 'manual'"
                    class="mt-6 rounded-xl border border-red-400/30 bg-red-500/10 p-3 text-sm text-red-100">
                    {{ submitError }}
                </p>
                <p v-if="submitSuccess && createMode === 'manual'"
                    class="mt-6 rounded-xl border border-emerald-400/30 bg-emerald-500/10 p-3 text-sm text-emerald-100">
                    {{ submitSuccess }}
                </p>

                <Button v-if="createMode === 'manual'" type="submit" :disabled="isSubmitting || isRecurringRangeInvalid" variant="solid" block
                    class="mt-6">
                    {{ isSubmitting ? 'Saving...' : 'Create invoice' }}
                </Button>
                <Button v-else type="button" :disabled="isImporting || !importFile" variant="solid" block class="mt-6"
                    @click="submitImport">
                    {{ isImporting ? 'Importing...' : 'Import invoice' }}
                </Button>
            </aside>
        </form>
    </section>
</template>