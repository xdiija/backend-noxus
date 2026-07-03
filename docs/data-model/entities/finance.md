---
domain: finance
tables: [donors, donations, expenses]
tenant_scoped: true
soft_deletes: true
---

# Finance

Lightweight income/outgoing tracking. This is **not** the full accounting module
from the base financial system (no installments, accounts, cost centers). See
ADR-0007 for what was intentionally dropped.

## `donors`

Recurring/known donors. Optional — one-off gifts can be recorded on `donations`
without a donor row.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| id | bigint PK | | |
| organization_id | FK → organizations | no | |
| name | varchar | no | |
| email | varchar | yes | |
| phone | varchar | yes | |
| notes | text | yes | |
| timestamps, deleted_at | | | Soft-deleted. |

## `donations`

| Column | Type | Null | Notes |
|--------|------|------|-------|
| id | bigint PK | | |
| organization_id | FK → organizations | no | |
| donor_id | FK → donors | yes | Optional link; null on delete. |
| donor_name | varchar | yes | Inline fallback for one-off/anonymous gifts. |
| donor_email | varchar | yes | |
| donor_phone | varchar | yes | |
| donation_type_id | FK → donation_types | no | Dinheiro / Ração / … |
| amount | decimal(10,2) | yes | Null for in-kind with no cash value. |
| donation_date | date | no | |
| notes | text | yes | |
| timestamps, deleted_at | | | Soft-deleted. |

### Rules & invariants
- Either `donor_id` **or** the inline `donor_*` fields identify the donor.
  Prefer linking a `donor` for recurring givers; use inline fields for anonymous
  or one-time donations.
- `amount` is nullable so non-monetary donations (food, supplies) can be logged
  without a value.

## `expenses`

| Column | Type | Null | Notes |
|--------|------|------|-------|
| id | bigint PK | | |
| organization_id | FK → organizations | no | |
| expense_category_id | FK → expense_categories | yes | Null on delete. |
| description | varchar | no | |
| amount | decimal(10,2) | no | |
| expense_date | date | no | |
| paid | boolean | no (def false) | Payment status. |
| timestamps, deleted_at | | | Soft-deleted. |

### Rules & invariants
- `paid = false` represents an outstanding/pending expense — the closest thing
  to accounts-payable here. There is no installment schedule; if you need one,
  reintroduce the base project's payments model.
