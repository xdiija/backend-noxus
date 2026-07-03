---
domain: system
tables: [documents, audit_logs]
tenant_scoped: true
soft_deletes: false
---

# System

Cross-cutting tables: file attachments and the audit trail.

## `documents`

Polymorphic file attachments (ADR-0005). Any entity can own documents without a
dedicated FK column.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| id | bigint PK | | |
| organization_id | FK → organizations | no | |
| documentable_type | varchar | yes | Owner model class (Laravel morph). |
| documentable_id | bigint | yes | Owner id. |
| path | varchar | no | Storage path/URL. |
| type | varchar | yes | Document category (contract, RG, exam…). |
| timestamps | | | No soft delete. |

### Rules & invariants
- `documentable_type` + `documentable_id` are set together (Laravel
  `nullableMorphs`). Both null = an org-level document not tied to a record.
- Typical owners: `App\Models\Animal`, `App\Models\Adopter`. Add new morphable
  models freely — no migration needed.

## `audit_logs`

Append-only activity trail.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| id | bigint PK | | |
| organization_id | FK → organizations | no | |
| user_id | FK → users | yes | Actor; null on delete. |
| action | varchar | no | e.g. created, updated, deleted. |
| entity | varchar | no | Affected model name. |
| entity_id | bigint | yes | Affected row id. Indexed with `entity`. |
| timestamps | | | `created_at` = event time. Append-only. |

### Rules & invariants
- Write-once. Never update or delete audit rows.
- Index `(entity, entity_id)` supports "history for record X" queries.
- Not a foreign key to the target row (target may be soft-deleted or gone);
  `entity`/`entity_id` are a loose reference by design.
