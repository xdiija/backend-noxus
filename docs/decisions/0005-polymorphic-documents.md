# ADR-0005: Polymorphic document attachments

**Status:** Accepted

## Context
The original `documents` table had nullable `animal_id` and `adopter_id` columns.
Every new attachable entity (adoption contract, volunteer agreement, org
document) would add another nullable FK column — a widening, sparse table.

## Decision
Use Laravel's polymorphic relation: `documents` has `documentable_type` +
`documentable_id` (`nullableMorphs('documentable')`) instead of per-entity FK
columns. `organization_id` is still present for tenant scoping. Both morph
columns null = an org-level document not tied to a specific record.

## Consequences
- Any model can own documents with no schema change — just implement the
  `morphMany` side.
- No referential integrity at the DB level for the polymorphic link (Laravel
  can't FK a polymorphic column). Deletions of owners don't cascade to
  documents automatically — handle in the app (model events) if needed.
- `(documentable_type, documentable_id)` is indexed by `nullableMorphs` for fast
  "documents for record X" lookups.
