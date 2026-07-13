# Shelter Module — Build Roadmap

The shelter/NGO feature is built in **modules**, each an independently testable
vertical slice. Build in dependency order; each module follows
[`patterns/backend-vertical-slice.md`](patterns/backend-vertical-slice.md)
(Controller → FormRequest → DTO → Service → Resource → routes → test) with
`organization_id` scoping in the Service, and reads the relevant specs in
[`data-model/`](data-model/) + the ADRs in [`decisions/`](decisions/). The UI for
a module follows [`patterns/frontend-vertical-slice.md`](patterns/frontend-vertical-slice.md)
(Service → List page → Form page → route → menu), including the shelter-module
deviations (deferred permissions/menu, client-paginated lookup lists).

Migrations already exist (`database/migrations/2026_07_01_*`), so each module is
"wire the slice + test", not "design the schema".

## Status

| Module | Status |
|--------|--------|
| 0 — Foundation & tenancy | ✅ Done |
| 1 — Animal reference data (lookups) | ✅ Done |
| 2 — Animals (core) | ✅ Done |
| 3 — Adoptions | ✅ Done |
| 4 — Health | 🟨 Backend done — UI pending |
| 5 — Fostering & volunteers | ✅ Done |
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
  lookups. `species` is global read-only reference (no CRUD). `breeds`,
  `animal_sizes`, `animal_statuses`, `tags` are **Noxus-managed global lookups**:
  full CRUD, but only Noxus users write (the `noxus` middleware); all rows are
  `NULL`-org and every tenant reads the same list — see
  [ADR-0008](decisions/0008-noxus-managed-global-lookups.md).

## Module 2 — Animals (core) ✅
Animal CRUD (org-scoped, `suppliers` pattern); `age` accessor derived from
`estimated_birth_date` (ADR-0006); global microchip uniqueness; neuter fields;
`rescue` 1:1 intake nested in the Animal payload (ADR-0003); status changes (on
create, on update, and via `PUT /animals/{id}/status`) write an
`animal_status_history` row through `AnimalService::recordStatusChange`.
`animal_tags` synced from the form. `animal_images`: photo upload/gallery on
MinIO (S3), **presigned-private** — nested `animals/{animal}/images` routes,
stored via the `s3` disk, served through short-lived signed URLs generated on
the `s3_public` disk (see [[minio-presigned-image-storage]]).
Menu: **Abrigo** group → **Animais** (`AnimalMenusSeeder`, Admin granted full
access; Noxus bypasses). Covered by `tests/Feature/AnimalTest.php` and
`tests/Feature/AnimalImageTest.php`.
Tables: `animals`, `rescues`, `animal_status_history`, `animal_tags`,
`animal_images`.

