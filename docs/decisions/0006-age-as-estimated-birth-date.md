# ADR-0006: Store estimated birth date, not age

**Status:** Accepted

## Context
The original schema stored `animals.estimated_age` as an integer. A stored age
goes stale — an animal recorded as "3" is wrong a year later — and the unit
(years? months?) was ambiguous.

## Decision
Replace `estimated_age` with:
- `estimated_birth_date` (date, nullable)
- `birth_date_is_estimated` (boolean, default true)

Age is **derived** at read time from `estimated_birth_date`; it is never stored.

## Consequences
- Age is always current; no periodic recompute or drift.
- `birth_date_is_estimated` distinguishes a known birthday (rare) from a vet's
  estimate (common), so the UI can show "~2 years" vs "2 years".
- Unknown age → `estimated_birth_date = NULL`; the UI handles the null case.
- Consumers must compute age (e.g. an accessor on the Animal model) rather than
  read a column.
