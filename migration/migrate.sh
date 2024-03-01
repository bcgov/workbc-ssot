#! /bin/bash

# Labour Force Survey
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/2021_LFS Data Sheet finalv3.xlsx" "data/2021_LFS Data Sheet finalv3-%s.csv"
cat "data/2021_LFS Data Sheet finalv3-Industry Profiles.csv" | php csv_extract.php --range 5-23 | php csv_refill.php --col 7 --col 8 --col 11 --col 14 --col 17 --col 20 --col 23 --col 26 --col 29 --col 32 --col 35 --col 38 --col 41 --col 43 --col 45 --col 47 --col 49 --col 51 --col 53 --col 55 > "load/labour_force_survey_industry.csv"
cat "data/2021_LFS Data Sheet finalv3-Regional Profiles.csv" | php csv_extract.php --range 6-13 > "load/labour_force_survey_regional_employment.csv"
cat "data/2021_LFS Data Sheet finalv3-Regional Profiles.csv" | php csv_extract.php --range 20-27 > "load/labour_force_survey_regional_industry_region.csv"
cat "data/2021_LFS Data Sheet finalv3-Regional Profiles.csv" | php csv_extract.php --range 34-41 > "load/labour_force_survey_regional_industry_province.csv"

# High Opportunity Occupations
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/2022 HOO BC and Region for new tool - Wage data median wage UPDATED July 2023.xlsx" "data/2022 HOO BC and Region for new tool - Wage data median wage UPDATED July 2023-%s.csv"
cat "data/2022 HOO BC and Region for new tool - Wage data median wage UPDATED July 2023-Sheet2.csv" | php csv_extract.php --range 2 --cols 15 > "load/high_opportunity_occupations.csv"

# B.C. Labour Market Outlook, Career Profiles
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/3.3.1_WorkBC_Career_Profile_Data_2022-2032.xlsx" "data/3.3.1_WorkBC_Career_Profile_Data_2022-2032-%s.csv"
cat "data/3.3.1_WorkBC_Career_Profile_Data_2022-2032-Regional Outlook.csv" | php csv_extract.php --range 5 > load/career_regional.csv
cat "data/3.3.1_WorkBC_Career_Profile_Data_2022-2032-Provincial Outlook.csv" | php csv_extract.php --range 4 > load/career_provincial.csv

# B.C. Labour Market Outlook, Industry Profiles
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/3.3.2_WorkBC_Industry_Profile_2022-2032_revised_Feb24.xlsx" "data/3.3.2_WorkBC_Industry_Profile_2022-2032_revised_Feb24-%s.csv"
cat "data/3.3.2_WorkBC_Industry_Profile_2022-2032_revised_Feb24-BC.csv" | php csv_extract.php --range 3 > load/industry_outlook.csv

# B.C. Labour Market Outlook, Regional Profiles
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/3.3.3_WorkBC_Regional_Profile_Data_2022-2032_Updated_March92022.xlsx" "data/3.3.3_WorkBC_Regional_Profile_Data_2022-2032_Updated_March92022-%s.csv"
cat "data/3.3.3_WorkBC_Regional_Profile_Data_2022-2032_Updated_March92022-Regional Profiles - LMO.csv" | php csv_extract.php --range 5 > load/regional_labour_market_outlook.csv
cat "data/3.3.3_WorkBC_Regional_Profile_Data_2022-2032_Updated_March92022-Top Industries.csv" | php csv_extract.php --range 5 | php csv_empty.php > load/regional_top_industries.csv
cat "data/3.3.3_WorkBC_Regional_Profile_Data_2022-2032_Updated_March92022-Top Occupation.csv" | php csv_extract.php --range 5 | php csv_empty.php > load/regional_top_occupations.csv

# B.C. Population Distribution
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/2021 BC Population Distribution.xlsx" "data/2021 BC Population Distribution-%s.csv"
cat "data/2021 BC Population Distribution-Region Population Estimates.csv" | php csv_extract.php --range 3-11 > load/population.csv

