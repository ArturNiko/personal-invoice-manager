<script setup lang="ts">
import { onMounted, ref } from "vue";
import axios from "axios";
import { useRoute } from "vue-router";

import InvoiceEditForm from "@/Components/InvoiceEditForm.vue";

import type { InvoiceEvent } from "@/Types/Invoice";

const route = useRoute();
const invoice = ref<InvoiceEvent | null>(null);
const loading = ref(true);
const loadError = ref("");

const loadInvoice = async () => {
    const invoiceId = route.params.id;

    if (typeof invoiceId !== "string") {
        loadError.value = "Missing invoice id.";
        loading.value = false;
        return;
    }

    try {
        const response = await axios.get<InvoiceEvent>(
            `/invoices/${invoiceId}`,
        );
        invoice.value = response.data;
    } catch (error: any) {
        loadError.value =
            error?.response?.data?.message ?? "Failed to load invoice.";
    } finally {
        loading.value = false;
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

    <InvoiceEditForm 
        v-else 
        :invoice="invoice" 
        @deleted="invoice = null" 
        />
</template>
