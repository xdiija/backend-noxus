---
domain: animals
tables: [animals, rescues, animal_images, animal_status_history, animal_tags]
tenant_scoped: true
soft_deletes: true
---

# Animals

The central entity. An animal belongs to one organization and moves through a
configurable status lifecycle.

## `animals`

| Column | Type | Null | Notes |
|--------|------|------|-------|
| id | bigint PK | | |
| organization_id | FK → organizations | no | Owner tenant. |
| name | varchar | no | |
| species_id | FK → species | no | Restrict on delete. |
| breed_id | FK → breeds | yes | Null on delete. |
| sex | char(1) | yes | `M` / `F`. |
| estimated_birth_date | date | yes | Store this, **not** a static age. |
| birth_date_is_estimated | boolean | no (def true) | Whether the birth date is a guess. |
| size_id | FK → animal_sizes | no | |
| color | varchar | yes | |
| microchip | varchar | yes | **Unique** when present. |
| neutered | boolean | no (def false) | Spay/neuter flag. |
| neuter_date | date | yes | |
| status_id | FK → animal_statuses | no | Current lifecycle status. |
| notes | text | yes | |
| timestamps, deleted_at | | | |

### Rules & invariants
- **Age is derived** from `estimated_birth_date`, never stored as a number (ADR-0006).
- `microchip` is unique across all animals when set; multiple animals may have
  `NULL` microchip.
- `status_id` reflects the *current* status only. History is in
  `animal_status_history` — write a history row on every status change.
- An animal's intake details live in `rescues` (1:1), not on this table.

## `rescues`

The single intake event that brought the animal into care (ADR-0003).

| Column | Type | Null | Notes |
|--------|------|------|-------|
| id | bigint PK | | |
| organization_id | FK → organizations | no | |
| animal_id | FK → animals | no | **Unique** — one rescue per animal. Cascade on delete. |
| rescue_date | datetime | no | |
| location | varchar | yes | Where the animal was found. |
| rescued_by | varchar | yes | Free text (may be a volunteer name). |
| condition | varchar | yes | Condition at intake. |
| description | text | yes | |
| timestamps, deleted_at | | | |

### Rules & invariants
- Exactly **one** rescue per animal (enforced by unique `animal_id`). If an
  animal could be re-intaken after release, this becomes 1:N — revisit ADR-0003.

## `animal_images`

| Column | Type | Null | Notes |
|--------|------|------|-------|
| id | bigint PK | | |
| animal_id | FK → animals | no | Cascade on delete. |
| path | varchar | no | Storage path/URL. |
| timestamps | | | No soft delete (delete = remove image). |

## `animal_status_history`

Append-only log of status transitions. Drives audit and time-in-status metrics.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| id | bigint PK | | |
| animal_id | FK → animals | no | Cascade on delete. |
| old_status_id | FK → animal_statuses | yes | Null for the first status. |
| new_status_id | FK → animal_statuses | no | |
| changed_by | FK → users | yes | Actor; null on delete. |
| notes | text | yes | Reason for the change. |
| timestamps | | | `created_at` = transition time. Append-only. |

### Rules & invariants
- Write one row **whenever `animals.status_id` changes**. `new_status_id` must
  equal the animal's new `status_id`.
- Never update or delete rows — corrections are new rows.

## `animal_tags` (pivot)

Many-to-many between animals and `tags`.

| Column | Type | Notes |
|--------|------|-------|
| animal_id | FK → animals | Composite PK, cascade on delete. |
| tag_id | FK → tags | Composite PK, cascade on delete. |

- No `id`, no timestamps. Composite PK `(animal_id, tag_id)` prevents duplicates.