# B.C. Labour Market Outlook, Job Openings
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/Job Openings by Industry_2016 Census_2022 LMO_Draft.xlsx" "data/Job Openings by Industry_2016 Census_2022 LMO_Draft-%s.csv"
cat "data/Job Openings by Industry_2016 Census_2022 LMO_Draft-Career Profiles.csv" | php csv_extract.php --range 5 > load/openings_careers.csv

# Wage Data
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/WorkBC_2022_Wage_Data - UPDATED June 28 2023.xlsx" "data/WorkBC_2022_Wage_Data - UPDATED June 28 2023-%s.csv"
cat "data/WorkBC_2022_Wage_Data - UPDATED June 28 2023-Sheet1.csv" | php csv_extract.php --range 2 > load/wages.csv

# Occupational Interests
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/Occupational Interests_updated_March_10_2023.xlsx" "data/Occupational Interests_updated_March_10_2023-%s.csv"
cat "data/Occupational Interests_updated_March_10_2023-Occ Interest_Stack.csv" | php csv_extract.php --range 2 > load/occupational_interests.csv

# TODO Common Job Titles for Career Profiles

# Skills for Career Profiles
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/UPDATED FINAL Skills Data for Career Profiles (updated Sept 12 23).xlsx" "data/UPDATED FINAL Skills Data for Career Profiles (updated Sept 12 23)-%s.csv"
cat "data/UPDATED FINAL Skills Data for Career Profiles (updated Sept 12 23)-Sheet1.csv" | php csv_extract.php --range 2 > load/skills.csv

# TODO Education Backgrounds for Career Profiles

# TODO Census

# Top 10 Careers by Industry
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/2022 top_10_careers_by_aggregate_industry_8_29_23.xlsx" "data/2022 top_10_careers_by_aggregate_industry_8_29_23-%s.csv"
cat "data/2022 top_10_careers_by_aggregate_industry_8_29_23-Sheet1.csv" | php csv_extract.php --range 2 > load/openings_industry.csv

# TODO Key Cities

