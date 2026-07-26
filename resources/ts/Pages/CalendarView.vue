<script setup lang="ts">
import InvoiceCalendar from "@/Components/InvoiceCalendar.vue";

import type { InvoiceEvent } from "@/Types/Invoice";

const props = defineProps<{ invoices: InvoiceEvent[] }>();
</script>

<template>
    <InvoiceCalendar :invoices="props.invoices" />
</template>
