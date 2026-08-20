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

## Steps

**1a.** File validation: confirm file type is PDF. If unsupported → reject with error `unsupported_file_type`, do not proceed to extraction.

**1b.** Document validation: confirm the document is an invoice/statement/bill (not a PO, receipt, contract, or unrelated document).
- If confident invoice → proceed.
- If ambiguous → proceed with extraction but set `confidence=low` and add note `possible_non_invoice`.
- If confident NOT an invoice → return `status=rejected`, `reason=not_an_invoice`, skip DB field mapping entirely.

**2.** Extract both DB target candidates and metadata fields.

**3.** Normalize values: dates as YYYY-MM-DD, currency as ISO code, amounts as numeric values.

**4.** Determine recurrence — evaluate signals before mapping type (see Step 5).
- Check for explicit keyword signals (e.g. recurring, subscription, auto-renew, monthly, yearly, next billing date).
- If none found, check structural signals (see rules).
- If ≥1 signal (keyword or structural) found → recurrence detected.
- If none found → no recurrence detected.

**5.** Map type (based on Step 4's result):
- recurrence detected → `type=recurring`
- no recurrence detected → `type=one-time`

**6.** Map recurrence:
- recurring → `recurrence` must be one of: weekly, biweekly, monthly, quarterly, semiannual, yearly
- if not explicitly stated, infer from billing period length (see rules)
- one-time → `recurrence=null`

**7.** Map start_date with clear priority:
- recurring: `next_invoice_date`, else `invoice_date`
- one-time: `invoice_date`, else `due_date`

> ⚠️ Open question from last test: for bills/statements (vs. purchase invoices), the date a household cares about is often `due_date`, not `invoice_date`. As written, this rule would pick `invoice_date` (03.08.2026) over `due_date` (20.08.2026) for a credit card statement. Consider branching this step by document subtype.

**8.** Map end_date:
- recurring: `recurrence_end_date` when explicitly present
- otherwise: `null`

**9a.** Map price:
- for credit card statements or account statements, prefer `amount_due` / minimum amount due / payment due amount when present
- otherwise prefer `total`
- fallback to `amount`/`price`
- if `subtotal` and `tax` exist, validate `subtotal + tax` against `total` and add a mismatch note

**9b.** German-term mapping. Statement/account-type documents — term mapping (German → canonical):
- "Mindestbetrag" / "Mindestbetrag fällig" → `amount_due` (this becomes `price`)
- "Neuer Saldo" / "Saldo" → `total` / statement balance (metadata only, NOT `price`)
- "Fällig am" / "Zahlbar bis" / "Fälligkeitsdatum" → `due_date`
- "Rechnung vom" → `invoice_date`
- "Abrechnung vom ... bis ..." → `statement_period_start` / `statement_period_end` (metadata)
- "Kreditkartennummer" / "Kontonummer" (masked) → NEVER `invoice_number`; ignore for extraction
- "MwSt." / "USt." → `tax`
- "Netto" → `subtotal`, "Brutto" → `total`

**10.** Build title:
- prefer `vendor_name`
- fallback `invoice_number`
- final fallback "Imported invoice"

**11.** Set status:
- default `pending` unless explicit valid status is present

**12a.** Return structured output only; include metadata fields in the `metadata` object.

**12b.** Payload example:
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