# TODO Labour Market Monthly Updates
# REFRESH_WorkBC LMS _2021 Jan FINAL.xlsx
# REFRESH_WorkBC LMS _2021 Feb FINAL.xlsx
# REFRESH_WorkBC LMS _2021 Mar FINAL.xlsx
# REFRESH_WorkBC LMS _2021 Apr FINAL.xlsx
# REFRESH_WorkBC LMS _2021 May FINAL.xlsx
# REFRESH_WorkBC LMS _2021 June FINAL.xlsx
# REFRESH_WorkBC LMS _2021 July FINAL.xlsx
# REFRESH_WorkBC LMS _2021 Aug FINAL.xlsx
# REFRESH_WorkBC LMS _2021 Sept FINAL.xlsx
# REFRESH_WorkBC LMS _2021 Oct FINAL.xlsx
# REFRESH_WorkBC LMS _2021 Nov FINAL.xlsx
# REFRESH_WorkBC LMS _2021 Dec FINAL.xlsx
# REFRESH_WorkBC LMS _2022 Jan FINAL.xlsx
# REFRESH_WorkBC LMS _2022 Feb FINAL.xlsx
# REFRESH_WorkBC LMS _2022 Mar FINAL.xlsx
# REFRESH_WorkBC LMS _2022 Apr FINAL.xlsx
# REFRESH_WorkBC LMS _2022 May FINAL.xlsx
# REFRESH_WorkBC LMS _2022 June FINAL.xlsx
# REFRESH_WorkBC LMS _2022 July FINAL.xlsx
# REFRESH_WorkBC LMS _2022 Aug FINAL.xlsx
# REFRESH_WorkBC LMS _2022 Sept FINAL.xlsx
# REFRESH_WorkBC LMS _2022 Oct.xlsx
# REFRESH_WorkBC LMS _2022_Nov.xlsx
# REFRESH_WorkBC LMS _2022_Dec.xlsx
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/REFRESH_WorkBC LMS _2023_Jan new v2.xlsx" "data/REFRESH_WorkBC LMS _2023 Jan-%s.csv"
cat "data/REFRESH_WorkBC LMS _2023 Jan-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update_202101.php 2023 1 > "load/updates/monthly_labour_market_updates_2023_01.csv"
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/REFRESH_WorkBC LMS _2023_FEB_updated Mar21 v2.xlsx" "data/REFRESH_WorkBC LMS _2023 Feb-%s.csv"
cat "data/REFRESH_WorkBC LMS _2023 Feb-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update_202101.php 2023 2 > "load/updates/monthly_labour_market_updates_2023_02.csv"
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/REFRESH_WorkBC LMS _2023_Mar updated Apr14.xlsx" "data/REFRESH_WorkBC LMS _2023 Mar-%s.csv"
cat "data/REFRESH_WorkBC LMS _2023 Mar-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update_202303.php 2023 3 > "load/updates/monthly_labour_market_updates_2023_03.csv"
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/REFRESH_WorkBC LMS _2023_Apr.xlsx" "data/REFRESH_WorkBC LMS _2023 Apr-%s.csv"
cat "data/REFRESH_WorkBC LMS _2023 Apr-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update_202303.php 2023 4 > "load/updates/monthly_labour_market_updates_2023_04.csv"
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/REFRESH_WorkBC LMS _2023_May.xlsx" "data/REFRESH_WorkBC LMS _2023_May-%s.csv"
cat "data/REFRESH_WorkBC LMS _2023_May-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update_202303.php 2023 5 > "load/updates/monthly_labour_market_updates_2023_05.csv"
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/REFRESH_WorkBC LMS _2023_June.xlsx" "data/REFRESH_WorkBC LMS _2023_Jun-%s.csv"
cat "data/REFRESH_WorkBC LMS _2023_Jun-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update_202303.php 2023 6 > "load/updates/monthly_labour_market_updates_2023_06.csv"
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/REFRESH_WorkBC LMS _2023_July.xlsx" "data/REFRESH_WorkBC LMS _2023_Jul-%s.csv"
cat "data/REFRESH_WorkBC LMS _2023_Jul-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update_202303.php 2023 7 > "load/updates/monthly_labour_market_updates_2023_07.csv"
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/REFRESH_WorkBC LMS _2023_Aug_FIXED_10-18-23.xlsx" "data/REFRESH_WorkBC LMS _2023_Aug_FIXED_10-18-23-%s.csv"
cat "data/REFRESH_WorkBC LMS _2023_Aug_FIXED_10-18-23-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update_202303.php 2023 8 > "load/updates/monthly_labour_market_updates_2023_08.csv"
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/REFRESH_WorkBC LMS _Sept_2023_FIXED.xlsx" "data/REFRESH_WorkBC LMS _Sept_2023_FIXED-%s.csv"
cat "data/REFRESH_WorkBC LMS _Sept_2023_FIXED-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update_202308.php 2023 9 > "load/updates/monthly_labour_market_updates_2023_09.csv"
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/REFRESH_WorkBC LMS _Oct_2023.xlsx" "data/REFRESH_WorkBC LMS _Oct_2023-%s.csv"
cat "data/REFRESH_WorkBC LMS _Oct_2023-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update_202308.php 2023 10 > "load/updates/monthly_labour_market_updates_2023_10.csv"
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/REFRESH_WorkBC LMS _Nov_2023_FIXED.xlsx" "data/REFRESH_WorkBC LMS _Nov_2023-%s.csv"
cat "data/REFRESH_WorkBC LMS _Nov_2023-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update_202308.php 2023 11 > "load/updates/monthly_labour_market_updates_2023_11.csv"
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/REFRESH_WorkBC LMS _Dec_2023.xlsx" "data/REFRESH_WorkBC LMS _Dec_2023-%s.csv"
cat "data/REFRESH_WorkBC LMS _Dec_2023-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update_202308.php 2023 12 > "load/updates/monthly_labour_market_updates_2023_12.csv"
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/WorkBC LMS _JAN_2024.xlsx" "data/WorkBC LMS _JAN_2024-%s.csv"
cat "data/WorkBC LMS _JAN_2024-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update_202308.php 2024 01 > "load/updates/monthly_labour_market_updates_2024_01.csv"

# Load all data in the database.
for f in load/*.load; do pgloader -l workbc.lisp "$f"; done
psql -c 'TRUNCATE monthly_labour_market_updates'
for f in load/updates/*.csv; do SOURCE="/app/$f" pgloader -l workbc.lisp load/updates/monthly_labour_market_updates.load; done
