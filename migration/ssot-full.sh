#! /bin/bash

##
## Rebuild full SSOT from scratch.
##

set -xeuo pipefail

# Labour Force Survey
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/2023_LFS_data_sheet_Feb6_24.xlsx" "data/2023_LFS_data_sheet_Feb6_24-%s.csv"
cat "data/2023_LFS_data_sheet_Feb6_24-Industry Profiles.csv" | php csv_extract.php --range 5-23 | php csv_refill.php --col 7 --col 8 --col 11 --col 14 --col 17 --col 20 --col 23 --col 26 --col 29 --col 32 --col 35 --col 38 --col 41 --col 43 --col 45 --col 47 --col 49 --col 51 --col 53 --col 55 > "load/labour_force_survey_industry.csv"
cat "data/2023_LFS_data_sheet_Feb6_24-Regional Profiles.csv" | php csv_extract.php --range 6-13 > "load/labour_force_survey_regional_employment.csv"
cat "data/2023_LFS_data_sheet_Feb6_24-Regional Profiles.csv" | php csv_extract.php --range 21-28 > "load/labour_force_survey_regional_industry_region.csv"
cat "data/2023_LFS_data_sheet_Feb6_24-Regional Profiles.csv" | php csv_extract.php --range 35-42 > "load/labour_force_survey_regional_industry_province.csv"

# High Opportunity Occupations
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/HOO_BC_and_Region_for_new_tool_2024_Jan_15_2025.xlsx" "data/HOO_BC_and_Region_for_new_tool_2024_Jan_15_2025-%s.csv"
cat "data/HOO_BC_and_Region_for_new_tool_2024_Jan_15_2025-Sheet 1.csv" | php csv_extract.php --range 2 --cols 15 > "load/high_opportunity_occupations.csv"

# B.C. Labour Market Outlook, Career Profiles
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/WorkBC_Career_Profile_Data_2024_Jan_15_2025.xlsx" "data/WorkBC_Career_Profile_Data_2024_Jan_15_2025-%s.csv"
cat "data/WorkBC_Career_Profile_Data_2024_Jan_15_2025-Regional Outlook.csv" | php csv_extract.php --range 5 > load/career_regional.csv
cat "data/WorkBC_Career_Profile_Data_2024_Jan_15_2025-Provincial Outlook.csv" | php csv_extract.php --range 4 > load/career_provincial.csv

# B.C. Labour Market Outlook, Industry Profiles
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/WorkBC_Industry_Profile_2024_Jan13_2025.xlsx" "data/WorkBC_Industry_Profile_2024_Jan13_2025-%s.csv"
cat "data/WorkBC_Industry_Profile_2024_Jan13_2025-Sheet1.csv" | php csv_extract.php --range 3 > load/industry_outlook.csv

# B.C. Labour Market Outlook, Regional Profiles
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/WorkBC_Regional_Profile_Data_2024_Jan13_2025.xlsx" "data/WorkBC_Regional_Profile_Data_2024_Jan13_2025-%s.csv"
cat "data/WorkBC_Regional_Profile_Data_2024_Jan13_2025-Regional Profiles - LMO.csv" | php csv_extract.php --range 5 > load/regional_labour_market_outlook.csv
cat "data/WorkBC_Regional_Profile_Data_2024_Jan13_2025-Top Occupation.csv" | php csv_extract.php --range 4 | php csv_empty.php > load/regional_top_occupations.csv

# B.C. Labour Market Outlook, Job Openings
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/Job_Openings_by_Industry_LMO_2024_Jan_13_2025.xlsx" "data/Job_Openings_by_Industry_LMO_2024_Jan_13_2025-%s.csv"
cat "data/Job_Openings_by_Industry_LMO_2024_Jan_13_2025-Career Profiles.csv" | php csv_extract.php --range 5 > load/openings_careers.csv

# Wage Data
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/WorkBC_2024_Wage_Data_Jan_13_2025.xlsx" "data/WorkBC_2024_Wage_Data_Jan_13_2025-%s.csv"
cat "data/WorkBC_2024_Wage_Data_Jan_13_2025-Sheet1.csv" | php csv_extract.php --range 2 | php csv_trimpad.php --column 1:L:5:0 > load/wages.csv

# Occupational Interests
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/Occupational_Interests_2023_Apr5_24.xlsx" "data/Occupational_Interests_2023_Apr5_24-%s.csv"
cat "data/Occupational_Interests_2023_Apr5_24-Sheet 1.csv" | php csv_extract.php --range 2 > load/occupational_interests.csv

# Job Titles for Career Profiles
php titles.php "data/NOC2021/noc_2021_version_1.0_-_elements.csv" > load/titles.csv

