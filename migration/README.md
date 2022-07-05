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
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet data/Data_File.xlsx "data/Data_File-%s.csv"
```

- Make sure that your CSV headers do not contain newlines in order for the data transformation to succeed.
```
php csv_header.php < "data/Data_File-Sheet_Name.csv"  > "data/Data_file-Sheet_Name-Fixed.csv"
```

- Write a data loading script to transform the CSV file into PostgreSQL data. Follow the [`pgloader` documentation](https://pgloader.readthedocs.io/en/latest/tutorial/tutorial.html#loading-csv-data-with-pgloader) and refer to examples in this present folder. Name the script `Data_File.load` to correspond to the source `Data_File.xlsx`.

- Run the transformation and examine the output to ensure there are no errors.
```
pgloader Data_File.load
```
