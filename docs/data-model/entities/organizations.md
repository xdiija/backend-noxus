---
domain: core
tables: [organizations]
tenant_scoped: false
soft_deletes: true
---

# Organizations

The tenant root. Every other shelter table hangs off an organization. See
[conventions](../conventions.md) for the scoping rules and ADR-0001 for the
tenancy model.

## `organizations`

The shelter / NGO itself.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| id | bigint PK | | |
| name | varchar | no | Display name. |
| legal_name | varchar | yes | Registered legal name (razão social). |
| tax_id | varchar | yes | CNPJ. **Unique** across the table. |
| email | varchar | yes | |
| phone | varchar | yes | |
| website | varchar | yes | |
| address | varchar | yes | |
| city | varchar | yes | |
| state | varchar | yes | |
| timestamps, deleted_at | | | Soft-deleted. |

### Relationships
- Has many of nearly everything (animals, adopters, donors, volunteers,
  foster_homes, donations, expenses, documents, audit_logs) via
  `organization_id`.
- Optionally owns custom lookup rows (see [lookups](../lookups.md)).

### Rules & invariants
- `tax_id` is globally unique but **nullable** — an org can be created before
  its CNPJ is known.
- Deleting an organization cascades to its owned rows at the DB level. Prefer
  soft-deleting instead; hard delete is destructive and irreversible for tenant
  data.

## `users` (extension only)

`users` is inherited from the base financial system. The shelter module adds a
single nullable column:

| Column | Type | Null | Notes |
|--------|------|------|-------|
| organization_id | bigint FK → organizations | yes | Scopes a user to one org. Nullable so existing rows survive the migration; new users should always be assigned. |

Full `users` table is out of scope for these docs — see the base project's
migrations.
