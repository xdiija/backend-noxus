# Frontend Development Patterns (Vertical Slice)

How a resource is built end-to-end in the Vue 3 + Quasar SPA (`frontend-noxus/`).
This mirrors the backend slice — one API service, a list page, a form page,
routes, and a menu entry per resource. The **Suppliers** slice
(`suppliersService.js`, `SuppliersPage.vue`, `SuppliersForm.vue`) is the canonical
reference; the **Module 1 lookup** slices (`animalSizes`, `animalStatuses`, `tags`)
are the reference for *shelter* resources and their deviations (see
[Shelter-module specifics](#shelter-module-specifics)).

## Layers & responsibilities

Request flow: **Page/Form → Service → `useApi` → axios (`boot/axios`) → API**

| Layer | Location | Responsibility |
|-------|----------|----------------|
| Service | `src/services/<resource>Service.js` | Thin wrapper: pick the verbs a resource supports off `useApi('<route>')` and re-export them. No logic. |
| `useApi` | `src/composables/UseApi.js` | Generic axios verbs: `list`, `getByID`, `post`, `update`, `changeStatus`, `destroy` + query-string builder. Shared by every service. |
| List page | `src/pages/<Resource>Page.vue` | `q-table` of rows, `ViewHeader` with the "add" button, row actions (edit/status/delete), `$q.dialog` confirms, `notifications`. |
| Form page | `src/pages/<Resource>Form.vue` | `q-form` create **and** edit (edit mode = `route.params.id` present); loads via `getByID`, submits via `post`/`update`, maps a `makePayload()`. |
| Router | `src/router/routes.js` | Two entries per resource: `<resource>` (list) and `<resource>-form/:id?` (form, `meta.resource`). Children of the authed `MainLayout` route. |
| Menu | backend `menus` table → `MainLayout.vue` | Sidebar is **data-driven** from the per-role menu returned at login (`getMenus()`), rendered via `EssentialLink`. |

Supporting pieces: `src/composables/usePermissions.js` (per-resource gating),
`src/utils/notifications.js` (`notifySuccess`/`notifyError`),
`src/utils/mask.js` (`cnpjMask`/`phoneMask`/`unMask`),
`src/constants/statusOptions.js` (`STATUS`, `activeInactive`),
`src/services/authService.js` (user/menus/permissions from `localStorage`).

## Service conventions

- One file per resource, named `<resource>Service.js`, default-exporting a
  function that destructures the verbs it needs off `useApi('<api-route>')`:
  ```js
  import useApi from 'src/composables/UseApi'
  export default function tagsService() {
      const { list, getByID, post, update, destroy } = useApi('tags')
      return { list, getByID, post, update, destroy }
  }
  ```
- The argument to `useApi` is the **API route segment** (e.g. `'suppliers'`,
  `'animal-sizes'`); axios prepends the base URL (`process.env.API_URL`,
  default `http://localhost:8989/api/`).
- Only re-export the verbs the resource actually supports — e.g. lookups omit
  `changeStatus` (they have no active/inactive toggle); read-only reference
  resources export just `list`.
- `list(endPoint, queryParams)` turns a params object into a query string;
  arrays become `key[]=a&key[]=b`, and empty/null values are dropped.

## List-page conventions

- `ViewHeader` renders the title + the "Adicionar" button (`btnTo` = the form
  route name). Gate the button with `canCreate('<resource>')`.
- `q-table` with a `columns` array. A column with `name: 'actions'` +
  `align: 'right'` hosts the row buttons via a `body-cell-actions` slot.
- Row actions gate on `canUpdate('<resource>')`; use `$q.dialog({...})
  .onOk(async () => { ... })` (persistent, PT-BR labels) to confirm
  status-change and delete, then re-fetch.
- Surface outcomes with `notifySuccess` / `notifyError`.
- **Server-side pagination** (the default for entity resources): bind
  `v-model:pagination`, handle `@request`, send `{ page, per_page, filter }`,
  and read `data.data` + `data.meta.total` from the paginated response.

## Form-page conventions

- A single form handles create and edit. `isEditMode = computed(() =>
  !!route.params.id)`; set the header title from it.
- `onMounted`: if `route.params.id`, call `getByID(id)` and hydrate `form.value`
  (unwrap `data.data`; map nested lookup objects back to their id, e.g.
  `status: data.status?.id`).
- `onSubmit` branches on `form.value.id` → `update(makePayload(), id)` vs
  `post(makePayload())`, then `notifySuccess` and `router.push` back to the list.
- `makePayload()` returns exactly the fields the API expects (strip masks with
  `unMask`, coerce numbers). Keep it separate from the reactive `form`.
- **Surface backend validation errors** field-by-field:
  ```js
  const errors = error.response?.data?.errors
  if (errors) Object.keys(errors).forEach(k => notifyError(errors[k]))
  else notifyError(error.response?.data?.message || 'Erro ao salvar!')
  ```

## Router conventions

- Add both routes as children of the `MainLayout` (`requiresAuth`) route:
  ```js
  { path: 'tags', name: 'tags', component: () => import('src/pages/TagsPage.vue') },
  { path: 'tags-form/:id?', name: 'tagsForm', meta: { resource: 'tags' },
    component: () => import('src/pages/TagsForm.vue') },
  ```
- The list route `name` is what the form's "Listar"/back button targets; the
  form route `name` is the list page's "Adicionar" `btnTo`.

## Permissions & menu

- `usePermissions()` exposes `can`, `canView`, `canCreate`, `canUpdate`. The
  `resource` key is the **menu route without its leading slash** (e.g.
  `'suppliers'`), and it reads the permissions map cached from `login`/`auth/me`.
- The sidebar is **not** hard-coded: `MainLayout.prepareMenus()` builds it from
  `authService().getMenus()` (per-role menu tree from the backend) and renders it
  with `EssentialLink` (nested menus become `q-expansion-item`).
- The axios boot installs a **403 interceptor**: authenticated-but-forbidden
  requests keep the session, warn in yellow, and redirect to `home` (they do
  **not** log the user out).

### Menu conventions (apply to every new navigable resource)

1. **Menus live in the database, not the frontend.** A new navigable resource
   gets a `menus` row (seed it, e.g. in `ShelterMenusSeeder` or a resource
   seeder) plus `role_menu` grants, so it flows through `getMenus()` and is
   manageable in `MenusPage`. **Never hardcode nav in `MainLayout`.**
2. **Authorize every route with `check.permissions:<route>,<action>`** (`view`
   on reads, `create`/`update` on writes), and seed the matching `role_menu`
   grants per role. Noxus bypasses the check.
3. **Global "settings-like" resources go under "Configurações".** Any menu that
   is a system setting — **especially global reference data that is not scoped
   per organization** (the lookups, and future ones like it) — is seeded as a
   **child of the "Configurações" menu**, granted `can_view` to all roles and
   write grants to none (Noxus-managed). Org-scoped resources (suppliers,
   customers, animals, …) belong under their own domain menus, not here.

## Shelter-module specifics

Shelter resources deviate from the base pattern because **roles/permissions and
the backend menu are deferred** for them (see `roadmap.md` conventions). The
Module 1 lookup slices establish these rules — follow them for Module 2+ UI:

1. **Gate writes on `canCreate`/`canUpdate`, like every other resource.** The
   reference lookups (`breeds`, `animal_sizes`, `animal_statuses`, `tags`) are
   **Noxus-managed global data** (ADR-0008): they're real menu rows
   (`ShelterMenusSeeder`) granted `can_view` for all roles and write grants for
   none, so `canCreate('breeds')` / `canUpdate('breeds')` are `true` only for
   Noxus (who gets all-true) by default. Gate the "Adicionar" button on
   `canCreate('<route>')` and edit/delete on `canUpdate('<route>')`. The backend
   `check.permissions:<route>,create|update` middleware is the real guard.
2. **Nav is DB-driven.** The "Configurações" group and its children live in the
   `menus` table (`ShelterMenusSeeder`) with `role_menu` `can_view=1` for every
   role (write grants `0` → Noxus-only edits), so they show read-only in every
   sidebar and flow through `getMenus()` like every other menu — manageable via
   `MenusPage`. Add a new such page by seeding a child menu there (+ its
   `can_view` grants), not by hardcoding it in `MainLayout`. Because menus are
   cached from the login payload, a user must re-login to see a newly seeded menu.
3. **Configurable-lookup lists are non-paginated.** Their API returns a bare
   `{ data: [...] }` (a resource *collection*, no `meta`). Use **client-side**
   `q-table` pagination — load all rows once into `rows.value = data.data`, and
   do **not** wire `@request`/`v-model:pagination`/`data.meta.total`.
4. **One shared list; Noxus edits, others view.** All rows are global
   (`organization_id = NULL`), so `is_custom` is always `false` and is **not**
   used for gating. Show edit/delete + the "Adicionar" button via
   `canUpdate`/`canCreate('<route>')`; roles without the grant see a plain
   read-only table (no "Tipo"/`is_custom` column, no per-row lock).
5. **No status toggle.** Lookups have no active/inactive `changeStatus`; row
   actions are edit + delete only.
6. **`species`** is global read-only with **no management page** — a service
   exporting only `list`, consumed by forms (the Animal form's species→breed
   cascade; `breeds` takes `list('', { species_id })` to narrow by species).
   `breeds` itself *does* have a management page (Noxus-managed, per above).

## Checklist — adding a new shelter resource (frontend)

1. `src/services/<resource>Service.js` — `useApi('<api-route>')`, export the
   supported verbs.
2. `src/pages/<Resource>Page.vue` — `q-table` list (client-side pagination for
   lookups), `ViewHeader`, create/edit/delete gated on
   `canCreate`/`canUpdate('<route>')`, delete confirm.
3. `src/pages/<Resource>Form.vue` — create/edit `q-form`, `getByID` hydrate,
   `makePayload`, field-by-field error surfacing.
4. Two routes in `src/router/routes.js` (list + `-form/:id?`).
5. A child menu row under "Configurações" in `ShelterMenusSeeder` (+ `role_menu`
   `can_view` grants for every role), then re-seed. The sidebar renders it via
   `getMenus()`.
6. Verify: `npm run lint` on the new files, `npm run build` green.
