# Data Model — Shelter / ONG Module

Specification of the animal-shelter data model for Noxus. These docs are the
human- and agent-facing specification layer; the **Laravel migrations are the
canonical source of truth**. If a spec and a migration disagree, the migration
wins — fix the spec.

## How to read these docs (for humans and agents)

1. Start with [`conventions.md`](conventions.md) — global rules that apply to
   *every* table (multi-tenancy, soft deletes, timestamps, money, naming). They
   are not repeated in each entity spec.
2. Open the relevant [`entities/`](entities/) spec for the domain you're working
   on. Each file documents its tables' columns, relationships, and business
   rules/invariants.
3. Reference lookups (status/type/category tables + seeded defaults) live in
   [`lookups.md`](lookups.md).
4. The *why* behind non-obvious design choices is in
   [`../decisions/`](../decisions/) (ADRs). Read these before changing an
   invariant — they exist to stop a regression.
5. Domain vocabulary is in [`glossary.md`](glossary.md).
6. **How to build a resource** (Controller/FormRequest/DTO/Service/Resource
   layering) is in [`../patterns/backend-vertical-slice.md`](../patterns/backend-vertical-slice.md).

## Artifacts

| File | Role |
|------|------|
| `entities/*.md` | Per-domain table specs (columns, rules, relationships) |
| `lookups.md` | Reference tables + their seeded default values |
| `conventions.md` | Global rules applied to all tables |
| `ERD.md` | Entity-relationship diagram (Mermaid) |
| `schema.dbml` | DBML snapshot — source for rendering the ERD (dbdocs.io) |
| `glossary.md` | Domain terminology |
| `../decisions/*.md` | Architecture Decision Records |

## Canonical sources

- **Schema:** `database/migrations/2026_07_01_*` — the executable truth.
- **Seed data / defaults:** `database/seeders/DefaultLookupsSeeder.php` and its callees.

To regenerate a machine-readable schema from the live DB:

```bash
php artisan schema:dump        # writes database/schema/mysql-schema.sql
```

## Scope

Covers the shelter module only (organizations, animals, health, adoptions,
fostering, donations/expenses, and their lookups). The inherited
financial-system tables (users, roles, menus, suppliers) are **not**
documented here; `users` and `suppliers` are referenced only where the shelter
module extends them with `organization_id`.
