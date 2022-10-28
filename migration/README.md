SSoT Data Migration
===================

This folder contains a simple migration system from Excel / CSV files to a PostgreSQL database. The idea is to convert Excel sheets to CSV, then import the CSV into PostgreSQL via a loading tool that applies some transformations to the data. The steps for this migration are:

- On your host machine, copy the original data files to the local `data/` folder.
```
find /path/to/WorkBC/src/SSIS -type f -ipath '*Delivered*' -not -iname '*.ivt' -not -iname '*.txt' -exec cp {} migration/data \;
for f in migration/data/*.zip; do unzip -o "$f" -d migration/data; done
```

- Enter the `migrator` container to perform the remaining steps.
```
docker-compose exec migrator bash
```

- Convert the Excel sheet to CSV. Since multiple sheets can exist in an Excel file, we define an output template `Data_File-%s.csv` that `ssconvert` uses to generate each CSV separately with the sheet name appended to the base filename.
```
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/Data_File.xlsx" "data/Data_File-%s.csv"
```

- In some cases, there are multiple tables per sheet. You need to extract each table to its own file for further processing.
```
php csv_split.php --range 1-20 --output "data/Data_File-Sheet_Name-Table1_Name.csv" --range 23-60 --output "data/Data_File-Sheet_Name-Table1_Name.csv" < "data/Data_File-Sheet_Name.csv"
```

- Make sure that your CSV headers do not contain newlines in order for the data transformation to succeed.
```
php csv_header.php < "data/Data_File-Sheet_Name.csv" > "data/Data_file-Sheet_Name-Transformed.csv"
```

- Write a data loading script to transform the CSV file into PostgreSQL data. Follow the [`pgloader` documentation](https://pgloader.readthedocs.io/en/latest/tutorial/tutorial.html#loading-csv-data-with-pgloader) and refer to examples in this present folder. Please follow the following conventions when writing a data loading script:
  - The script filename is named after the database table that will receive the data
  - The column names don't exceed 64 characters to avoid truncation
  - The column names are free of dates and date ranges, and instead refer to period increments, e.g. `current`, `first5y`, `next10y`, etc.
  - The columns include SQL comments that are copies of the spreadsheet column headers
  - When dates and date ranges are present in the column's comment, they are included within curly braces e.g. `{2022}` to allow parsing by the client

- Move the final `Data_file-Sheet_Name-Transformed.csv` file to `load/data_table.csv`.

- Run the loading script and examine the output to ensure there are no errors. Note that the script expects the environment variables `PGUSER`, `PGPASSWORD`, `PGDATABASE`, and `PGHOST` to be set for the PostgreSQL database. These variables are already set in the `docker-compose.yml` file.
```
pgloader load/data_table.load
```
WARNING! The environment variable `PGDATABASE` is currently not used by `pgloader`, so ensure that the connection string includes the database name (`ssot`) in each `.load` file.

## Monthly Labour Updates
These updates have a slightly different workflow to accommodate their specific structure:

- Convert the supplied Excel sheet to CSV.
```
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/Update_File.xlsx" "data/Update_File-%s.csv"
```

- Transform the CSV to another CSV that is suitable for loading, supplying the month and year that correspoknds to the sheet.
```
php monthly_labour_market_update.php year{YYYY} month{1..12} < "data/Update_File-Sheet_Name.csv" > "load/updates/monthly_labour_market_updates_{YYYY}_{MM}.csv"
```

- Run the existing loading script `load/monthly_labour_market_updates.load`, supplying the transformed CSV above as the `SOURCE` environment variable.
```
SOURCE="/app/load/updates/monthly_labour_market_updates_{YYYY}_{MM}.csv" pgloader load/monthly_labour_market_updates.load
```
## Data Sources
The `load/sources.csv` file contains provenance metadata for all the migrated data sources, including a source label that can be displayed to end-users. The level of granularity of the metadata is the "Data point", which represents a field or a section of the dataset. If the value is `NULL`, then the provenance covers all data points, except for those that may be specifically mentioned in other records of this table.

By examining this metadata, you can determine which source spreadsheets/tabs/ranges are needed to recreate the full dataset.