# Agent Workflow (DB-Aligned)

## Database Target Fields

Supported invoice fields in this project:
- title
- start_date
- end_date
- price
- currency
- type
- recurrence
- status

## Extracted Metadata (useful but not DB columns)

These can still be extracted and returned for traceability:
- vendor_name
- invoice_number
- due_date
- amount_due
- subtotal
- tax
- total
- recurrence_status
- recurrence_interval
- next_invoice_date

## Agent Workflow Steps

### 1a. File validation
Confirm file type is PDF. If unsupported → reject with error `unsupported_file_type`, do not proceed to extraction.

*No rule needed — fully specified by the step.*

---

### 1b. Document validation
Confirm the document is an invoice/statement/bill (not a PO, receipt, contract, or unrelated document).
- If confident invoice → proceed.
- If ambiguous → proceed with extraction but set `confidence=low` and add note `possible_non_invoice`.
- If confident NOT an invoice → return `status=rejected`, `reason=not_an_invoice`, skip DB field mapping entirely.

*No rule needed — fully specified by the step.*

---

### 2. Extract both DB target candidates and metadata fields.

**Rules:**
- Only extract information that is present in the document.
- Never invent missing values. Use `null` when a field is unavailable.
- Prefer exact text from the invoice over guessed interpretations.

---

### 3. Normalize values: dates as YYYY-MM-DD, currency as ISO code, amounts as numeric values.

**Rule:**
- Normalization must preserve the original value's meaning — e.g. German decimal-comma format (`4.763,03`) converts to `4763.03`, not a reinterpreted number. Never let locale formatting change magnitude.

---

### 4. Determine recurrence only if explicitly stated (e.g. recurring, subscription, auto-renew, monthly, yearly, next billing date).

**Rule:**
- Only mark recurring when explicit signals exist. Do not infer recurrence from document type alone (e.g. a credit card statement is not automatically "recurring").

---

### 5. Map type:
- recurring signal present → `type=recurring`
- no recurring signal → `type=one-time`

**Rule:**
- If recurrence is ambiguous, set `type=one-time` and `recurrence=null`.

---

### 6. Map recurrence:
- recurring → `recurrence` must be one of: weekly, biweekly, monthly, quarterly, semiannual, yearly
- one-time → `recurrence=null`

**Rule:**
- If recurrence is ambiguous, set `type=one-time` and `recurrence=null`. *(same rule as Step 5 — both are governed by the same ambiguity fallback)*

---

### 7. Map start_date with clear priority:
- recurring: `next_invoice_date`, else `invoice_date`
- one-time: `invoice_date`, else `due_date`

**Rule:**
- Do not force `due_date` into `start_date` unless the mapping fallback explicitly requires it (i.e. `invoice_date` is missing). `due_date` remains metadata by default.

> ⚠️ Open question from your last test: for bills/statements (vs. purchase invoices), the date a household actually cares about is often `due_date`, not `invoice_date` — this rule as written would have picked `invoice_date` (03.08.2026) for the Advanzia statement, not `due_date` (20.08.2026). Worth deciding whether to branch this rule by document subtype before finalizing.

---

### 8. Map end_date:
- recurring: `recurrence_end_date` when explicitly present
- otherwise: `null`

*No rule needed — fully specified by the step.*

---

### 9a. Map price:
- for credit card statements or account statements, prefer `amount_due` / minimum amount due / payment due amount when present
- otherwise prefer `total`
- fallback to `amount`/`price`
- if `subtotal` and `tax` exist, validate `subtotal + tax` against `total` and add a mismatch note

**Rule:**
- Prefer exact text from the invoice over guessed interpretations when identifying which labeled amount corresponds to `amount_due` vs `total`. *(same rule as Step 2 — extraction fidelity governs this mapping too)*

---

### 9b. German-term mapping
Statement/account-type documents — term mapping (German → canonical):
- "Mindestbetrag" / "Mindestbetrag fällig" → `amount_due` (this becomes `price`)
- "Neuer Saldo" / "Saldo" → `total` / statement balance (metadata only, NOT `price`)
- "Fällig am" / "Zahlbar bis" / "Fälligkeitsdatum" → `due_date`
- "Rechnung vom" → `invoice_date`
- "Abrechnung vom ... bis ..." → `statement_period_start` / `statement_period_end` (metadata)
- "Kreditkartennummer" / "Kontonummer" (masked) → NEVER `invoice_number`; ignore for extraction
- "MwSt." / "USt." → `tax`
- "Netto" → `subtotal`, "Brutto" → `total`

**Rule:**
- Do not use masked card numbers or account fragments as `invoice_number` unless the document explicitly labels them as invoice, statement, or account numbers.

---

### 10. Build title:
- prefer `vendor_name`
- fallback `invoice_number`
- final fallback "Imported invoice"

**Rule:**
- `vendor_name` must come from company identification (letterhead, legal entity name, contact/sender block), never from a document heading or product/service name (e.g. "Rechnung Gebührenfrei Mastercard Gold" is a product name, not the vendor).

---

### 11. Set status:
- default `pending` unless explicit valid status is present

*No rule needed — fully specified by the step.*

---

### 12a. Return structured output only; include metadata fields in the `metadata` object.

**Rules:**
- Keep output structured and machine-friendly — no freeform fields outside the defined schema.
- If multiple invoices appear in one document, return separate records.

---

### 12b. Payload example
```json
{
  "title": "Advanzia Bank S.A.",
  "start_date": "2026-08-20",
  "end_date": null,
  "price": 142.89,
  "currency": "EUR",
  "type": "one-time",
  "recurrence": null,
  "status": "pending",
  "metadata": {
    "vendor_name": "Advanzia Bank S.A.",
    "invoice_number": null,
    "due_date": "2026-08-20",
    "amount_due": 142.89,
    "subtotal": null,
    "tax": null,
    "total": 4763.03,
    "recurrence_status": null,
    "recurrence_interval": null,
    "next_invoice_date": null
  }
}
```