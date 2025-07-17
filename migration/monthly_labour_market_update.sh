#! /bin/bash

##
## Convert and load a new LMMU sheet.
##

set -xeuo pipefail

# Arguments validation.
if [[ -z "${1:-}" ]] ; then
    echo 'Error: Missing sheet filename.'
    exit 1
fi
if [[ -z "${2:-}" ]] ; then
    echo 'Error: Missing year.'
    exit 1
fi
if [[ -z "${3:-}" ]] ; then
    echo 'Error: Missing month.'
    exit 1
fi
if [[ -z "${4:-}" ]] ; then
    echo 'Error: Missing update date.'
    exit 1
fi
filename="$1"
year=$2
month_without_zero=$3
month_with_zero=$(printf "%02d" $3)
date="$4"

# Sheet conversion.
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/$filename" "data/${filename%.xlsx}-%s.csv"
cat "data/${filename%.xlsx}-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update.php $year $month_without_zero > "load/updates/monthly_labour_market_updates_${year}_${month_with_zero}.csv"

# Load file into database.
psql -c "DELETE FROM monthly_labour_market_updates WHERE year = ${year} AND month = ${month_without_zero};"
SOURCE="/app/load/updates/monthly_labour_market_updates_${year}_${month_with_zero}.csv" pgloader -l workbc.lisp load/updates/monthly_labour_market_updates.load

# Update sources.csv and load it into database.
csvq --repository load --datetime-format "%Y/%m/%d %H:%i" \
"REPLACE INTO sources (filename, date, endpoint, period, sheet, label) USING (endpoint, period) VALUES('${filename%.xlsx}', '${date}', 'monthly_labour_market_updates', '${year}/${month_with_zero}/01 08:00', 'Sheet3', 'Labour Force Survey (monthly, seasonally adjusted)')"
pgloader -l workbc.lisp load/sources.load

# If we reach this line, the whole script ran successfully.
echo "LMMU SUCCESSFULLY UPDATED."
