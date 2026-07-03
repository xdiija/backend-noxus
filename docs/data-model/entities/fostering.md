---
domain: fostering
tables: [foster_homes, animal_foster_homes, volunteers]
tenant_scoped: true
soft_deletes: partial
---

# Fostering & People

Temporary placements and the volunteers who run them.

## `volunteers`

People who help the organization. Not the same as `users` — a volunteer need
not have a login.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| id | bigint PK | | |
| organization_id | FK → organizations | no | |
| name | varchar | no | |
| phone | varchar | yes | |
| email | varchar | yes | |
| role | varchar | yes | Free-text role/function. |
| status | boolean | no (def true) | Active flag (project convention: `status`). |
| timestamps, deleted_at | | | Soft-deleted. |

## `foster_homes`

A temporary home that can host animals.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| id | bigint PK | | |
| organization_id | FK → organizations | no | |
| responsible_name | varchar | no | |
| phone | varchar | yes | |
| address | varchar | yes | |
| capacity | int | yes | Max animals it can host. |
| timestamps, deleted_at | | | Soft-deleted. |

## `animal_foster_homes`

An animal's stay in a foster home (a placement period).

| Column | Type | Null | Notes |
|--------|------|------|-------|
| id | bigint PK | | |
| animal_id | FK → animals | no | Cascade on delete. |
| foster_home_id | FK → foster_homes | no | Cascade on delete. |
| entry_date | date | no | |
| exit_date | date | yes | Null = currently fostered here. |
| timestamps | | | No soft delete. |

### Rules & invariants
- An open stay is one with `exit_date IS NULL`. An animal should have at most
  one open foster stay at a time — application-enforced.
- `capacity` is advisory; the DB does not enforce it. Check active stays
  (`exit_date IS NULL`) against `capacity` in the app if you want to block
  over-capacity placements.
