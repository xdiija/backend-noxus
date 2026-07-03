# ADR-0004: Configurable lookups with nullable organization_id

**Status:** Accepted

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
`adoption_follow_up_statuses`, `donation_types`, `expense_categories`, `tags`.
Global reference data (`species`, `breeds`, `vaccines`, `medications`) stays
shared with no org column.

## Consequences
- New tenants get working defaults immediately; can add custom entries later.
- Queries must always include the `NULL OR org` predicate — a helper/scope
  should encapsulate it to avoid mistakes.
- Deleting a system default affects all tenants; guard against it in the UI.
- Open question: whether defaults are *cloned* per org on signup (editable
  copies) or *shared* read-only. Current model is shared read-only; cloning
  would happen in a registration service, not a migration.
