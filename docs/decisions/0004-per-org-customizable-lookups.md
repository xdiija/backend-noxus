# ADR-0004: Configurable lookups with nullable organization_id

**Status:** Superseded (2026-07-04) for `breeds`, `animal_sizes`,
`animal_statuses`, `tags` by [ADR-0008](0008-noxus-managed-global-lookups.md),
which makes those four Noxus-managed global data (write-gated to Noxus, all rows
`organization_id = NULL`). The nullable-`organization_id` mechanism below still
stands as the schema; only the *ownership/write* model changed. Lookups not yet
built (`adoption_statuses`, `donation_types`, `expense_categories`, …) are
unaffected and their model is TBD when their module lands.

## Context
Lookups like animal statuses, sizes, tags, donation types, and expense
categories are values users select from. Some organizations want to customize
them (their own kanban statuses, their own tags); others are happy with sensible
defaults. Making them global blocks customization; making them purely per-org
forces every new tenant to recreate the basics.

## Decision
Configurable lookup tables carry a **nullable** `organization_id`:
- `NULL` → a system default, shared by all organizations (seeded by
  `DefaultLookupsSeeder`).
- non-null → a custom entry owned by that one organization.

Consumers query `organization_id IS NULL OR organization_id = :orgId`.

Applies to: `animal_statuses`, `animal_sizes`, `adoption_statuses`,
`adoption_follow_up_statuses`, `donation_types`, `expense_categories`, `tags`,
and `breeds`. Global reference data (`species`, `vaccines`, `medications`) stays
shared with no org column.

**Amendment (2026-07-04):** `breeds` was promoted from global reference to a
configurable lookup so organizations can register breeds missing from the seeded
BR defaults. It carries a nullable `organization_id` (migration
`2026_07_04_000001_add_organization_id_to_breeds_table`) and uniqueness moved
from `(species_id, name)` to `(organization_id, species_id, name)`. `species`
stays global read-only — a new species is a rare, cross-tenant concern.

## Consequences
- New tenants get working defaults immediately; can add custom entries later.
- Queries must always include the `NULL OR org` predicate — a helper/scope
  should encapsulate it to avoid mistakes.
- Deleting a system default affects all tenants; guard against it in the UI.
- Open question: whether defaults are *cloned* per org on signup (editable
  copies) or *shared* read-only. Current model is shared read-only; cloning
  would happen in a registration service, not a migration.
