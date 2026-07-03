# Architecture Decision Records

Non-obvious data-model decisions and the reasoning behind them. Read the
relevant ADR before changing an invariant — each exists to prevent a regression.

| ADR | Decision |
|-----|----------|
| [0001](0001-multi-tenancy-org-scoping.md) | Multi-tenancy via `organization_id` scoping |
| [0002](0002-adoptions-not-unique-per-animal.md) | Adoptions are not unique per animal (returns/re-adoption) |
| [0003](0003-rescue-one-to-one.md) | Rescue is a 1:1 intake event on the animal |
| [0004](0004-per-org-customizable-lookups.md) | Configurable lookups with nullable `organization_id` |
| [0005](0005-polymorphic-documents.md) | Polymorphic document attachments |
| [0006](0006-age-as-estimated-birth-date.md) | Store estimated birth date, not age |
| [0007](0007-simplified-finance-scope.md) | Simplified finance (donations & expenses) |
