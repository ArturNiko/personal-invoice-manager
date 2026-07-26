<script setup lang="ts">
import InvoiceList from '@/Components/InvoiceList.vue'

import type { InvoiceEvent } from '@/Types/Invoice'

const props = defineProps<{ invoices: InvoiceEvent[] }>()
</script>

<template>
    <InvoiceList :invoices="props.invoices" />
</template>
