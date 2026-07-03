# Global Conventions

Rules that apply to **every** shelter table. Entity specs assume these and do
not repeat them.

## Multi-tenancy (organization scoping)

- Every tenant-owned table has `organization_id` → `organizations.id`.
- **All queries must be scoped by `organization_id`.** A user only ever sees
  rows belonging to their organization. This is enforced in the application
  layer (query scope / policy), not by the database alone.
- FK is `cascadeOnDelete` from `organizations`: hard-deleting an organization
  removes its data. In practice organizations use soft deletes, so cascade is a
  safety net, not the normal path.
- **Lookup tables are the exception:** `organization_id` is *nullable* there.
  `NULL` = system default, shared by all organizations. A non-null value = a
  custom entry owned by one organization. See ADR-0004.

## Soft deletes

- All **entity** tables use `deleted_at` (`softDeletes()`). Rows are never hard
  deleted through the app; queries exclude trashed rows by default.
- **Pivot** tables (`animal_tags`, `animal_foster_homes`, `animal_vaccines`,
  `animal_medications`) and append-only log tables (`animal_status_history`,
  `audit_logs`) do **not** use soft deletes.
- Most lookup tables carry `deleted_at` only where deactivation is expected
  (`animal_statuses`); simple lists (`species`, `donation_types`) do not.

## Timestamps

- `created_at` / `updated_at` on all tables (`timestamps()`), except pure pivots
  where noted.
- Log tables use `created_at` as the event time (they are append-only).

## Money

- All monetary columns are `DECIMAL(10,2)`. No floats. Single implied currency
  (BRL); there is no multi-currency support.

## Identifiers & naming

- Surrogate PK `id` (`BIGINT UNSIGNED`, auto-increment) on every table except
  the `animal_tags` pivot, which uses a composite PK `(animal_id, tag_id)`.
- Table names: plural, snake_case. Columns: snake_case. FKs: `<singular>_id`.
- Booleans use the `status` naming from the base project where they mean
  "active" (e.g. `volunteers.status`), and descriptive names elsewhere
  (`agreement_signed`, `paid`, `neutered`, `is_default`).

## Foreign keys

- Declared with `constrained()`. Where the referenced table name can't be
  inferred from the column (`size_id`, `status_id`, `old_status_id`, …), the
  table is named explicitly.
- Delete behavior:
  - `cascadeOnDelete` — children that cannot outlive their parent
    (e.g. `animal_images`, `rescues`, `animal_status_history` → animal).
  - `nullOnDelete` — optional references that should survive parent removal
    (e.g. `donations.donor_id`, `animals.breed_id`, `audit_logs.user_id`).
  - default (restrict) — references that must not dangle
    (e.g. `animals.species_id`, `adoptions.animal_id`).

## PII / LGPD

- `organizations.tax_id`, `adopters.cpf`, and contact fields are personal data.
  Uniqueness on `cpf` is **per organization** (`unique(organization_id, cpf)`),
  not global. Consider encryption at rest for these columns (not yet applied).
