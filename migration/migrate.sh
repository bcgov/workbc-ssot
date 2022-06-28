#! /bin/bash

# Usage: migrate.sh /path/to/data-file [pgsql://user:pass@host/db]
#
# Operation:
# - Reads a data file (xlsx, csv, etc.) into a SQLite database
# - Runs a local query file with same filename as data file against the SQLite database (with spaces converted to _underscores_)
# - Optionally load the SQLite tables into the specified PostgreSQL database if connection string is supplied
#
# Dependencies:
# - SQLite (https://www.sqlite.org/)
# - dsq for data conversion (https://github.com/multiprocessio/dsq)
# - pgloader for data loading (https://pgloader.io/)

set -e
dsq -C "$1" "select * from {}" > /dev/null
sqlite=$(dsq -D "$1" "select * from {}")
basename=$(basename -- "$1")
filename=${basename// /_}
cp "$sqlite" "${filename%.*}.db"
sqlite3 "${filename%.*}.db" < "${filename%.*}.sql"
if [ -n "$2" ]; then
    pgloader "${filename%.*}.db" "$2"
fi