# Skills for Career Profiles
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/Top skills by NOC2021 occupations_Feb12_24.xlsx" "data/Top skills by NOC2021 occupations_Feb12_24-%s.csv"
cat "data/Top skills by NOC2021 occupations_Feb12_24-Sheet 1.csv" | php csv_extract.php --range 2 > load/skills.csv

# TEERs for Career Profiles
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/All_Occupations'_TEERs_2023_Jan22_24.xlsx" "data/All_Occupations'_TEERs_2023_Jan22_24-%s.csv"
cat "data/All_Occupations'_TEERs_2023_Jan22_24-Sheet 1.csv" | php csv_extract.php --range 2 > load/education.csv

# Census
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/2021_Census_Jan18_23.xlsx" "data/2021_Census_Jan18_23-%s.csv"
cat "data/2021_Census_Jan18_23-Career Profiles.csv" | php csv_extract.php --range 5 > load/census.csv

# Top 10 Careers by Industry
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/top_10_careers_by_aggregate_industry_2024_Jan13_2025.xlsx" "data/top_10_careers_by_agg-regate_industry_2024_Jan13_2025-%s.csv"
cat "data/top_10_careers_by_aggregate_industry_2024_Jan13_2025-Sheet 1.csv" | php csv_extract.php --range 2 > load/openings_industry.csv

# Career Trek
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/WorkBC_Career_Trek_2023__Apr25_24.xlsx" "data/WorkBC_Career_Trek_2023__Apr25_24-%s.csv"
cat "data/WorkBC_Career_Trek_2023__Apr25_24-LMO.csv" | php csv_extract.php --range 2 > load/career_trek.csv

# Career Search Tool Job Openings
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/Career_Search_Tool_Job_Openings_2024_Jan_13_2025.xlsx" "data/Career_Search_Tool_Job_Openings_2024_Jan_13_2025-%s.csv"
cat "data/Career_Search_Tool_Job_Openings_2024_Jan_13_2025-Sheet 1.csv" | php csv_extract.php --range 2 > load/career_search_openings.csv

# Career Search Tool Occupation Groups
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/career_search_tool_occupation_groups_manual_update_2024_Jan_15_2025.xlsx" "data/career_search_tool_occupation_groups_manual_update_2024_Jan_15_2025-%s.csv"
cat "data/career_search_tool_occupation_groups_manual_update_2024_Jan_15_2025-Sheet 1.csv" | php csv_extract.php --range 2 > load/career_search_groups.csv

# Career Transition Tool Opportunities
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/FINAL_Career Transition Tool_Opportunities_05_2024.xlsx" "data/FINAL_Career Transition Tool_Opportunities_05_2024-%s.csv"
cat "data/FINAL_Career Transition Tool_Opportunities_05_2024-Sheet 1.csv" | php csv_extract.php --range 2 > load/career_transition_opportunities.csv

# NOC 2021 Concordance
php nocs.php "data/NOC2021/" > load/nocs.csv

# EDM NAICS / Industry Profiles
php industries.php "data/EDM/" > load/industries.csv

# O*NET <> NOC Concordance
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/onet2019_soc2018_noc2016_noc2021_crosswalk.xlsx" "data/onet2019_soc2018_noc2016_noc2021_crosswalk-%s.csv"
cat "data/onet2019_soc2018_noc2016_noc2021_crosswalk-Sheet1.csv" | php csv_extract.php --range 2 | php csv_trimpad.php --column 1:L:5:0  --column 3:L:4:0 > load/onet_nocs.csv

# Labour Market Monthly Updates
csvq --repository load --without-header --format csv --datetime-format "%Y/%m/%d %H:%i" \
"SELECT filename, YEAR(DATETIME(period)) AS year, MONTH(DATETIME(period)) AS month FROM sources WHERE endpoint='monthly_labour_market_updates' AND filename <> ''" \
| while IFS=, read -r filename year month; do
    ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/$filename.xlsx" "data/$filename-%s.csv"
    cat "data/$filename-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update.php $year $month > "load/updates/monthly_labour_market_updates_${year}_$(printf '%02d' $month).csv"
  done

