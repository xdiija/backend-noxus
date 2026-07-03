# ADR-0003: Rescue is a 1:1 intake event on the animal

**Status:** Accepted

## Context
The original schema stored `rescue_date` on `animals` *and* had a separate
`rescues` table with `rescue_date`, `location`, `rescued_by`, `condition`,
`description` — duplicating the same fact in two places.

## Decision
Keep all intake data in `rescues` and remove `rescue_date` from `animals`.
Enforce one rescue per animal with a `UNIQUE` constraint on `rescues.animal_id`
(1:1). `rescues.animal_id` cascades on delete.

## Consequences
- Single source of truth for intake; no risk of the two dates diverging.
- Intake details (`location`, `condition`, …) live in one obvious place.
- Assumes an animal has exactly one intake event. If the domain later needs
  re-intake after release (animal leaves care and returns as a new rescue),
  drop the unique constraint to make it 1:N and revisit this ADR.
