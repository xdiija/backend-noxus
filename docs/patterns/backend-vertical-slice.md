# Backend Development Patterns (Vertical Slice)

How a resource is built end-to-end in this codebase. New shelter resources
**must** follow this layering — it mirrors the existing `users`, `suppliers`,
and `roles` flows. The **Supplier** slice is the canonical reference
implementation: it is the one flow that is already **organization-scoped**, so
copy it (not User/Role) when building a shelter resource.

> ⚠️ **Multi-tenancy addendum:** the `users` and `roles` slices predate the
> shelter module and are **not** organization-scoped — don't copy them for
> tenant data. `suppliers` shows the scoping done right: reads filtered by org
> in the Service, `organization_id` set from the authenticated user on create,
> and uniqueness composed with `organization_id`. See
> [Organization scoping](#organization-scoping) below and
> [ADR-0001](../decisions/0001-multi-tenancy-org-scoping.md).

## Layers & responsibilities

Request flow: **Route → Controller → FormRequest → DTO → Service → Model → Resource → Response**

| Layer | Location | Responsibility | Must NOT |
|-------|----------|----------------|----------|
| Route | `routes/api.php` | Map URL → controller action; attach `auth` + `check.permissions` middleware. | Contain logic. |
| Controller | `app/Http/Controllers/<Resource>Controller.php` | Orchestrate only: receive validated request, build DTO, call Service, wrap result in a Resource. Constructor-inject the Service. | Query models, contain business rules, or validate. |
| FormRequest | `app/Http/Requests/<Resource>/` | Validation + input sanitization + PT-BR messages. | Persist or transform for storage. |
| DTO | `app/DTOs/<Resource>/<Resource>DTO.php` | Typed, immutable carrier from request → service. | Contain business logic or touch the DB. |
| Service | `app/Services/<Resource>Service.php` | All business logic + Eloquent access; returns models/paginators. | Know about HTTP (requests/responses). |
| Model | `app/Models/<Model>.php` | Eloquent mapping, relationships, casts, `SoftDeletes`, accessors. | Contain cross-entity workflow logic. |
| Resource | `app/Http/Resources/<Resource>Resource.php` | Shape the JSON response (formatting, enums, dates). | Contain business logic or DB queries. |

Supporting pieces: `app/Enums/` (e.g. `Status` with `label()`), `app/Helpers/`
(`DateHelper::toBR`, `CpfHelper`, `CnpjHelper`, `PhoneHelper`, `MoneyHelper`),
`app/Rules/` (`CpfRule`, `PhoneRule`).

## Controller conventions

- Thin. One method per action: `index`, `store`, `show`, `update`, `destroy`,
  plus resource-specific actions (e.g. `changeStatus`).
- Constructor property promotion to inject the Service:
  `public function __construct(protected AnimalService $animalService) {}`
- `store`/`update` build the DTO from the request and hand it to the service:
  `$this->animalService->create(AnimalDTO::fromRequest($request))`, then return a
  Resource.
- `index` returns a Resource collection with **ETag / 304** support:
  ```php
  $response = AnimalResource::collection(
      $this->animalService->list($request->input('per_page'), $request->input('filter'))
  )->response();
  $response->setEtag(md5($response->getContent()));
  $response->isNotModified($request);
  return $response;
  ```
- `destroy` → `response()->noContent()`. Status toggles → a JSON message.

## FormRequest conventions

- One folder per resource: `app/Http/Requests/<Resource>/`.
- **Two request classes** (matching the existing split):
  - `StoreUpdate<Resource>Request` — shared by store **and** update. For unique
    rules use `Rule::unique('table')->ignore($this->id)` so updates don't
    collide with themselves. **Add `organization_id` to unique scopes** for
    shelter tables (e.g. `Rule::unique('adopters')->where('organization_id', $orgId)->ignore($this->id)`).
  - `Index<Resources>Request` — filter/pagination validation
    (`per_page`, `filter`), with defaults applied in `passedValidation()`.
- `authorize(): bool` returns `true` (authorization lives in
  `check.permissions` middleware).
- Sanitize input in `prepareForValidation()` (e.g. strip CPF/phone masks via
  Helpers) before rules run.
- `messages()` returns **Portuguese** validation messages.

## DTO conventions

- `app/DTOs/<Resource>/<Resource>DTO.php`, one per resource.
- Immutable: `public readonly` promoted constructor properties, typed.
- `public static function fromRequest(StoreUpdate<Resource>Request $request): self`
  builds from `$request->validated()` (cast types here, e.g. `(int)`).
- `public function toArray(): array` produces the model-ready payload the Service
  passes to `Model::create()` / `->update()`.
- The DTO carries **request data only**. `organization_id` is *not* in the DTO —
  it's added by the Service on create, from the authenticated user (see the
  Supplier slice). This keeps the DTO free of `auth()` and impossible to spoof
  from the request body.

