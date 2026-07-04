# Shelter Module — Build Roadmap

The shelter/NGO feature is built in **modules**, each an independently testable
vertical slice. Build in dependency order; each module follows
[`patterns/backend-vertical-slice.md`](patterns/backend-vertical-slice.md)
(Controller → FormRequest → DTO → Service → Resource → routes → test) with
`organization_id` scoping in the Service, and reads the relevant specs in
[`data-model/`](data-model/) + the ADRs in [`decisions/`](decisions/).

Migrations already exist (`database/migrations/2026_07_01_*`), so each module is
"wire the slice + test", not "design the schema".

## Status

| Module | Status |
|--------|--------|
| 0 — Foundation & tenancy | ✅ Done |
| 1 — Animal reference data (lookups) | ✅ Done |
| 2 — Animals (core) | ⬜ Next |
| 3 — Adoptions | ⬜ |
| 4 — Health | ⬜ |
| 5 — Fostering & volunteers | ⬜ |
| 6 — Finance | ⬜ |
| 7 — Cross-cutting (documents, audit) | ⬜ |

Dependency spine: `0 → 1 → 2 → 3`; modules `4,5,6,7` are largely independent.

---

## Module 0 — Foundation & tenancy ✅
Users ↔ organizations wired; `organization_id` set on user create; `organization`
exposed on `login`/`me`; user management org-scoped; per-user `permissions` map on
`login`/`me`; frontend permission gating (menus/edit/status/delete/new). Tenant
isolation covered by `tests/Feature/TenantIsolationTest.php`.
Tables: `organizations`, `users.organization_id`.

## Module 1 — Animal reference data (lookups) ✅
Read/list + select endpoints (and management CRUD for the org-customizable ones)
for the tables the Animal form depends on. Configurable lookups serve the
`NULL`-org system defaults **plus** the org's own rows
(`whereNull('organization_id')->orWhere('organization_id', $orgId)`, ADR-0004).
- **Tables:** `species`, `breeds`, `animal_sizes`, `animal_statuses`, `tags`.
- **Testable outcome:** `GET /species`, `/breeds?species_id=`, `/animal-sizes`,
  `/animal-statuses`, `/tags` return the seeded defaults (+ org customs).
- Start with `species` + `breeds` (dependency-free), then the configurable
  lookups. Global reference (`species`, `breeds`) is shared (no org column).

## Module 2 — Animals (core) ⬜
Animal CRUD (org-scoped); `age` accessor from `estimated_birth_date` (ADR-0006);
microchip uniqueness; neuter fields; `rescue` 1:1 intake (ADR-0003); status change
writes `animal_status_history`. Optional sub-step: `animal_images`, `animal_tags`.
Tables: `animals`, `rescues`, `animal_status_history` (+ images/tags).

## Module 3 — Adoptions ⬜
Adopters CRUD; Adoptions with adopt/return/cancel and the "one active adoption per
animal" rule (ADR-0002); syncs animal status; follow-ups.
Tables: `adopters`, `adoptions`, `adoption_statuses`, `adoption_follow_ups`,
`adoption_follow_up_statuses`.

## Module 4 — Health ⬜
Health records; vaccine catalog + applied doses; medication catalog + courses;
appointments; surgeries (keep `animals.neutered` in sync with a castration).
Tables: `health_records`, `vaccines`, `animal_vaccines`, `medications`,
`animal_medications`, `appointments`, `surgeries`.

## Module 5 — Fostering & volunteers ⬜
Volunteers CRUD; foster homes CRUD; foster placements (open/close stay, capacity).
Tables: `volunteers`, `foster_homes`, `animal_foster_homes`.

## Module 6 — Finance ⬜
Donors; Donations (typed, in-kind allowed); Expenses (categorized, `paid`).
Tables: `donors`, `donations`, `donation_types`, `expenses`, `expense_categories`.

## Module 7 — Cross-cutting / system ⬜
Documents (polymorphic, ADR-0005); Audit logs; dashboard/report endpoints.
Tables: `documents`, `audit_logs`.

---

## Conventions carried over from Module 0
- New resources are **organization-scoped** in the Service (reads filtered by
  `organization_id`; org set server-side on create, never from request input).
  Follow the `suppliers` slice — it's the reference implementation.
- Roles/permissions gating is **deferred** for shelter resources for now: keep the
  `auth` middleware on routes; add `check.permissions:<route>,<action>` later.
  (Permission identity = menu route without the leading slash.)
- When adding a new permission-guarded resource later, its `check.permissions`
  arg must equal its route without the slash (single-segment routes only).
