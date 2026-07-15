---
domain: lookups
tenant_scoped: mixed
---

# Lookups & Reference Data

Two kinds of reference tables:

- **Global reference** — shared, not org-scoped: `species`, `vaccines`,
  `medications`.
- **Configurable lookups** — `organization_id` nullable (`NULL` = shared global
  entry). See ADR-0004.
  - **Noxus-managed global** (`breeds`, `animal_sizes`, `animal_statuses`,
    `tags`): a single curated list per lookup; only Noxus users create/edit/
    delete, all rows are `NULL`-org, every tenant reads the same list
    ([ADR-0008](../decisions/0008-noxus-managed-global-lookups.md)).
  - The workflow/finance lookups (`adoption_statuses`,
    `adoption_follow_up_statuses`, `kennel_statuses`, `donation_types`,
    `expense_categories`) keep the ADR-0004 per-org model: exposed as
    read-only list endpoints returning the system defaults plus the org's own
    rows (no management CRUD yet).

Seeded by `DefaultLookupsSeeder` (idempotent). Defaults below are Portuguese
(user-facing); table/column names stay English.

## Configurable lookups

### `animal_statuses`
Animal lifecycle. Columns: `organization_id?`, `name`, `color`, `sort_order`,
`is_default`, soft deletes.

| name | default |
|------|---------|
| Em Tratamento | ✅ is_default |
| Disponível | |
| Em Lar Temporário | |
| Reservado | |
| Adotado | |
| Devolvido | |
| Óbito | |

### `animal_sizes`
Columns: `organization_id?`, `name`, `sort_order`.
Defaults: Pequeno, Médio, Grande, Gigante.

### `adoption_statuses`
Adoption lifecycle. Columns: `organization_id?`, `name`, `color`, `sort_order`,
`is_default`.

| name | default |
|------|---------|
| Pendente | ✅ is_default |
| Concluída | |
| Devolvida | |
| Cancelada | |

### `adoption_follow_up_statuses`
Columns: `organization_id?`, `name`, `color`.
Defaults: Agendado, Contatado, Sem Resposta, Alerta, Encerrado.

### `kennel_statuses`
Kennel operational state (not occupancy — that is derived from open stays, see
`entities/kennels.md`). Columns: `organization_id?`, `name`, `color`,
`sort_order`, `is_default`.

| name | default |
|------|---------|
| Disponível | ✅ is_default |
| Em Manutenção | |
| Isolamento | |

### `donation_types`
Columns: `organization_id?`, `name`.
Defaults: Dinheiro, Ração / Alimentos, Medicamentos, Suprimentos, Serviços.

### `expense_categories`
Columns: `organization_id?`, `name`.
Defaults: Veterinário, Alimentação, Medicamentos, Suprimentos, Transporte,
Contas / Utilidades, Outros.

### `tags`
Free-form animal labels. Columns: `organization_id?`, `name`.
Defaults: Necessidades Especiais, Idoso, Dupla Inseparável, Sociável com
Crianças, Sociável com Outros Animais, Resgate Recente.

### `breeds`
Columns: `organization_id?`, `species_id`, `name`, unique
`(organization_id, species_id, name)`. Org-customizable (ADR-0004 amendment):
`NULL` org = seeded system default; a non-null value = one org's custom breed.
Defaults: common BR breeds per species; `SRD (Vira-lata)` = mixed breed.

## Global reference

### `species`
Columns: `name` (unique). Defaults: Cachorro, Gato.

### `vaccines`
Columns: `name` (unique).
Defaults: V8 (Óctupla), V10 (Déctupla), Giárdia, Gripe Canina, Tríplice Felina
(V3), Quádrupla Felina (V4), Quíntupla Felina (V5), Antirrábica.

### `medications`
Columns: `name`, `description`.
Defaults: Vermífugo, Antipulgas / Carrapaticida, Antibiótico, Anti-inflamatório,
Analgésico, Antiparasitário.

## Rules for consuming lookups

- Query a lookup as `WHERE organization_id IS NULL OR organization_id = :orgId`
  so each org sees the system defaults plus its own custom entries.
- `is_default` marks the row to preselect when creating a new record. Exactly
  one system default per configurable status table.
- Don't hard-code lookup IDs in code — resolve by `name`/`is_default`. IDs are
  environment-specific.