## Service conventions

- `app/Services/<Resource>Service.php`, constructor-injectable, no HTTP types.
- Standard methods: `list(int $perPage, string $filter): LengthAwarePaginator`,
  `find(string $id): Model`, `create(<Resource>DTO $dto): Model`,
  `update(string $id, <Resource>DTO $dto): Model`, `delete(string $id): void`,
  plus domain actions (`changeStatus`, and shelter workflows like
  `return`/`adopt`).
- Use `findOrFail` for single lookups.
- **This is where shelter business rules live** — e.g. the "one active adoption
  per animal" check (ADR-0002), writing an `animal_status_history` row on status
  change, keeping `animals.neutered` in sync with a castration surgery.
- Cross-entity workflows that touch multiple tables belong here and should run
  in a DB transaction.

## Resource conventions

- `app/Http/Resources/<Resource>Resource.php`, `toArray(Request): array`.
- Format dates with `DateHelper::toBR(...)`.
- Expose status/enum fields as objects: `['id' => $this->status, 'name' => Status::tryFrom($this->status)?->label()]`.
- For lookup FKs (status, size, type), return the nested lookup `{id, name, color?}`
  rather than the raw id, using `whenLoaded()` relationships.

## Route conventions

- Group per resource with a prefix and shared middleware:
  ```php
  Route::prefix('animals')->middleware('auth')->controller(AnimalController::class)->group(function () {
      Route::get('/', 'index')->middleware('check.permissions:animals,view');
      Route::get('/{id}', 'show')->middleware('check.permissions:animals,view');
      Route::post('/', 'store')->middleware('check.permissions:animals,create');
      Route::put('/{id}', 'update')->middleware('check.permissions:animals,update');
      Route::delete('/{id}', 'destroy')->middleware('check.permissions:animals,update');
  });
  ```
- Note: permission middleware exists in the base project. The shelter module was
  scoped to **ignore roles/permissions for now** — keep the `auth` middleware and
  add `check.permissions` later if/when roles are wired in.

## Organization scoping

Follow the **`suppliers`** slice — it implements all of this. For every shelter
resource:

1. **Read:** every Service query filters by the current user's `organization_id`.
   In `SupplierService` this is centralized in a `scoped()` helper
   (`Supplier::query()->where('organization_id', $this->organizationId())`) that
   `find`/`update`/`delete`/`changeStatus` all route through, so a tenant can
   never reach another org's rows. A global Eloquent scope is an alternative.
   **Watch OR-filters:** wrap them in a nested `where(fn ($q) => ...)` so an
   `orWhere` can't escape the org scope.
2. **Write:** the Service sets `organization_id` on create from
   `auth()->user()->organization_id` (merged into the create payload); it is
   never accepted from the request body, and `update` never changes it.
3. **Uniqueness:** compose unique validation *and* the DB index with
   `organization_id` (suppliers: `unique(organization_id, cnpj)` +
   `Rule::unique('suppliers')->where('organization_id', $orgId)`). Same pattern
   for adopter `cpf` (unique per org, not globally).
4. **Lookups:** when listing a configurable lookup, include system defaults:
   `where(fn ($q) => $q->whereNull('organization_id')->orWhere('organization_id', $orgId))`.

## Checklist — adding a new shelter resource

1. Migration already exists (`database/migrations/2026_07_01_*`).
2. Model in `app/Models/` — `$fillable`, `SoftDeletes`, relationships, casts,
   accessors (e.g. `age` from `estimated_birth_date`, ADR-0006).
3. `app/Http/Requests/<Resource>/StoreUpdate<Resource>Request.php` +
   `Index<Resources>Request.php`.
4. `app/DTOs/<Resource>/<Resource>DTO.php` (`fromRequest`, `toArray`; request
   fields only — **no** `organization_id`).
5. `app/Services/<Resource>Service.php` (CRUD + domain rules + **org scoping**;
   sets `organization_id` on create from the authenticated user).
6. `app/Http/Resources/<Resource>Resource.php`.
7. `app/Http/Controllers/<Resource>Controller.php` (thin, injects Service).
8. Routes in `routes/api.php` (prefixed group, `auth` middleware).
9. Feature test under `tests/Feature/`.
