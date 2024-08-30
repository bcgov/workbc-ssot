SSoT
====

This is the WorkBC Single Source of Truth (SSoT) API service.

[![Lifecycle:Experimental](https://img.shields.io/badge/Lifecycle-Experimental-339999)](https://github.com/bcgov/workbc-ssot)

## Architecture
- [PostgREST](https://postgrest.org/en/stable/) provides an automatically-generated CRUD API on top of a PostgreSQL database
- An [OpenAPI / Swagger 2.0](https://swagger.io/resources/open-api/) specification and Web interface are also provided
- Access to the API is read-only: any HTTP verb other than `GET` will fail with a permission error

## Development
- `docker-compose build && docker-compose up`
- Open http://localhost:8080 to access the OpenAPI / Swagger Web interface
- Invoke PostgREST API directly via http://localhost:3000
- Open the PostgreSQL `ssot` database directly via `postgresql://workbc:workbc@localhost/ssot`
- Backup: `docker-compose exec -T postgres pg_dump --no-privileges --clean --username workbc ssot | gzip > ssot-full.sql.gz`
- Restore: `docker-compose exec -T postgres psql --username workbc ssot < ssot-reset.sql && gunzip -k -c ssot-full.sql.gz | docker-compose exec -T postgres psql --username workbc ssot && docker-compose kill -s SIGUSR1 ssot`

## Data ingestion
Please refer to [migration/README.md](migration#readme).

## Updating SSoT for WorkBC
- Update the dataset as described in the "Data ingestion" section.
- Export the full dataset:
```
docker-compose exec -T postgres pg_dump --clean --username workbc ssot | gzip > ssot-full.sql.gz
```
- Open the Restore page on the desired WorkBC Drupal stage `/admin/config/development/backup_migrate/restore` then select **Restore To > SSoT Database** and upload the file `ssot-full.sql.gz`.
- Repeat the procedure above with the file `ssot-refresh.sql` which is included in this repo.
