#! /bin/bash

##
## Convert and load a new Industry Profile sheet.
##
## Usage: industry_outlook.sh SheetName["WorkBC_Industry_Profile_2026-08-05.xlsx"] ReportYear[2026] ReportMonth[1] UpdateDate["2028/08/05 14:06"]
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

# B.C. Labour Market Outlook, Industry Profiles
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/$filename" "data/${filename%.xlsx}-%s.csv"
cat "data/${filename%.xlsx}-Sheet1.csv" | php csv_extract.php --range 3 > load/updates/industry_outlook_${year}.csv

# Load file into database.
psql -c "DELETE FROM industry_outlook;"
SOURCE="/app/load/updates/industry_outlook_${year}.csv" pgloader -l workbc.lisp load/updates/industry_outlook.load

# Update sources.csv and load it into database.
csvq --repository load --datetime-format "%Y/%m/%d %H:%i" \
"REPLACE INTO sources (filename, date, endpoint, period, sheet, label) USING (endpoint, period) VALUES('${filename%.xlsx}', '${date}', 'industry_outlook', '${year}/${month_with_zero}/01 08:00', 'Sheet1', 'B.C. Labour Market Outlook')"
pgloader -l workbc.lisp load/sources.load
chmod ugoa+rw load/sources.csv

# If we reach this line, the whole script ran successfully.
echo "Industry Outlook SUCCESSFULLY UPDATED."
