SSoT Data Migration
===================

This folder contains a simple migration system from Excel / CSV files to a PostgreSQL database. The idea is to convert Excel sheets to CSV, then apply a series of transformations to the CSV, then import the resulting CSV into PostgreSQL via a loading tool.

As a prerequisite, you need to place the LMO data sheets in the local `data/` folder. If you are copying over the data sheets from the legacy WorkBC Kentico project, these would be located in the `src/SSIS` subfolder of that project.
```
find /path/to/WorkBC/src/SSIS -type f -ipath '*Delivered*' -not -iname '*.ivt' -not -iname '*.txt' -exec cp {} migration/data \;
for f in migration/data/*.zip; do unzip -o "$f" -d migration/data; done
```

Then run the full migration script to full re-populate the `ssot` database:
```
docker-compose exec migrator migrate.sh
```

## Migrating a new data source or updating an existing one
The steps for the migration are as follows:

- Enter the `migrator` container.
```
docker-compose exec migrator bash
```

- Search the script `migrate.sh` for the dataset you are trying to import. If it's mentioned in there already, you're in luck: you only need to replicate the commands, taking care to adjust the source sheet to the newly-received one. Please update the script file accordingly.

- Convert the Excel sheet `Data_File.xlsx` to CSV. Since multiple sheets can exist in an Excel file, we define an output template `Data_File-%s.csv` that `ssconvert` uses to generate each CSV separately with the sheet name appended to the base filename.
```
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/Data_File.xlsx" "data/Data_File-%s.csv"
```

- Apply a number of PHP transformations to the CSV as needed by the nature of the input data.

| PHP script | Purpose |
|------------|---------|
| `csv_empty.php` | Remove empty rows. |
| `csv_extract.php` | Extract rows based on given ranges. |
| `csv_refill.php` | Fill empty cells with previous values from the same column. |

Refer to the source code of these scripts for usage details. Ensure that the final CSV is stored in the `load/` folder and is named after the target database table.

Typically, the pipeline would like something like the following:
```
cat "data/Data_File-Sheet_Name.csv" | php csv_extract.php --range 5-504 > "load/target_table.csv"
```

- Write a data loading script to transform the final CSV file into PostgreSQL data. Follow the [`pgloader` documentation](https://pgloader.readthedocs.io/en/latest/tutorial/tutorial.html#loading-csv-data-with-pgloader) and refer to examples in this present folder. Please follow the following conventions when writing a data loading script:
  - The loading script filename is named after the target database table, just like the final CSV that it will load.
  - The column names don't exceed 64 characters to avoid truncation.
  - The column names are free of dates and date ranges, and instead refer to period increments, e.g. `current`, `first5y`, `next10y`, etc.
  - The columns include SQL comments that are copies of the spreadsheet column headers.
  - When dates and date ranges are present in the column's comment, they are included within curly braces e.g. `{2022}` to allow parsing by the client.
  - Region names are transformed into keys for data normalization purposes.

- Run the loading script and examine the output to ensure there are no errors. Note that the script expects the environment variables `PGUSER`, `PGPASSWORD`, `PGDATABASE`, and `PGHOST` to be set for the PostgreSQL database. These variables are already set in the `docker-compose.yml` file.
```
pgloader -l workbc.lisp load/data_table.load
```
WARNING! The environment variable `PGDATABASE` is currently not used by `pgloader`, so ensure that the connection string includes the database name (`ssot`) in each `.load` file.

- Add or modify the corresponding entry to the `load/sources.csv` metadata table which is described below, then re-run the `source.load` loading script.

- Export the full SSoT database by running `docker-compose exec -T postgres pg_dump --clean --username workbc ssot | gzip > ssot-full.sql.gz` OUTSIDE the container.

## Migrating monthly labour updates
These updates have a slightly different workflow to accommodate their specific structure:

- Convert the supplied Excel sheet to CSV.
```
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/Update_File.xlsx" "data/Update_File-%s.csv"
```

- Transform the CSV for loading, supplying the month and year that corresponds to the sheet.
```
cat "data/Update_File-Sheet_Name.csv" | php csv_empty.php | php monthly_labour_market_update_v3.php year{YYYY} month{1..12} > "load/updates/monthly_labour_market_updates_{YYYY}_{MM}.csv"
```
NOTE! For months earlier than March 2023, use the script `monthly_labour_market_update_v1.php`.
For months March 2023 through August 2023, use the script `monthly_labour_market_update_v2.php`.

- Run the loading script `load/monthly_labour_market_updates.load`, supplying the transformed CSV above as the `SOURCE` environment variable.
```
SOURCE="/app/load/updates/monthly_labour_market_updates_{YYYY}_{MM}.csv" pgloader -l workbc.lisp load/updates/monthly_labour_market_updates.load
```
WARNING! The monthly labour market update loading script does not offer the capability of updating an existing record - you would have to manually delete the record for a given month if you want to update it. For this, use a SQL instruction like:
```
psql -c 'DELETE FROM monthly_labour_market_updates WHERE year = YYYY AND month = MM;'
```
## Sources metadata
The `load/sources.csv` file contains provenance metadata for all the migrated data sources, including a source label that can be displayed to end-users. It is meant to be manually edited.

The level of granularity of the metadata is the "Data point", which represents a single field or a section of the dataset. If the data point value is `NULL`, then the provenance covers all data points, except for those that may be specifically mentioned in other records of this table.

When you update the sources file, please make sure to update the following columns (when applicable):
- **Source filename** to reflect the latest datasheet corresponding to the given dataset
- **Date** of datasheet production (if known) or delivery

You can then refresh the sources metadata table using the following line:
```
pgloader -l workbc.lisp load/sources.load
```
