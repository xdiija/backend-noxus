# Entity-Relationship Diagram

Rendered from the relationships in the migrations. `schema.dbml` is the source
for a richer render on [dbdocs.io](https://dbdocs.io). Lookup tables are grouped
at the bottom to keep the diagram legible.

```mermaid
erDiagram
    organizations ||--o{ users : "scopes"
    organizations ||--o{ animals : owns
    organizations ||--o{ adopters : owns
    organizations ||--o{ donors : owns
    organizations ||--o{ volunteers : owns
    organizations ||--o{ foster_homes : owns

    species ||--o{ breeds : has
    species ||--o{ animals : classifies
    breeds  ||--o{ animals : classifies

    animals ||--|| rescues : "intake (1:1)"
    animals ||--o{ animal_images : has
    animals ||--o{ animal_status_history : logs
    animals }o--o{ tags : "via animal_tags"
    animals ||--o{ health_records : has
    animals ||--o{ animal_vaccines : has
    animals ||--o{ animal_medications : has
    animals ||--o{ appointments : has
    animals ||--o{ surgeries : has
    animals ||--o{ adoptions : "adopted via"
    animals ||--o{ animal_foster_homes : "placed via"

    vaccines    ||--o{ animal_vaccines : referenced
    medications ||--o{ animal_medications : referenced

    adopters ||--o{ adoptions : makes
    adoptions ||--o{ adoption_follow_ups : "checked via"

    foster_homes ||--o{ animal_foster_homes : hosts

    donors ||--o{ donations : makes
    organizations ||--o{ donations : records
    organizations ||--o{ expenses : records

    organizations ||--o{ documents : owns
    organizations ||--o{ audit_logs : records
    users ||--o{ audit_logs : acts
```

## Lookups (configurable per organization; `NULL` org = system default)

`animal_statuses`, `animal_sizes`, `adoption_statuses`,
`adoption_follow_up_statuses`, `donation_types`, `expense_categories`, `tags`.

## Global reference (shared, not org-scoped)

`species`, `breeds`, `vaccines`, `medications`.
