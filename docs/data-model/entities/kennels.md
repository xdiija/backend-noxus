---
domain: kennels
tables: [kennels, animal_kennel_stays]
tenant_scoped: true
soft_deletes: partial
---

# Kennels (physical housing)

Where the animal is at, inside the shelter: physical kennels and each animal's
stay history. Complements fostering — a kennel stay means "at the shelter", a
foster stay means "away in a temporary home"; the two are mutually exclusive.

## `kennels`

A physical enclosure (kennel/run/cage) of the organization.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| id | bigint PK | | |
| organization_id | FK → organizations | no | |
| name | varchar | no | e.g. A1, B2, Filhotes-01. Unique per org, app-enforced (soft deletes make a DB unique index awkward; trashed names are reusable). |
| capacity | int | no (def 1) | Max simultaneous animals. Always finite (unlike `foster_homes.capacity`). |
| size_id | FK → animal_sizes | yes | Largest size that fits. **Advisory** — not enforced; size-compatibility checks are a future improvement. `nullOnDelete`. |
| status_id | FK → kennel_statuses | no | Operational state (configurable lookup, see `lookups.md`). Restrict on delete. |
| notes | text | yes | |
| timestamps, deleted_at | | | Soft-deleted. |

**Occupancy is derived, never stored:** occupied = open stays
(`exit_date IS NULL`); available = status is not "Em Manutenção" **and**
open stays < `capacity`. Exposed as `open_stays_count` in the API.

## `animal_kennel_stays`

An animal's period in a kennel. **Append-only history**: every move creates a
new row; closed stays are never edited or reopened.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| id | bigint PK | | |
| animal_id | FK → animals | no | Cascade on delete. |
| kennel_id | FK → kennels | no | Cascade on delete. |
| entry_date | date | no | |
| exit_date | date | yes | Null = currently in this kennel. |
| reason | varchar | yes | Why the stay opened (Resgate, Transferência, …). Transfers stamp "Transferência" on the new row. Why it *ended* is documented by `animal_status_history` / the closing workflow, not here. |
| notes | text | yes | |
| timestamps | | | No soft delete (log table). Same-day moves order by `id`. |

### Rules & invariants (enforced in `KennelStayService` / `AnimalService`)

- **One open stay per animal**, org-wide — application-enforced, like foster
  stays.
- **Capacity blocks new stays**: open stays must stay < `kennels.capacity`
  (both on open and on transfer).
- **Kennel must be in service**: a kennel whose status is "Em Manutenção"
  rejects new stays. Any other status — including "Isolamento" — accepts
  animals (isolation-specific workflow is a future improvement).
- **Animal must be at the shelter**: an animal in an off-site lifecycle status
  ("Em Lar Temporário", "Adotado", "Óbito" — `OFF_SITE_ANIMAL_STATUSES`) or
  with an **open foster stay** can't be placed in a kennel. Close the foster
  stay first (that also flips the animal back to "Disponível").
- **Automatic close**: every status-changing path in `AnimalService`
  (adoption, foster placement, death, manual edits) closes the animal's open
  kennel stay when the new status is off-site. Kennel workflows never change
  the animal's status — being in a kennel says nothing about the lifecycle.
- **Transfer** = close current stay + create the new one, same date, one DB
  transaction, reason "Transferência".
- **Closed stays are immutable**; `PUT` corrections (entry date, reason,
  notes) apply to open stays only. The animal on a stay is immutable — a wrong
  animal means deleting the stay and opening a new one.
- **A kennel with open stays can't be deleted.**
