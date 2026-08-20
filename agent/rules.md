# Agent Rules

Each rule references the workflow step (see `workflow.md`) it governs. Steps not listed here have no dedicated rule — they're fully specified by the step text itself.

**Step 2 — Extract DB target candidates and metadata fields**
- Only extract information that is present in the document.
- Never invent missing values. Use `null` when a field is unavailable.
- Prefer exact text from the invoice over guessed interpretations.

**Step 3 — Normalize values**
- Normalization must preserve the original value's meaning — e.g. German decimal-comma format (`4.763,03`) converts to `4763.03`, not a reinterpreted number. Never let locale formatting change magnitude.

**Step 4 — Determine recurrence**
- Only mark recurring when explicit signals exist. Do not infer recurrence from document type alone (e.g. a credit card statement is not automatically "recurring").

**Step 5 — Map type**
- If recurrence is ambiguous, set `type=one-time` and `recurrence=null`.

**Step 6 — Map recurrence**
- If recurrence is ambiguous, set `type=one-time` and `recurrence=null`. *(same fallback as Step 5 — both governed by the same ambiguity rule)*

**Step 7 — Map start_date**
- Do not force `due_date` into `start_date` unless the mapping fallback explicitly requires it (i.e. `invoice_date` is missing). `due_date` remains metadata by default.

**Step 9a — Map price**
- Prefer exact text from the invoice over guessed interpretations when identifying which labeled amount corresponds to `amount_due` vs `total`. *(same fidelity requirement as Step 2)*

**Step 9b — German-term mapping**
- Do not use masked card numbers or account fragments as `invoice_number` unless the document explicitly labels them as invoice, statement, or account numbers.

**Step 10 — Build title**
- `vendor_name` must come from company identification (letterhead, legal entity name, contact/sender block), never from a document heading or product/service name (e.g. "Rechnung Gebührenfrei Mastercard Gold" is a product name, not the vendor).

**Step 12a — Return structured output**
- Keep output structured and machine-friendly — no freeform fields outside the defined schema.
- If multiple invoices appear in one document, return separate records.
