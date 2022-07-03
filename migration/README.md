SSoT Data Migration
===================

This folder contains a simple migration system from Excel / CSV files to a PostgreSQL database. The idea is to convert Excel sheets to CSV, then import the CSV into PostgreSQL via a loading tool that applies some transformations to the data.

The dependencies for this migration system are:

- [`ssconvert`](https://help.gnome.org/users/gnumeric/stable/gnumeric.html#sect-files-ssconvert) for conversion from Excel to CSV
- [`pgloader`](https://pgloader.io/) for data loading into PostgreSQL

The steps for this migration are:

- Convert the Excel sheet to CSV. Since multiple sheets can exist in an Excel file, we define an output template `Excel_File-%s.csv` that `ssconvert` uses to generate each CSV seperately with the sheet name appended to the base filename.
```
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet /path/to/Data_File.xlsx "Data_File-%s.csv"
```
**WARNING!** You will need to ensure that your CSV headers do not contain newlines in order for the data transformation to succeed.

- Write a data loading script to transform the CSV into PostgreSQL data. Follow the [`pgloader` documentation](https://pgloader.readthedocs.io/en/latest/tutorial/tutorial.html#loading-csv-data-with-pgloader) and refer to examples in this present folder. Name the script `Data_File.load` to correspond to `Data_File.xlsx`.

- Run the transformation and examine the output to ensure there are no errors.
```
pgloader Data_File.load
```
