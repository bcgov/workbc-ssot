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
- Open http://localhost:8080 to acccess the OpenAPI / Swagger Web interface
- Invoke PostgREST API directly via http://localhost:3000
- Open the PostgreSQL `ssot` database directly via `postgresql://ssot:ssot@localhost/ssot`
- Backup: `docker-compose exec -T db pg_dump --username ssot ssot > ssot.sql`
- Restore: `docker-compose exec -T db psql --username ssot ssot < ssot.sql`

## Migration
The folder `migration` contains a simple migration system from Excel / CSV files to a PostgreSQL database. The idea is to read a spreadsheet file into a local SQLite database, then apply a number of transformations to the tables, then load the database into a PostgreSQL database. Here's how it works:

- For a spreadsheet file `Example_WorkBC_Sheet.xlsx`, create a local file `Example_WorkBC_Sheet.sql` that contains SQL transformations on the data that will be imported from the sheet.
- Run `migrate.sh Example_WorkBC_Sheet.xlsx` to import the sheet and apply the SQL transformations - the output is `Example_WorkBC_Sheet.db` which is a SQLite database file.
- When you are satisfied with the data inside the SQLite database, run `migrate.sh Example_WorkBC_Sheet.xlsx pgsql://ssot:ssot@localhost/ssot` to import the data into a PostgreSQL database, where the same tables will be recreated.

The dependencies for this migration system are:

- SQLite (https://www.sqlite.org/)
- dsq for data conversion (https://github.com/multiprocessio/dsq)
- pgloader for data loading (https://pgloader.io/)
