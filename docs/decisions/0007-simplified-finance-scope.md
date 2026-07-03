# ADR-0007: Simplified finance (donations & expenses)

**Status:** Accepted

## Context
The base project is a full financial system: transactions, payments,
installments, accounts, cost centers, payment methods, transaction categories.
The shelter module needs to track money in (donations) and money out (expenses),
but not run double-entry accounting.

## Decision
Replace the accounting module with two flat tables:
- `donations` — income, optionally linked to a `donors` record, typed by
  `donation_types`.
- `expenses` — outgoings, categorized by `expense_categories`, with a `paid`
  boolean.

Drop installments, accounts, cost centers, payment methods, and the
transaction/payment split for this module.

## Consequences
- Much simpler to build and reason about; matches shelter reporting needs
  (totals by type/category/period).
- No installment schedules or accounts-payable aging beyond the `paid` flag. If
  a shelter later needs installment tracking or bank-account balances, the base
  project's `Transaction`/`Payment`/`Account` model can be reintroduced for that
  slice.
- `donations.amount` is nullable to allow in-kind (non-cash) donations.
