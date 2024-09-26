SSoT
====

This is the WorkBC Single Source of Truth (SSoT) API service.

[![Lifecycle:Experimental](https://img.shields.io/badge/Lifecycle-Experimental-339999)](https://github.com/bcgov/workbc-ssot)

## Architecture
- [PostgREST](https://postgrest.org/en/stable/) provides an automatically-generated CRUD API on top of a PostgreSQL database
- An [OpenAPI / Swagger 2.0](https://swagger.io/resources/open-api/) specification and Web interface are also provided
- Access to the API is read-only: any HTTP verb other than `GET` will fail with a permission error

## Development
- Start the containers: `docker-compose build && docker-compose up`
- Open http://localhost:8080 to access the OpenAPI / Swagger Web interface
- Invoke PostgREST API directly via http://localhost:3000
- Open the PostgreSQL `ssot` database directly via `postgresql://workbc:workbc@localhost/ssot`
- Backup:
```bash
docker-compose exec -T postgres psql --username workbc ssot < ssot-grants.sql \
&& docker-compose exec -T postgres pg_dump --clean --username workbc ssot | gzip > ssot-full.sql.gz \
&& gunzip -k -c ssot-full.sql.gz > ssot-full.sql
```
- Restore:
```bash
docker-compose exec -T postgres psql --username workbc ssot < ssot-reset.sql \
&& gunzip -k -c ssot-full.sql.gz | docker-compose exec -T postgres psql --username workbc ssot \
&& docker-compose kill -s SIGUSR1 ssot
```

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

## Triggering a data sheet rebuild
Using [GitHub Repository Dispatch API call](https://docs.github.com/en/rest/repos/repos?apiVersion=2022-11-28#create-a-repository-dispatch-event), a data sheet rebuild can be triggered. At the moment, only dataset `monthly_labour_market_update` is supported.

The following steps are carried out by the rebuild:
- Checkout the target branch of this repo
- Access the given data sheet located in `migration/data/`
- Convert the data sheet into the appropriate dataset CSV at `migration/load/`
- Update the corresponding entry in `migration/load/sources.csv` to reflect the change
- Spin up a running version of the SSOT database using `docker-compose`
- Ingest the 2 CSV files (dataset + `sources`) into the running database
- Export a snapshot of the running database after update into `ssot-full.sql` and `ssot-full.sql.gz`
- Commit and push the changed files to the GitHub repo

```bash
curl -L \
  -X POST \
  -H "Accept: application/vnd.github+json" \
  -H "Authorization: Bearer <YOUR-TOKEN>" \
  -H "X-GitHub-Api-Version: 2022-11-28" \
  https://api.github.com/repos/OWNER/REPO/dispatches \
  -d '{"event_type":"monthly_labour_market_update","client_payload":{
    "branch": "Branch where rebuild should happen",
    "filename": "Filename of the existing data sheet in migration/data",
    "year": "Numeric value of the period year of the target dataset",
    "month": "Numeric value of the period month of the target dataset",
    "date": "Full date of production/reception of the data sheet in YYYY/MM/DD hh:mm format (UTC timezone)",
    "notes": "Commit message to update files into GitHub"
  }}'
```
