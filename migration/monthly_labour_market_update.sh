#! /bin/bash

##
## Convert and load a new LMMU sheet.
##

set -xeuo pipefail

# Arguments validation.
if [[ -z "$1" ]] ; then
    echo 'Error: Missing sheet filename.'
    exit 1
fi
if [[ -z "$2" ]] ; then
    echo 'Error: Missing year.'
    exit 1
fi
if [[ -z "$3" ]] ; then
    echo 'Error: Missing month.'
    exit 1
fi
filename="$1"
year=$2
month=$(printf "%02d" $3)

# Sheet conversion.
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/$filename" "data/${filename%.xlsx}-%s.csv"
cat "data/${filename%.xlsx}-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update.php $year $month > "load/updates/monthly_labour_market_updates_${year}_${month}.csv"

# Load file into database.
psql -c "DELETE FROM monthly_labour_market_updates WHERE year = ${year} AND month = ${month};"
SOURCE="/app/load/updates/monthly_labour_market_updates_${year}_${month}.csv" pgloader -l workbc.lisp load/updates/monthly_labour_market_updates.load

# Update sources.csv and load it into database.
pgloader -l workbc.lisp load/sources.load
