# End-to-End Manual Test Plan (Shelter Modules 0–8)

## Phase 0 — Setup, auth & empty dashboard

- [X] Fresh DB: `php artisan migrate:fresh --seed` (inside the app container), then `npm run dev` in `frontend-noxus`.
- [X] **Login fails** with a wrong password (PT-BR error, stays on login page).
- [X] **Login succeeds** as `djalma@example.com` → lands on the dashboard.
- [X] Dashboard renders: animal counts by status (zero-count statuses shown), counts by species, active-volunteer count, monthly trends chart, and the "Agenda" card — all consistent with the seeded sample data.
- [X] Sidebar shows the menu groups: **Abrigo**, **Financeiro**, **Sistema**, **Configurações** (plus the legacy finance/admin menus).
- [X] **Minha Conta**: open, edit your own name, save, confirm it sticks after reload.
- [X] Logout → redirected to login; deep-linking a protected route (e.g. `/animals`) while logged out redirects to login.

## Phase 1 — Configurações: global lookups (login as `djalma`, Noxus)

These feed the Animal form, so they come first.

- [X] **Raças (Breeds)**: list shows seeded rows; filter by species works. Create "Vira-lata Teste" for a species; edit its name; it appears in the Animal form's breed select (checked in Phase 2).
- [X] **Portes (Animal sizes)**: list seeded rows; create/edit one.
- [X] **Status de Animais**: list seeded rows (Disponível, Adotado, Em Lar Temporário, Devolvido, Óbito…); create a custom one, e.g. "Em Quarentena".
- [X] **Tags**: create "Docil" and "FIV+".
- [X] Duplicate-name validation: try creating a breed/tag with an existing name → PT-BR validation error.
- [X] **Espécies** are read-only reference (no CRUD anywhere) — confirm no create/edit surface.

## Phase 2 — Abrigo → Animais (core)

