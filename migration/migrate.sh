#! /bin/bash

# 2021_LFS Data Sheet finalv2.xlsx
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/2021_LFS Data Sheet finalv2.xlsx" "data/2021_LFS Data Sheet finalv2-%s.csv"
cat "data/2021_LFS Data Sheet finalv2-Industry Profiles.csv" | php csv_extract.php --range 5-23 | php csv_refill.php --col 7 --col 8 --col 11 --col 14 --col 17 --col 20 --col 23 --col 26 --col 29 --col 32 --col 35 --col 38 --col 41 --col 43 --col 45 --col 47 --col 49 --col 51 --col 53 --col 55 > "load/labour_force_survey_industry.csv"
cat "data/2021_LFS Data Sheet finalv2-Regional Profiles.csv" | php csv_extract.php --range 6-13 > "load/labour_force_survey_regional_employment.csv"
cat "data/2021_LFS Data Sheet finalv2-Regional Profiles.csv" | php csv_extract.php --range 20-27 > "load/labour_force_survey_regional_industry_region.csv"
cat "data/2021_LFS Data Sheet finalv2-Regional Profiles.csv" | php csv_extract.php --range 34-41 > "load/labour_force_survey_regional_industry_province.csv"

# 2022 HOO BC and Region for new tool.xlsx
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/2022 HOO BC and Region for new tool.xlsx" "data/2022 HOO BC and Region for new tool-%s.csv"
cat "data/2022 HOO BC and Region for new tool-Sheet1.csv" | php csv_extract.php --range 2 > "load/high_opportunity_occupations.csv"

# Load all data in the database.
for f in load/*.load; do pgloader -l workbc.lisp "$f"; done
for f in load/updates/*.csv; do SOURCE="/app/$f" pgloader -l workbc.lisp load/updates/monthly_labour_market_updates.load; done
