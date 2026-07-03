---
domain: lookups
tenant_scoped: mixed
---

# Lookups & Reference Data

Two kinds of reference tables:

- **Global reference** — shared, not org-scoped: `species`, `breeds`,
  `vaccines`, `medications`.
- **Configurable lookups** — org-customizable with `organization_id` nullable
  (`NULL` = system default, shared by all orgs; non-null = one org's custom
  entry). See ADR-0004.

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

## Global reference

### `species`
Columns: `name` (unique). Defaults: Cachorro, Gato.

### `breeds`
Columns: `species_id`, `name`, unique `(species_id, name)`.
Defaults: common BR breeds per species; `SRD (Vira-lata)` = mixed breed.

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
