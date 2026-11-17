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
Using [GitHub Repository Dispatch API call](https://docs.github.com/en/rest/repos/repos?apiVersion=2022-11-28#create-a-repository-dispatch-event), a rebuild can be triggered for an uploaded XLSX workbook.
Commit the workbook before dispatching; send its Git blob SHA and dataset metadata
in the event. The workflow updates `migration/load/sources.csv` before ingestion.

The following steps are carried out by the rebuild:
- Checkout the target branch of this repo
- Spin up a running version of the SSOT database using `docker-compose` and restore the current snapshot
- Verify the uploaded workbook SHA and register its source metadata from the dispatch payload
- Run `php migration/src/ssot_ingest.php --file "Workbook.xlsx"` to convert and load all datasets registered for that workbook, followed by source metadata
- Preserve unrelated datasets and other monthly periods
- Export a snapshot of the running database after update into `ssot-full.sql` and `ssot-full.sql.gz`
- Commit metadata, generated CSVs and snapshots together; push without force

The dataset inventory and public provenance remain in
[`migration/load/sources.csv`](./migration/load/sources.csv). Conversion recipes
are maintained separately in [`migration/conversion.json`](./migration/conversion.json).
See the [ingestion guide](./migration/README.md) for the architecture and a
worked example of adding a new dataset without changing the driver.

```bash
curl -L \
  -X POST \
  -H "Accept: application/vnd.github+json" \
  -H "Authorization: Bearer <YOUR-TOKEN>" \
  -H "X-GitHub-Api-Version: 2022-11-28" \
  https://api.github.com/repos/OWNER/REPO/dispatches \
  -d '{"event_type":"ssot_update","client_payload":{
    "branch": "Branch where rebuild should happen",
    "filename": "Filename of the existing data sheet in migration/data",
    "workbook_sha": "Git blob SHA returned by the workbook upload",
    "year": 2026,
    "month": 1,
    "date": "2026/08/05 08:00",
    "notes": "Commit message to update files into GitHub",
    "metadata": {
      "label": "B.C. Labour Market Outlook",
      "author": null,
      "datasets": [
        {"endpoint": "industry_outlook", "sheet": "Sheet1", "range": "A3:H20", "match": "endpoint"}
      ]
    }
  }}'
```
