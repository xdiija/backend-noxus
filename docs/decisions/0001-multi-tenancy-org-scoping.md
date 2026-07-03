# ADR-0001: Multi-tenancy via organization scoping

**Status:** Accepted

## Context
The system serves many independent shelters/NGOs from one database. Data must be
isolated per organization.

## Decision
Use a single shared database with a per-row `organization_id` on every
tenant-owned table (shared-schema multi-tenancy). Every query is scoped by
`organization_id`, enforced in the application layer (global query scope +
policies). Users carry `organization_id` to determine their tenant.

Lookup tables are the exception: `organization_id` is nullable, where `NULL`
means a system default shared by all tenants (see ADR-0004).

## Consequences
- Simple ops (one DB, one migration set); cheap to add tenants.
- Isolation depends on discipline — a missing scope leaks data across tenants.
  Mitigate with a global scope/base model and tests.
- Cross-tenant reporting is possible but must be deliberate and guarded.
- Not suitable if a tenant ever needs physical data isolation (compliance); that
  would require DB-per-tenant, a much larger change.
