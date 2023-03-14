#! /bin/bash

# 2021_LFS Data Sheet finalv2
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/2021_LFS Data Sheet finalv2.xlsx" "data/2021_LFS Data Sheet finalv2-%s.csv"
cat "data/2021_LFS Data Sheet finalv2-Industry Profiles.csv" | php csv_extract.php --range 5-23 | php csv_refill.php --col 7 --col 8 --col 11 --col 14 --col 17 --col 20 --col 23 --col 26 --col 29 --col 32 --col 35 --col 38 --col 41 --col 43 --col 45 --col 47 --col 49 --col 51 --col 53 --col 55 > "load/labour_force_survey_industry.csv"
cat "data/2021_LFS Data Sheet finalv2-Regional Profiles.csv" | php csv_extract.php --range 6-13 > "load/labour_force_survey_regional_employment.csv"
cat "data/2021_LFS Data Sheet finalv2-Regional Profiles.csv" | php csv_extract.php --range 20-27 > "load/labour_force_survey_regional_industry_region.csv"
cat "data/2021_LFS Data Sheet finalv2-Regional Profiles.csv" | php csv_extract.php --range 34-41 > "load/labour_force_survey_regional_industry_province.csv"

# 2022 HOO BC and Region for new tool
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/2022 HOO BC and Region for new tool.xlsx" "data/2022 HOO BC and Region for new tool-%s.csv"
cat "data/2022 HOO BC and Region for new tool-Sheet1.csv" | php csv_extract.php --range 2 > "load/high_opportunity_occupations.csv"

# 3.3.1_WorkBC_Career_Profile_Data_2022-2032
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/3.3.1_WorkBC_Career_Profile_Data_2022-2032.xlsx" "data/3.3.1_WorkBC_Career_Profile_Data_2022-2032-%s.csv"
cat "data/3.3.1_WorkBC_Career_Profile_Data_2022-2032-Regional Outlook.csv" | php csv_extract.php --range 5 > load/career_regional.csv
cat "data/3.3.1_WorkBC_Career_Profile_Data_2022-2032-Provincial Outlook.csv" | php csv_extract.php --range 4 > load/career_provincial.csv

# 3.3.2_WorkBC_Industry_Profile_2022-2032_revised_Feb24
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/3.3.2_WorkBC_Industry_Profile_2022-2032_revised_Feb24.xlsx" "data/3.3.2_WorkBC_Industry_Profile_2022-2032_revised_Feb24-%s.csv"
cat "data/3.3.2_WorkBC_Industry_Profile_2022-2032_revised_Feb24-BC.csv" | php csv_extract.php --range 4 > load/industry_outlook.csv

# 3.3.3_WorkBC_Regional_Profile_Data_2022-2032_Updated_March92022
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/3.3.3_WorkBC_Regional_Profile_Data_2022-2032_Updated_March92022.xlsx" "data/3.3.3_WorkBC_Regional_Profile_Data_2022-2032_Updated_March92022-%s.csv"
cat "data/3.3.3_WorkBC_Regional_Profile_Data_2022-2032_Updated_March92022-Regional Profiles - LMO.csv" | php csv_extract.php --range 5 > load/regional_labour_market_outlook.csv
cat "data/3.3.3_WorkBC_Regional_Profile_Data_2022-2032_Updated_March92022-Top Industries.csv" | php csv_extract.php --range 5 | php csv_empty.php > load/regional_top_industries.csv
cat "data/3.3.3_WorkBC_Regional_Profile_Data_2022-2032_Updated_March92022-Top Occupation.csv" | php csv_extract.php --range 5 | php csv_empty.php > load/regional_top_occupations.csv

# 2022 HOO BC and Region for new tool
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/2022 HOO BC and Region for new tool.xlsx" "data/2022 HOO BC and Region for new tool-%s.csv"
cat "data/2022 HOO BC and Region for new tool-Sheet1.csv" | php csv_extract.php --range 2 > load/high_opportunity_occupations.csv

