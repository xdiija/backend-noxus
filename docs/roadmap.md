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
| 6 — Finance | ✅ Done |
| 7 — Cross-cutting (documents, audit) | ✅ Done |
| 8 — Kennels (housing / where the animal is at) | ✅ Done |

Dependency spine: `0 → 1 → 2 → 3`; modules `4,5,6,7,8` are largely independent
(8 touches 2 and 5: animal status changes close kennel stays, and kennel/foster
stays are mutually exclusive).

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

## Module 6 — Finance ✅
Lightweight income/outgoing tracking (ADR-0007). Donors CRUD (org-scoped,
`suppliers` pattern). Donations CRUD (org-scoped): typed by `donation_types`;
the donor is either an org-scoped `donor_id` link **or** the inline `donor_*`
fallback fields (a linked donor wins — `StoreUpdateDonationRequest` drops the
inline fields when `donor_id` is present); `amount` nullable for in-kind gifts;
`donation_date` can't be future. Expenses CRUD (org-scoped): categorized by
`expense_categories`, `amount` required, `expense_date` may be future (unpaid
bill), and the `paid` boolean is the only accounts-payable state — toggled via
`PUT /expenses/{id}/paid` (volunteers `changeStatus` pattern).
`donation_types` and `expense_categories` are read-only configurable lookups
(system defaults + org's own, ADR-0004 — the adoption-statuses pattern),
exposed as `GET /donation-types` / `GET /expense-categories` reusing the
`donations`/`expenses` grants.
Menu: new top-level **Financeiro** group → **Doadores** (`/donors`),
**Doações** (`/donations`), **Despesas** (`/expenses`) (`FinanceMenusSeeder`,
Admin granted full access; Noxus bypasses). Covered by
`tests/Feature/DonorTest.php`, `DonationTest.php`, `ExpenseTest.php`.
Tables: `donors`, `donations`, `donation_types`, `expenses`, `expense_categories`.
Frontend: `DonorsPage`/`DonorsForm` (phone mask) and
`DonationsPage`/`DonationsForm` (searchable donor select with inline
anonymous-donor fields when no donor is linked, BRL-masked optional amount)
and `ExpensesPage` (paid/pending chip + toggle row action) / `ExpensesForm`
(category select, BRL-masked amount, paid toggle). Menus render from
`getMenus()` after re-seeding + re-login.

## Module 7 — Cross-cutting / system ✅
**Documents** (polymorphic, ADR-0005): top-level org-scoped resource
(`GET/POST /documents`, `PUT/DELETE /documents/{id}`) — not nested, because
any record or the org itself (both morphs null) can own one. Files live on
MinIO/S3 presigned-private like animal images; the original filename is kept
in the object key (`{uniqid}_{slug.ext}` — the schema has no name column by
design) and exposed as `name` in the Resource. The API accepts friendly owner
keys (`Document::OWNER_TYPES`: animal, adopter, adoption, volunteer,
foster_home) and `DocumentService` resolves the owner **inside the org scope**
(404 cross-org). Only the `type` metadata is editable; replacing a file =
delete + re-upload.
**Audit logs**: append-only trail written by the `Auditable` model concern
(`app/Models/Concerns/Auditable.php`) on created/updated/deleted — applied to
Animal, Adopter, Adoption, Volunteer, FosterHome, Donor, Donation, Expense and
Document. Only authenticated actions are audited (seeders stay silent); org
comes from the affected row, falling back to the actor's. Read-only API:
`GET /audit-logs` (org-scoped, filters: `entity`, `entity_id`, `action`,
`filter` on entity/user name) — no write surface anywhere.
**Dashboard endpoints** (auth-only, no `check.permissions` — the panel is the
landing page and returns only the caller's own-org aggregates):
`GET /dashboard/summary` (animal counts by status — zero-count statuses
included, sorted — and by species, + active volunteer count),
`GET /dashboard/monthly-trends?months=N` (adoptions + rescues pre-bucketed per
month, zero months included) and `GET /dashboard/upcoming-appointments?days=N`
(cross-animal agenda: scheduled `appointment_date`s **and** suggested
`next_visit` returns inside the window, date-ordered). *Note:* "vaccines due"
from the original plan isn't representable — `animal_vaccines` has no
next-dose column (migrations are canonical), so the agenda covers
appointments/returns only.
Menu: new top-level **Sistema** group → **Documentos** (`/documents`, Admin
view/create/update) + **Auditoria** (`/audit-logs`, Admin view-only)
(`SystemMenusSeeder`; Noxus bypasses). Covered by
`tests/Feature/DocumentTest.php`, `AuditLogTest.php`, `DashboardTest.php`.
Tables: `documents`, `audit_logs`.
Frontend: `DocumentsPage` (server-paginated list with owner-type filter;
upload/edit-type happen in dialogs — upload links a searchable owner select
per kind; download opens the fresh presigned URL) and `AuditLogsPage`
(read-only list with entity/action select filters + action chips).
`HomePage` now consumes `dashboardService` (`summary`/`monthlyTrends`/
`upcomingAppointments`) — the `fetchAllPages` client-side aggregation is gone —
and gained an "Agenda" card listing upcoming consultas/retornos. Menus render
from `getMenus()` after re-seeding + re-login.

## Module 8 — Kennels (housing) ✅
Where the animal is at, inside the shelter (spec:
`data-model/entities/kennels.md`). Kennels CRUD (org-scoped, `suppliers`
pattern; name unique per org, app-enforced; `capacity` required;
`open_stays_count` exposed for occupancy — always derived, never stored;
delete blocked while open stays exist). Kennel status is the `kennel_statuses`
configurable lookup (read-only `GET /kennel-statuses`, ADR-0004; seeded:
Disponível ✅default, Em Manutenção, Isolamento) — a null `status_id` on
create falls back to the `is_default` row. Stays are nested under their kennel
(`kennels/{kennel}/stays`, foster-placements pattern): `POST` opens
(`KennelStayService` enforces kennels.md — one open stay per animal, capacity,
kennel not "Em Manutenção", animal on-site: no off-site status and no open
foster stay), `PUT /{id}` corrects open stays only (closed = immutable
history), `PUT /{id}/close` sets `exit_date`, `PUT /{id}/transfer` closes +
opens in the target kennel (reason "Transferência") in one transaction.
Kennel workflows never change the animal's status; the reverse hook lives in
`AnimalService::closeKennelStayIfOffSite` — any status change to
Adotado / Em Lar Temporário / Óbito (adoption, fostering, death, manual edit)
auto-closes the open stay.
Menu: **Abrigo** group → **Canis** (`/kennels`) (`KennelsMenusSeeder`, Admin
granted full access; nested stays + the status lookup reuse the `kennels`
grant). Covered by `tests/Feature/KennelTest.php`, `KennelStayTest.php`.
Tables: `kennel_statuses`, `kennels`, `animal_kennel_stays`
(migrations `2026_07_14_*`).
Frontend: `KennelsPage` (status + occupancy chips, server-paginated) /
`KennelsForm` (status select preselecting the `is_default` lookup row, size
select; nested stays in edit mode: searchable animal select + entry date +
reason to open, close via the shared `ClosePlacementDialog`, transfer via
`TransferStayDialog` — target kennel + date —, delete). `AnimalsForm` shows a
"Localização atual" banner (edit mode) from the new `current_kennel` /
`current_foster_home` fields on the animal show endpoint. Menus render from
`getMenus()` after re-seeding + re-login.

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