# LMO 2024 Report
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/LMO 2024E Charts and Tables 2025 02 12.xlsx" "data/LMO 2024E Charts and Tables 2025 02 12-%s.csv"
cat "data/LMO 2024E Charts and Tables 2025 02 12-Figure 1.1-1.csv" | php csv_extract.php --range 4-6 --range 8-12 --range 16-17 --cols 3 | php csv_colkey.php --column 1 > load/lmo_report_2024_job_openings_10y.csv
cat "data/LMO 2024E Charts and Tables 2025 02 12-Figure 1.2-1.csv" | php csv_extract.php --range 5-7 --range 10-12 --range 15-17 | php csv_colkey.php --column 1 > load/lmo_report_2024_job_openings_annual.csv
cat "data/LMO 2024E Charts and Tables 2025 02 12-Figure 1.2-2.csv" | php csv_extract.php --range 5-10 --range 13-18 --range 21-26 | php csv_colkey.php --column 1 > load/lmo_report_2024_job_openings_new_supply_annual.csv
cat "data/LMO 2024E Charts and Tables 2025 02 12-Figure 2-1.csv" | php csv_extract.php --range 5-11 > load/lmo_report_2024_job_openings_teers.csv
cat "data/LMO 2024E Charts and Tables 2025 02 12-Figure 3-1.csv" | php csv_extract.php --range 3-23 | php csv_colkey.php --column 15 --industries > load/lmo_report_2024_job_openings_industries.csv
cat "data/LMO 2024E Charts and Tables 2025 02 12-Figure 4.1-1.csv" | php csv_extract.php --range 5-17 | php csv_trimpad.php --column="1:L:0:0:#" > load/lmo_report_2024_job_openings_broad_categories.csv
cat "data/LMO 2024E Charts and Tables 2025 02 12-Table 4.1-1.csv" | php csv_extract.php --header --cols=7 --range=6-10 --range=13-17 --range=20-24 --range=27-31 | php csv_trimpad.php --column="2:L:5:0:#" > load/lmo_report_2024_job_openings_occupations_altgrp_top5.csv
cat "data/LMO 2024E Charts and Tables 2025 02 12-Table 5-1.csv" | php csv_extract.php --range 5-12 | php csv_colkey.php --column 1 --regions > load/lmo_report_2024_job_openings_regions.csv
cat "data/LMO 2024E Charts and Tables 2025 02 12-Table 5.1-1.csv" | php csv_extract.php --range 5-10 > load/lmo_report_2024_job_openings_vancouver_island_coast.csv
cat "data/LMO 2024E Charts and Tables 2025 02 12-Table 5.2-1.csv" | php csv_extract.php --range 5-10 > load/lmo_report_2024_job_openings_mainland_southwest.csv
cat "data/LMO 2024E Charts and Tables 2025 02 12-Table 5.3-1.csv" | php csv_extract.php --range 5-10 > load/lmo_report_2024_job_openings_thompson_okanagan.csv
cat "data/LMO 2024E Charts and Tables 2025 02 12-Table 5.4.-1.csv" | php csv_extract.php --range 5-10 > load/lmo_report_2024_job_openings_kootenay.csv
cat "data/LMO 2024E Charts and Tables 2025 02 12-Table 5.5-1.csv" | php csv_extract.php --range 5-10 > load/lmo_report_2024_job_openings_cariboo.csv
cat "data/LMO 2024E Charts and Tables 2025 02 12-Table 5.6-1.csv" | php csv_extract.php --range 5-10 > load/lmo_report_2024_job_openings_north_coast_nechako.csv
cat "data/LMO 2024E Charts and Tables 2025 02 12-Table 5.7-1.csv" | php csv_extract.php --range 5-10 > load/lmo_report_2024_job_openings_northeast.csv
cat "data/LMO 2024E Charts and Tables 2025 02 12-Appendix 3.csv" | php csv_extract.php --cols=8 --range 5-84 | php csv_colkey.php --column 1 --industries > load/lmo_report_2024_job_openings_industries_full.csv
cat "data/LMO 2024E Charts and Tables 2025 02 12-Appendix 4.csv" | php csv_extract.php --cols=7 --range 5-516 | php csv_trimpad.php --column="1:L:5:0:#" > load/lmo_report_2024_job_openings_occupations_full.csv
cat "data/LMO 2024E Charts and Tables 2025 02 12-Appendix 5.csv" | php csv_extract.php --cols=7 --header=2 --range 5-39 --range 44-53 --range 58-125 --range 130-192 | php csv_trimpad.php --column="1:L:5:0:#" > load/lmo_report_2024_job_openings_occupations_altgrp.csv
cat "data/LMO 2024E Charts and Tables 2025 02 12-Appendix 6.csv" | php csv_extract.php --cols=7 --range 5-129 | php csv_trimpad.php --column="1:L:5:0:#" > load/lmo_report_2024_job_openings_occupations_high.csv

# FYP Categories & Areas of Interest.
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/FYP - Categories, Areas of interest, and NOCs 2025-04-23.xlsx" "data/FYP - Categories, Areas of interest, and NOCs 2025-04-23-%s.csv"
cat "data/FYP - Categories, Areas of interest, and NOCs 2025-04-23-CONSOLIDATED LIST.csv" | php csv_extract.php --range 2 | php csv_trimpad.php --column="3:L:5:0:#" > load/fyp_categories_interests.csv

# Load all data in the database.
for f in load/*.load; do echo "$f"; pgloader -l workbc.lisp "$f"; done
psql -c 'DROP TABLE monthly_labour_market_updates'
for f in load/updates/*.csv; do echo "$f"; SOURCE="/app/$f" pgloader -l workbc.lisp load/updates/monthly_labour_market_updates.load; done