# 2021 BC Population Distribution
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/2021 BC Population Distribution.xlsx" "data/2021 BC Population Distribution-%s.csv"
cat "data/2021 BC Population Distribution-Region Population Estimates.csv" | php csv_extract.php --range 3-11 > load/population.csv

# Job Openings by Industry_2016 Census_2022 LMO_Draft
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/Job Openings by Industry_2016 Census_2022 LMO_Draft.xlsx" "data/Job Openings by Industry_2016 Census_2022 LMO_Draft-%s.csv"
cat "data/Job Openings by Industry_2016 Census_2022 LMO_Draft-Career Profiles.csv" | php csv_extract.php --range 5 > load/openings_careers.csv

# TODO WorkBC_2021_Wage_Data

# TODO Common Job Titles revised - July 18 2017 (RA)

# TODO UPDATED FINAL Skills Data for Career Profiles (updated April16 19)

# TODO All Occupation's Education Background 2021

# TODO 2016 Census

# TODO Occupational Interests_Mar 24 2021

# TODO 2022 top_10_careers_by_aggregate_industry

# TODO Key Cities

# TODO REFRESH_WorkBC LMS _<YYYY> <MMM> FINAL
# REFRESH_WorkBC LMS _2021 Apr FINAL.xlsx
# REFRESH_WorkBC LMS _2021 Aug FINAL.xlsx
# REFRESH_WorkBC LMS _2021 Dec FINAL.xlsx
# REFRESH_WorkBC LMS _2021 Feb FINAL.xlsx
# REFRESH_WorkBC LMS _2021 Jan FINAL.xlsx
# REFRESH_WorkBC LMS _2021 July FINAL.xlsx
# REFRESH_WorkBC LMS _2021 June FINAL.xlsx
# REFRESH_WorkBC LMS _2021 Mar FINAL.xlsx
# REFRESH_WorkBC LMS _2021 May FINAL.xlsx
# REFRESH_WorkBC LMS _2021 Nov FINAL.xlsx
# REFRESH_WorkBC LMS _2021 Oct FINAL.xlsx
# REFRESH_WorkBC LMS _2021 Sept FINAL.xlsx
# REFRESH_WorkBC LMS _2022 Apr FINAL.xlsx
# REFRESH_WorkBC LMS _2022 Aug FINAL.xlsx
# REFRESH_WorkBC LMS _2022 Feb FINAL.xlsx
# REFRESH_WorkBC LMS _2022 Jan FINAL.xlsx
# REFRESH_WorkBC LMS _2022 July FINAL.xlsx
# REFRESH_WorkBC LMS _2022 June FINAL.xlsx
# REFRESH_WorkBC LMS _2022 Mar FINAL.xlsx
# REFRESH_WorkBC LMS _2022 May FINAL.xlsx
# REFRESH_WorkBC LMS _2022 Oct.xlsx
# REFRESH_WorkBC LMS _2022 Sept FINAL.xlsx
# REFRESH_WorkBC LMS _2022_Dec.xlsx
# REFRESH_WorkBC LMS _2022_Nov.xlsx
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/REFRESH_WorkBC LMS _2023 Jan.xlsx" "data/REFRESH_WorkBC LMS _2023 Jan-%s.csv"
cat "data/REFRESH_WorkBC LMS _2023 Jan-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update.php 2023 1 > "load/updates/monthly_labour_market_updates_2023_01.csv"
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/REFRESH_WorkBC LMS _2023 Feb.xlsx" "data/REFRESH_WorkBC LMS _2023 Feb-%s.csv"
cat "data/REFRESH_WorkBC LMS _2023 Feb-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update.php 2023 2 > "load/updates/monthly_labour_market_updates_2023_02.csv"

# Load all data in the database.
for f in load/*.load; do pgloader -l workbc.lisp "$f"; done
psql -c 'TRUNCATE monthly_labour_market_updates'
for f in load/updates/*.csv; do SOURCE="/app/$f" pgloader -l workbc.lisp load/updates/monthly_labour_market_updates.load; done