## Module 3 — Adoptions ✅
Adopters CRUD (org-scoped, `suppliers` pattern; `cpf` unique per org). Adoptions
(org-scoped) with the adopt/return/cancel workflow: `POST /adoptions` (adopt),
`PUT /adoptions/{id}/return`, `PUT /adoptions/{id}/cancel`, plus edit/list/show.
The "one active adoption per animal" rule (ADR-0002) is enforced in
`AdoptionService` (Pendente/Concluída with no `return_date` block a new one); each
transition syncs the animal's status (Adotado / Devolvido / Disponível) by
delegating to `AnimalService::changeStatus`, which writes the
`animal_status_history` row. Follow-ups are nested under an adoption
(`adoptions/{adoption}/follow-ups`), scoped via the parent. `adoption_statuses`
and `adoption_follow_up_statuses` are exposed as read-only lookup endpoints
(system defaults + org's own) for the forms.
Menu: **Abrigo** group → **Adotantes** (`/adopters`) + **Adoções** (`/adoptions`)
(`AdoptionMenusSeeder`, Admin granted full access; the nested follow-up and
status-lookup routes reuse the `adoptions` grant). Covered by
`tests/Feature/AdopterTest.php`, `AdoptionTest.php`, `AdoptionFollowUpTest.php`.
Tables: `adopters`, `adoptions`, `adoption_statuses`, `adoption_follow_ups`,
`adoption_follow_up_statuses`.
Frontend: `AdoptersPage`/`AdoptersForm` (CPF/phone masks) and
`AdoptionsPage`/`AdoptionsForm` (searchable animal/adopter selects, nested
follow-ups in edit mode); return/cancel are row actions on the list
(`ReturnAdoptionDialog` + the shared `SelectNotesDialog`). Menus render from
`getMenus()` after re-seeding + re-login.

## Module 4 — Health 🟨 (backend done, UI pending)
Clinical history nested under an animal (follow-ups/images pattern): five
resources under `animals/{animal}/…` — `health-records`, `vaccines` (applied
doses), `medications` (courses, `end_date` null = ongoing), `appointments`,
`surgeries` — each with index/store/update/destroy, org scoping enforced in
the Service via the org-scoped parent animal, and `organization_id` set from
the animal where the table carries it. The routes reuse the `animals`
permission grant (`view` to read, `update` to write) — no new menus.
`vaccines` and `medications` catalogs are global reference data like species:
read-only `GET /vaccines` + `GET /medications` (seeded, shared by all orgs).
Invariant (health.md): recording a castration keeps the animal in sync —
`StoreUpdateSurgeryRequest` accepts a non-persisted `is_castration` flag and
`SurgeryService` sets `animals.neutered = true` + `neuter_date = surgery_date`
in the same transaction (never un-neuters). Covered by
`tests/Feature/HealthRecordTest.php`, `AnimalVaccineTest.php`,
`AnimalMedicationTest.php`, `AppointmentTest.php`, `SurgeryTest.php`.
Tables: `health_records`, `vaccines`, `animal_vaccines`, `medications`,
`animal_medications`, `appointments`, `surgeries`.
**Pending:** frontend (health sections on the Animal form/detail per
`patterns/frontend-vertical-slice.md`).

## Module 5 — Fostering & volunteers ✅
Volunteers CRUD (org-scoped, `suppliers` pattern, active/inactive `status`
toggle). Foster homes CRUD (org-scoped; list/show expose
`open_placements_count` so the UI shows occupancy against `capacity`).
Placements (stays) are nested under their home
(`foster-homes/{fosterHome}/placements`, follow-ups pattern — org scoping via
the org-scoped parent, routes reuse the `foster-homes` grant):
`POST` opens a stay, `PUT /{id}/close` closes it, `PUT /{id}` corrects the
entry date (animal immutable). `FosterPlacementService` enforces the
fostering.md invariants — at most one open stay per animal (org-wide) and the
home's capacity (null = unlimited) — and keeps the animal's status in sync via
`AnimalService::changeStatus` (+history): open → "Em Lar Temporário"; close →
"Disponível" **only if** still "Em Lar Temporário" (never clobbers e.g. an
adoption made from the foster home).
Menu: **Abrigo** group → **Lares Temporários** (`/foster-homes`) +
**Voluntários** (`/volunteers`) (`FosteringMenusSeeder`, Admin granted full
access). Covered by `tests/Feature/VolunteerTest.php`, `FosterHomeTest.php`,
`FosterPlacementTest.php`.
Tables: `volunteers`, `foster_homes`, `animal_foster_homes`.
Frontend: `VolunteersPage`/`VolunteersForm` (phone mask, status toggle) and
`FosterHomesPage` (occupancy chip) / `FosterHomesForm` (nested stays in edit
mode: searchable animal select to open a stay, close via
`ClosePlacementDialog`, delete). Menus render from `getMenus()` after
re-seeding + re-login.

## Module 6 — Finance ⬜
Donors; Donations (typed, in-kind allowed); Expenses (categorized, `paid`).
Tables: `donors`, `donations`, `donation_types`, `expenses`, `expense_categories`.

## Module 7 — Cross-cutting / system ⬜
Documents (polymorphic, ADR-0005); Audit logs; dashboard/report endpoints.
Tables: `documents`, `audit_logs`.

**Dashboard/report endpoints — notes from the `HomePage` charts built ahead of
this module:** `HomePage.vue` currently computes everything client-side from
existing CRUD endpoints (animals/adoptions/volunteers), paginating through
every page with `per_page=100` and aggregating in the browser — workable at
today's shelter scale but not something to keep scaling. When this module is
built, prefer dedicated aggregate endpoints over more client-side pagination
loops, in particular:
- **Status/species counts** — `HomePage` derives these by fetching every
  animal; a `GET` that returns pre-aggregated counts (e.g. grouped by
  `status_id`/`species_id`) avoids pulling full rows just to count them.
- **Monthly trends** (adoptions per month, rescues vs. adoptions) — currently
  bucketed client-side from `adoption_date`/`rescue.rescue_date` strings across
  a full paginated fetch; a real endpoint should accept a date range and
  return pre-bucketed series server-side.
- **Upcoming vaccines/appointments due** — not built at all yet. `animal_vaccines`
  and `appointments` are only reachable per-animal (`animals/{animal}/vaccines`,
  `animals/{animal}/appointments}`), so there's no way today to ask "what's due
  across every animal in the org in the next N days." This needs a
  cross-animal endpoint, not just client aggregation.
- If status/species/monthly-trend endpoints land, swap `HomePage`'s
  `fetchAllPages` aggregation for them and delete the client-side grouping
  logic — it becomes dead weight once the backend does the counting.

---

## Conventions carried over from Module 0
- New resources are **organization-scoped** in the Service (reads filtered by
  `organization_id`; org set server-side on create, never from request input).
  Follow the `suppliers` slice — it's the reference implementation.
- **Menus are DB-backed and permission-gated.** Every navigable resource gets a
  `menus` row + `role_menu` grants (seeded), and every route is authorized with
  `check.permissions:<route>,<action>` (`view`/`create`/`update`). Never hardcode
  nav in the frontend. The `check.permissions` arg = the route without its
  leading slash (single-segment routes). See the Menu conventions in
  [`patterns/frontend-vertical-slice.md`](patterns/frontend-vertical-slice.md).
- **Global "settings-like" resources live under the "Configurações" menu** —
  especially global reference data that is *not* org-scoped (the Module 1
  lookups, and future ones like them): seeded as children of "Configurações",
  `can_view` for all roles, writes Noxus-only (ADR-0008). Org-scoped resources
  go under their own domain menus.
