---
domain: health
tables: [health_records, animal_vaccines, animal_medications, appointments, surgeries]
tenant_scoped: true
soft_deletes: partial
---

# Health

Clinical history for an animal. `vaccines` and `medications` are global catalogs
(see [lookups](../lookups.md)); the tables here record what was actually
administered.

## `health_records`

Generic clinical event/note when no specialized table fits.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| id | bigint PK | | |
| organization_id | FK → organizations | no | |
| animal_id | FK → animals | no | Cascade on delete. |
| type | varchar | no | e.g. exam, deworming, observation. |
| description | text | yes | |
| event_date | date | no | |
| veterinarian | varchar | yes | Free text. |
| notes | text | yes | |
| timestamps, deleted_at | | | Soft-deleted. |

## `animal_vaccines`

A vaccine dose administered to an animal.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| id | bigint PK | | |
| animal_id | FK → animals | no | Cascade on delete. |
| vaccine_id | FK → vaccines | no | Restrict. |
| application_date | date | no | |
| dose | varchar | yes | e.g. "1ª dose", "reforço". |
| timestamps | | | No soft delete (pivot-like log). |

## `animal_medications`

A medication course for an animal.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| id | bigint PK | | |
| animal_id | FK → animals | no | Cascade on delete. |
| medication_id | FK → medications | no | Restrict. |
| dosage | varchar | yes | |
| start_date | date | no | |
| end_date | date | yes | Null = ongoing. |
| veterinarian | varchar | yes | |
| notes | text | yes | |
| timestamps | | | No soft delete. |

## `appointments`

Scheduled/attended vet visits.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| id | bigint PK | | |
| organization_id | FK → organizations | no | |
| animal_id | FK → animals | no | Cascade on delete. |
| appointment_date | datetime | no | |
| veterinarian | varchar | yes | |
| clinic | varchar | yes | |
| purpose | varchar | yes | |
| result | text | yes | Outcome after the visit. |
| next_visit | date | yes | Suggested follow-up date. |
| timestamps, deleted_at | | | Soft-deleted. |

## `surgeries`

Surgical procedures, including castration.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| id | bigint PK | | |
| organization_id | FK → organizations | no | |
| animal_id | FK → animals | no | Cascade on delete. |
| surgery_date | date | no | |
| procedure | varchar | no | |
| veterinarian | varchar | yes | |
| clinic | varchar | yes | |
| cost | decimal(10,2) | yes | |
| notes | text | yes | |
| timestamps, deleted_at | | | Soft-deleted. |

### Rules & invariants
- A castration surgery is the clinical record; the animal-level `neutered` flag
  is the queryable summary. Keep them consistent (set `animals.neutered = true`
  and `neuter_date` when recording a castration). This consistency is
  application-enforced, not a DB constraint.
