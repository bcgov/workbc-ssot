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

- Make sure that your CSV headers do not contain newlines in order for the data transformation to succeed.
```
php csv_header.php < "data/Data_File-Sheet_Name.csv"  > "data/Data_file-Sheet_Name-Transformed.csv"
```

- Write a data loading script to transform the CSV file into PostgreSQL data. Follow the [`pgloader` documentation](https://pgloader.readthedocs.io/en/latest/tutorial/tutorial.html#loading-csv-data-with-pgloader) and refer to examples in this present folder. The existing loader scripts are named after the database tables that will receive the data.

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
php monthly_labour_market_update year{YYYY} month{1..12} < "data/Update_File-Sheet_Name.csv" > "data/Update_file-Sheet_Name-Transformed.csv"
```

- Run the existing loading script `load/monthly_labour_market_updates.load`, supplying the transformed CSV above as the `SOURCE` environment variable.
```
SOURCE="data/Update_file-Sheet_Name-Transformed.csv" pgloader load/monthly_labour_market_updates.load
```
## Data Sources
The `load/sources.csv` file contains an inventory of all the migrated data sources, including the label that can be displayed to end-users.

By examining this inventory, you can determine which source spreadsheets are needed to recreate the full dataset.