- [X] **Create dog**: "Rex", species Cachorro, the breed you created, size, status Disponível, estimated birth date ~2 years ago, microchip `TEST-0001`, tags Docil + FIV+, and fill the nested **rescue** block (date, location, notes). Save.
- [X] List shows Rex; the **age** column derives from the birth date ("2 anos").
- [X] **Create cat**: "Mimi", no microchip, no rescue — save works (both optional).
- [X] **Microchip uniqueness**: create another animal with microchip `TEST-0001` → validation error (it's global, not per-org).
- [X] **Edit Rex**: change size, remove one tag, edit the rescue notes — everything persists.
- [x] **Photos**: upload 2 images on Rex; gallery renders them (presigned MinIO URLs); delete one; re-open the form → one remains.
- [x] **Status change** via the dedicated action: Rex → "Em Quarentena" (your custom status) with notes → status chip updates; change it back to Disponível.
- [ ] **Filters/search** on the list: by name, by status, by species.
- [X] **Delete** a throwaway animal (create "Descartável" first) → soft-deleted, gone from the list.

## Phase 3 — Abrigo → Canis (kennels)

Needs animals on-site (Rex and Mimi are Disponível).

- [ ] **Create kennels**: "Canil A" (capacity 2, status Disponível — confirm the status select preselects the default), "Canil B" (capacity 1), "Canil M" (status Em Manutenção).
- [ ] **Unique name per org**: creating another "Canil A" fails.
- [ ] **Open stay**: edit Canil A → add stay for Rex (entry date + reason). Occupancy chip on the list shows 1/2.
- [ ] **One open stay per animal**: try opening a stay for Rex in Canil B → blocked.
- [ ] **Maintenance block**: try opening a stay for Mimi in Canil M → blocked.
- [ ] **Capacity**: put Mimi + one more animal in Canil B (capacity 1) → second stay blocked.
- [ ] **Transfer**: transfer Rex from Canil A to Canil B (after freeing it) via the transfer dialog → old stay closed with reason "Transferência", new open stay in Canil B.
- [ ] **Correct entry date** on an open stay works; a **closed** stay can't be edited.
- [ ] **Delete blocked**: deleting a kennel with an open stay fails; close the stay, then delete succeeds (use a throwaway kennel).
- [ ] **AnimalsForm banner**: open Rex in edit mode → "Localização atual" shows his current kennel.

## Phase 4 — Abrigo → Adotantes & Adoções

- [ ] **Create adopter** "João Silva" with CPF + phone (masks apply); create "Maria Souza".
- [ ] **CPF unique per org**: second adopter with João's CPF fails.
- [ ] **Adopt**: new adoption — Rex + João, status Concluída. After saving:
  - [ ] Rex's status is **Adotado** (check Animais list).
  - [ ] Rex's **kennel stay auto-closed** (Canil B occupancy back to 0/1) — the Module 8 hook.
- [ ] **One active adoption per animal**: try adopting Rex to Maria → blocked with a clear error.
- [ ] **Follow-ups**: edit the adoption → add a follow-up (date, status, notes); edit it; delete one.
- [ ] **Return**: row action "devolver" on Rex's adoption (return date + reason) → adoption returned, Rex's status becomes **Devolvido**.
- [ ] Rex is adoptable again: create a second adoption (Rex + Maria) → allowed (ADR-0002: history keeps both rows). 
- [ ] **Cancel**: create an adoption for Mimi (status Pendente), then cancel it via the row action → Mimi back to **Disponível**.
- [ ] **Status history**: Rex's trail (Disponível → Adotado → Devolvido → Adotado) is recorded — visible on the animal, or via Auditoria in Phase 8.

## Phase 5 — Abrigo → Voluntários & Lares Temporários (+ Health via API)

- [ ] **Volunteers**: create "Ana" (phone mask); toggle her status inactive → chip updates; back to active. Dashboard's active-volunteer count reflects it.
- [ ] **Create foster home** "Lar da Ana" (capacity 1) and "Lar Grande" (no capacity = unlimited).
- [ ] **Open placement**: edit Lar da Ana → place Mimi. Then:
  - [ ] Mimi's status becomes **Em Lar Temporário**.
  - [ ] Occupancy chip shows 1/1.
- [ ] **Capacity**: second placement in Lar da Ana → blocked. Open one in Lar Grande for another animal → fine.
- [ ] **Kennel/foster mutual exclusion**: try opening a *kennel* stay for Mimi → blocked (she's fostered, off-site).
- [ ] **Adoption from foster home**: adopt Mimi (Concluída) while placed → status Adotado; then **close** her placement → status must **stay Adotado** (close never clobbers). Return the adoption afterwards to free her up.
- [ ] **Close placement** normal path (place + close another animal): status goes back to **Disponível**.
- [ ] **Health** — open Rex in edit mode; the **Saúde** card shows five tabs:
  - [ ] **Histórico clínico**: add an entry (date + type required — try saving without them → PT-BR error); edit it via the pencil action; the row updates in place.
  - [ ] **Vacinas**: the vaccine select lists the seeded catalog; add a dose ("1ª dose"); edit and delete it.
  - [ ] **Medicações**: add a course with no end date → row shows the **"Em uso"** chip; edit it to set a Término date → chip replaced by the date.
  - [ ] **Consultas**: add an appointment (date+time mask) with a future **Retorno sugerido**.
  - [ ] **Cirurgias**: add a surgery with a BRL-masked cost and the **"É castração"** toggle on → after saving, the form's **Castrado** toggle flips on with the surgery date as Data de castração (no reload needed).
  - [ ] The future consulta/retorno shows up in the dashboard **Agenda** card.

## Phase 6 — Financeiro

- [ ] **Donors**: create "Carlos Doador" (phone mask).
- [ ] **Donation (linked)**: donation from Carlos, type Dinheiro, BRL-masked amount, today's date.
- [ ] **Donation (anonymous/in-kind)**: no donor selected → inline donor fields appear; type Ração, **no amount** (nullable for in-kind) → saves.
- [ ] **Future donation date is rejected**; linking a donor **drops** any inline donor fields (edit the anonymous one, link Carlos, save, re-open → inline fields gone).
- [ ] **Expenses**: create "Ração mensal" (category, BRL amount, today, unpaid) and a **future-dated** unpaid one (upcoming bill — allowed, unlike donations).
- [ ] **Paid toggle**: row action flips pendente ↔ pago, chip updates.
- [ ] Amount is **required** on expenses (try saving without it → validation error).

## Phase 7 — Sistema: Documentos

- [ ] **Upload to a record**: PDF attached to Rex (owner type Animal, searchable select), type "Termo". Original filename preserved as its name.
- [ ] **Upload org-level**: document with no owner → allowed (org itself owns it).
- [ ] **Owner-type filter** on the list works.
- [ ] **Download** opens a working presigned URL.
- [ ] **Edit**: only the `type` is editable; replacing a file = delete + re-upload.
- [ ] **Delete** removes it from the list.

## Phase 8 — Sistema: Auditoria + dashboard re-check

- [ ] **Auditoria** lists the trail of everything you did: creates/updates/deletes for animals, adoptions, placements, donations, expenses, documents — with action chips and your user name.
- [ ] Filters work: by entity (e.g. only Animal), by action (e.g. only updated), and text filter on entity/user name.
- [ ] It's **read-only** — no create/edit/delete surface anywhere.
- [ ] **Dashboard** now reflects the whole session: status counts (Adotado/Devolvido/Em Lar Temporário…), species counts, trends chart shows this month's adoptions + rescues, Agenda shows the Phase 5 appointment.

## Phase 9 — Tenant isolation (login as `gabriel@example.com`, org 2)

- [ ] Animais, Adotantes, Adoções, Canis, Lares, Voluntários, Doadores, Doações, Despesas, Documentos, Auditoria: **none of org 1's data appears** (only Abrigo Dois's own seeded rows, if any).
- [ ] Dashboard shows only org 2 aggregates.
- [ ] Global lookups (breeds, sizes, statuses, tags) **do** show the rows created in Phase 1 — they're Noxus-managed globals shared by every org (ADR-0008).
- [ ] **Direct-ID probe**: hit an org-1 record by URL, e.g. `/animals?id=<Rex's id>` (or `GET /api/animals/{id}` in Swagger with Gabriel's token) → 404, not a data leak.
- [ ] Create an animal as Gabriel, confirm Djalma **doesn't** see it after switching back.

## Phase 10 — Permissions (login as `admin@example.com`, Admin role)

- [ ] Sidebar renders from the Admin role's grants; shelter + finance + system menus present.
- [ ] **Documentos**: Admin has view/create/update but **no delete** — the delete action is hidden/blocked.
- [ ] **Auditoria**: view-only.
- [ ] **Global lookups are Noxus-only writes**: as Admin, breeds/sizes/statuses/tags are readable, but create/edit is hidden (or the API returns 403).
- [ ] Sanity: Admin can still run a normal flow end-to-end (create an animal, adopt it) — permissions gate, they don't break the happy path.
- [ ] **Role/menu management is Noxus-only** (roles are global — one org's edit would hit every org): as Admin, **Perfis** and **Menus** don't appear in the sidebar, and `GET /api/roles` returns 403. As `djalma` (Noxus) they're visible and editable.
- [ ] **Noxus role can't be granted by Admin**: as Admin, creating/editing a user with the Noxus profile fails with a PT-BR validation error (the role isn't offered / API rejects it).
- [ ] (Optional) As **Noxus** (`djalma`), in **Perfis** revoke a grant (e.g. Animais → delete) from Admin, re-login as Admin, confirm the action disappears; restore it.

---

## Wrap-up

- [ ] No console errors in the browser during the run (keep DevTools open).
- [ ] `php vendor/bin/phpunit` still green after the manual session.
- [ ] Note anything odd here:

| # | Phase | What happened | Expected |
|---|-------|---------------|----------|
| 1 | | | |
