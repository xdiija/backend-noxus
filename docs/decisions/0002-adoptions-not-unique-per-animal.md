# ADR-0002: Adoptions are not unique per animal

**Status:** Accepted

## Context
The original schema had `adoptions.animal_id` as `UNIQUE`, allowing only one
adoption row per animal. In real shelters, adopted animals are frequently
returned and later re-adopted. A unique constraint makes the second adoption
impossible and loses the return history.

## Decision
Remove the unique constraint on `adoptions.animal_id`. Model the adoption
lifecycle with a configurable `status_id` (→ `adoption_statuses`: Pendente,
Concluída, Devolvida, Cancelada) plus `return_date` and `return_reason`. Each
adoption attempt is its own row; multiple rows per animal form the history.

## Decision detail — the "one active adoption" rule
At most one *active* adoption (status Pendente/Concluída with no `return_date`)
may exist per animal at a time. This is **application-enforced** — the DB does
not express it. Check for an open adoption before creating a new one.

## Consequences
- Full adoption/return history is preserved and queryable.
- Returns update the adoption (status → Devolvida, `return_date`) and the
  animal's `status_id` (+ an `animal_status_history` row).
- The "one active adoption" invariant must be covered by application logic and
  tests, since it isn't a DB constraint.
