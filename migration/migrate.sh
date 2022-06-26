#! /bin/bash

# Usage: migrate.sh /path/to/data-file [postgresql-connection-string]
#
# Operation:
# - Reads a data file (xlsx, csv, etc.) into a SQLite database
# - Runs a local query file with same filename as data file against the SQLite database
# - Optionally load the SQLite tables into the specified PostgreSQL database
#
# Dependencies:
# - SQLite (https://www.sqlite.org/)
# - dsq for data conversion (https://github.com/multiprocessio/dsq)
# - pgloader for data loading (https://pgloader.io/)

set -e
dsq -C "$1" "select * from {}" > /dev/null
database=$(dsq -D "$1" "select * from {}")
filename=$(basename -- "$1")
cp "$database" "${filename%.*}.db"
sqlite3 "${filename%.*}.db" < "${filename%.*}.sql"
if [ -n "$2" ]; then
    pgloader "${filename%.*}.db" "$2"
fi
