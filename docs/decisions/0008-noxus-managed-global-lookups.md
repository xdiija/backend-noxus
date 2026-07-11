# ADR-0008: Shelter reference lookups are Noxus-managed global data

**Status:** Accepted (2026-07-04) — supersedes the per-org model of ADR-0004 for
`breeds`, `animal_sizes`, `animal_statuses`, and `tags`.

## Context
ADR-0004 made these lookups *per-org customizable* (nullable `organization_id`:
`NULL` = shared default, non-null = one org's custom entry). In practice the
product wants a **single, curated** set of breeds, sizes, statuses, and tags,
maintained centrally by the Noxus operator, with every tenant seeing the same
list. Letting each tenant fork its own copies added complexity (read scopes,
`is_custom` gating, "defaults are read-only") without a real use case, and left
the seeded defaults uneditable by anyone.

## Decision
Treat `breeds`, `animal_sizes`, `animal_statuses`, and `tags` as **global
reference data managed by Noxus users**:

- **Rows are global.** New entries are created with `organization_id = NULL`;
  there are no org-owned rows. Every role reads the same list.
- **Fully permission-gated (`check.permissions`).** All routes carry
  `check.permissions:<route>,<action>` — reads use `view`, writes `create`/
  `update`. The `ShelterMenusSeeder` grants every role `can_view=1` (so the data
  is readable across the system — the Animal form and other future features
  consume it) but `can_create=0` / `can_update=0`, so only Noxus (who bypasses
  the check) manages the data. The "Configurações" menu is **not**
  `exclusive_noxus` — it appears read-only in every role's sidebar; write buttons
  are hidden. (An earlier revision used a dedicated `noxus` middleware and an
  `exclusive_noxus` menu; both were dropped in favour of the app-wide permission
  mechanism so the view grant can be reused by other parts of the system.)
- **Service scoping:** the `writable()` query targets `whereNull('organization_id')`
  (the global rows). `readable()` still tolerates an org predicate defensively,
  but no org-owned rows are produced.
- **Uniqueness** is validated `whereNull('organization_id')` (per species for
  breeds).
- **Frontend:** the pages gate create/edit/delete on
  `canCreate`/`canUpdate('<route>')` from the permissions map (same as every
  other resource). Non-Noxus roles have `view` only, so they see the list
  read-only; Noxus sees the write buttons.

The `organization_id` columns are **retained but dormant** (always `NULL`) — no
migration reverts them, so per-org customization could be reintroduced later
without a schema change. `species` remains global read-only (no CRUD).

## Consequences
- One curated list per lookup; Noxus can finally edit the seeded defaults.
- Tenants cannot tailor their own breeds/tags/etc. If that need returns, revisit:
  the nullable `organization_id` is still in place to support it.
- `is_custom` in the lookup API responses is now always `false` (kept for
  compatibility; not used for gating anymore).
- Enforcement is server-side (`check.permissions`); the frontend `canCreate`/
  `canUpdate` gate is UX only and must never be the sole guard.
