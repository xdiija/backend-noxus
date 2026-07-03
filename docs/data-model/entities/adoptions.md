---
domain: adoptions
tables: [adopters, adoptions, adoption_follow_ups]
tenant_scoped: true
soft_deletes: partial
---

# Adoptions

Placement of an animal with an adopter, and post-adoption follow-ups. The
returns/re-adoption model is the key design point (ADR-0002).

## `adopters`

| Column | Type | Null | Notes |
|--------|------|------|-------|
| id | bigint PK | | |
| organization_id | FK → organizations | no | |
| name | varchar | no | |
| cpf | varchar | yes | **Unique per organization**, not globally. |
| phone | varchar | yes | |
| email | varchar | yes | |
| address | varchar | yes | |
| city | varchar | yes | |
| state | varchar | yes | |
| birth_date | date | yes | |
| timestamps, deleted_at | | | Soft-deleted. |

### Rules & invariants
- `unique(organization_id, cpf)` — the same person may exist as an adopter in
  two different organizations. Watch for `NULL` cpf: MySQL allows multiple NULLs
  under a unique index, which is intended (cpf optional).

## `adoptions`

| Column | Type | Null | Notes |
|--------|------|------|-------|
| id | bigint PK | | |
| organization_id | FK → organizations | no | |
| animal_id | FK → animals | no | **Not unique** — an animal can be adopted, returned, re-adopted. |
| adopter_id | FK → adopters | no | |
| adoption_date | date | no | |
| status_id | FK → adoption_statuses | no | Pendente / Concluída / Devolvida / Cancelada. |
| agreement_signed | boolean | no (def false) | Adoption contract signed. |
| return_date | date | yes | Set when the animal is returned. |
| return_reason | text | yes | |
| notes | text | yes | |
| timestamps, deleted_at | | | Soft-deleted. |

### Rules & invariants
- **No unique constraint on `animal_id`** — this is deliberate (ADR-0002).
  Multiple adoption rows per animal represent the adoption history.
- Only one adoption should be "active" (status Pendente/Concluída with no
  `return_date`) per animal at a time. This is **application-enforced**, not a
  DB constraint — check for an open adoption before creating a new one.
- Returning an animal: set `status_id` → Devolvida, fill `return_date` /
  `return_reason`, and update the animal's `status_id` (+ a
  `animal_status_history` row).

## `adoption_follow_ups`

Post-adoption welfare check-ins.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| id | bigint PK | | |
| adoption_id | FK → adoptions | no | Cascade on delete. |
| follow_up_date | date | no | |
| contact_method | varchar | yes | phone, visit, message… |
| status_id | FK → adoption_follow_up_statuses | no | Distinct from adoption status. |
| notes | text | yes | |
| timestamps | | | No soft delete. |

### Rules & invariants
- `status_id` here references `adoption_follow_up_statuses` (Agendado,
  Contatado, …) — **not** `adoption_statuses`. Don't conflate the two lookups.
