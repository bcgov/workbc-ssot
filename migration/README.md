SSoT Data Ingestion
===================

This folder contains a simple migration system from Excel / CSV files to a PostgreSQL database. The idea is to convert Excel sheets to CSV, then apply a series of transformations to the CSV, then import the resulting CSV into PostgreSQL via a loading tool.

## Building SSoT from scratch
Run the `ssot-full` script to rebuild the full database:
```
docker-compose exec -T postgres psql --username workbc ssot < ssot-reset.sql
docker-compose exec migrator ssot-full.sh
```

## Ingesting a new data source or updating an existing one
The steps for the ingestion are as follows:

- Place the new source sheet in the `data/` folder, e.g. `data/Data_File.xlsx`.

- Enter the `migrator` container:
```
docker-compose exec migrator bash
```

- Search the script `ssot-full.sh` for the dataset you are trying to import. If it's mentioned in there already, you're in luck: you only need to replicate the commands, taking care to adjust the source sheet to the newly-received one. Please update the script file accordingly.

- Convert the Excel sheet `data/Data_File.xlsx` to CSV. Since multiple sheets can exist in an Excel file, we define an output template `data/Data_File-%s.csv` that `ssconvert` uses to generate each CSV separately with the sheet name appended to the base filename.
```
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/Data_File.xlsx" "data/Data_File-%s.csv"
```

- Apply a number of PHP transformations to the CSV as needed by the nature of the input data.

| PHP script | Purpose |
|------------|---------|
| `csv_empty.php` | Remove empty rows from CSV. |
| `csv_extract.php` | Extract CSV rows based on given ranges. |
| `csv_refill.php` | Fill empty column cells with previous values in a CSV. |
| `csv_pad.php` | Pad CSV columns. |

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

- Export the full SSoT database by running the following OUTSIDE the container:
```bash
docker-compose exec -T postgres psql --username workbc ssot < ssot-grants.sql \
&& docker-compose exec -T postgres pg_dump --clean --username workbc ssot | gzip > ssot-full.sql.gz \
&& gunzip -k -c ssot-full.sql.gz > ssot-full.sql
```

## Ingesting monthly labour market updates
These updates have a specialized script to simplify the process and allow it to be called from outside the container.

- Place the new source sheet in the `data/` folder, e.g. `data/Monthly_File.xlsx`.

- Run the specialized script:
```
./monthly_labour_market_updates.sh Monthly_File.xlsx "Year as YYYY" "Month as M or MM" "Updated date as YYYY/MM/DD HH:MM"
```

- Export the full SSoT database by running the following OUTSIDE the container:
```bash
docker-compose exec -T postgres psql --username workbc ssot < ssot-grants.sql \
&& docker-compose exec -T postgres pg_dump --clean --username workbc ssot | gzip > ssot-full.sql.gz \
&& gunzip -k -c ssot-full.sql.gz > ssot-full.sql
```

## Sources metadata
The `load/sources.csv` file contains provenance metadata for all the migrated data sheets, including a source label that can be displayed to end-users. It is meant to be manually edited.

The level of granularity of the metadata is the "Data point", which represents a single field or a section of the dataset. If the data point value is `NULL`, then the provenance covers all data points, except for those that may be specifically mentioned in other records of this table.

When you update the sources file, please make sure to update the following columns (when applicable):
- **filename** to reflect the latest data sheet corresponding to the given dataset
- **period** to reflect the date at which the given data sheet is valid (e.g. Jan 1st, 2024)
- **date** of datasheet production or delivery

You can then refresh the sources metadata table using the following line:
```
pgloader -l workbc.lisp load/sources.load
```
