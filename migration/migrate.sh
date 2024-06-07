#! /bin/bash

# Labour Force Survey
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/2023_LFS_data_sheet_Feb6_24.xlsx" "data/2023_LFS_data_sheet_Feb6_24-%s.csv"
cat "data/2023_LFS_data_sheet_Feb6_24-Industry Profiles.csv" | php csv_extract.php --range 5-23 | php csv_refill.php --col 7 --col 8 --col 11 --col 14 --col 17 --col 20 --col 23 --col 26 --col 29 --col 32 --col 35 --col 38 --col 41 --col 43 --col 45 --col 47 --col 49 --col 51 --col 53 --col 55 > "load/labour_force_survey_industry.csv"
cat "data/2023_LFS_data_sheet_Feb6_24-Regional Profiles.csv" | php csv_extract.php --range 6-13 > "load/labour_force_survey_regional_employment.csv"
cat "data/2023_LFS_data_sheet_Feb6_24-Regional Profiles.csv" | php csv_extract.php --range 21-28 > "load/labour_force_survey_regional_industry_region.csv"
cat "data/2023_LFS_data_sheet_Feb6_24-Regional Profiles.csv" | php csv_extract.php --range 35-42 > "load/labour_force_survey_regional_industry_province.csv"

# High Opportunity Occupations
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/HOO_BC_and_Region_for_new_tool_2023_Jan22_24.xlsx" "data/HOO_BC_and_Region_for_new_tool_2023_Jan22_24-%s.csv"
cat "data/HOO_BC_and_Region_for_new_tool_2023_Jan22_24-Sheet 1.csv" | php csv_extract.php --range 2 --cols 15 > "load/high_opportunity_occupations.csv"

# B.C. Labour Market Outlook, Career Profiles
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/WorkBC_Career_Profile_Data_2023_Apr5_24.xlsx" "data/WorkBC_Career_Profile_Data_2023_Apr5_24-%s.csv"
cat "data/WorkBC_Career_Profile_Data_2023_Apr5_24-Regional Outlook.csv" | php csv_extract.php --range 5 > load/career_regional.csv
cat "data/WorkBC_Career_Profile_Data_2023_Apr5_24-Provincial Outlook.csv" | php csv_extract.php --range 4 > load/career_provincial.csv

# B.C. Labour Market Outlook, Industry Profiles
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/WorkBC_Industry_Profile_2023__Jan24_24.xlsx" "data/WorkBC_Industry_Profile_2023__Jan24_24-%s.csv"
cat "data/WorkBC_Industry_Profile_2023__Jan24_24-Sheet1.csv" | php csv_extract.php --range 3 > load/industry_outlook.csv

# B.C. Labour Market Outlook, Regional Profiles
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/WorkBC_Regional_Profile_Data_2023__Jan24_24.xlsx" "data/WorkBC_Regional_Profile_Data_2023__Jan24_24-%s.csv"
cat "data/WorkBC_Regional_Profile_Data_2023__Jan24_24-Regional Profiles - LMO.csv" | php csv_extract.php --range 5 > load/regional_labour_market_outlook.csv
cat "data/WorkBC_Regional_Profile_Data_2023__Jan24_24-Top Occupation.csv" | php csv_extract.php --range 4 | php csv_empty.php > load/regional_top_occupations.csv

# B.C. Labour Market Outlook, Job Openings
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/Job_Openings_by_Industry_LMO_2023_Jan22_24.xlsx" "data/Job_Openings_by_Industry_LMO_2023_Jan22_24-%s.csv"
cat "data/Job_Openings_by_Industry_LMO_2023_Jan22_24-Career Profiles.csv" | php csv_extract.php --range 5 > load/openings_careers.csv

# Wage Data
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/WorkBC_2023_Wage_Data_Jan29_24.xlsx" "data/WorkBC_2023_Wage_Data_Jan29_24-%s.csv"
cat "data/WorkBC_2023_Wage_Data_Jan29_24-Sheet1.csv" | php csv_extract.php --range 2 > load/wages.csv

# Occupational Interests
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/Occupational_Interests_2023_Apr5_24.xlsx" "data/Occupational_Interests_2023_Apr5_24-%s.csv"
cat "data/Occupational_Interests_2023_Apr5_24-Sheet 1.csv" | php csv_extract.php --range 2 > load/occupational_interests.csv

# TODO Common Job Titles for Career Profiles
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/2021NOC_CommonJobTitles_IllustrativeListing_Feb16_2024.xlsx" "data/2021NOC_CommonJobTitles_IllustrativeListing_Feb16_2024-%s.csv"
cat "data/2021NOC_CommonJobTitles_IllustrativeListing_Feb16_2024-CommonJobTitles_NOC2021_fina.csv" | php csv_extract.php --range 2 > load/titles.csv

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
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/top_10_careers_by_aggregate_industry_2023__Jan22_24.xlsx" "data/top_10_careers_by_aggregate_industry_2023__Jan22_24-%s.csv"
cat "data/top_10_careers_by_aggregate_industry_2023__Jan22_24-Sheet 1.csv" | php csv_extract.php --range 2 > load/openings_industry.csv

# Career Trek
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/WorkBC_Career_Trek_2023__Apr25_24.xlsx" "data/WorkBC_Career_Trek_2023__Apr25_24-%s.csv"
cat "data/WorkBC_Career_Trek_2023__Apr25_24-LMO.csv" | php csv_extract.php --range 2 > load/career_trek.csv

# Career Search Tool Job Openings
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/Career_Search_Tool_Job_Openings_2023_Apr29_24.xlsx" "data/Career_Search_Tool_Job_Openings_2023_Apr29_24-%s.csv"
cat "data/Career_Search_Tool_Job_Openings_2023_Apr29_24-Sheet 1.csv" | php csv_extract.php --range 2 > load/career_search_openings.csv

# Career Search Tool Occupation Groups
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/career_search_tool_occupation_groups_manual_update_2023_Apr29_24.xlsx" "data/career_search_tool_occupation_groups_manual_update_2023_Apr29_24-%s.csv"
cat "data/career_search_tool_occupation_groups_manual_update_2023_Apr29_24-Sheet 1.csv" | php csv_extract.php --range 2 > load/career_search_groups.csv

# Career Transition Tool Opportunities
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/FINAL_Career Transition Tool_Opportunities_05_2024.xlsx" "data/FINAL_Career Transition Tool_Opportunities_05_2024-%s.csv"
cat "data/FINAL_Career Transition Tool_Opportunities_05_2024-Sheet 1.csv" | php csv_extract.php --range 2 > load/career_transition_opportunities.csv

# NOC 2021 Concordance
php nocs.php "data/NOC2021/" > load/nocs.csv

# EDM NAICS / Industry Profiles
php industries.php "data/EDM/" > load/industries.csv

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
cat "data/REFRESH_WorkBC LMS _2023 Jan-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update_v1.php 2023 1 > "load/updates/monthly_labour_market_updates_2023_01.csv"
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/REFRESH_WorkBC LMS _2023_FEB_updated Mar21 v2.xlsx" "data/REFRESH_WorkBC LMS _2023 Feb-%s.csv"
cat "data/REFRESH_WorkBC LMS _2023 Feb-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update_v1.php 2023 2 > "load/updates/monthly_labour_market_updates_2023_02.csv"
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/REFRESH_WorkBC LMS _2023_Mar updated Apr14.xlsx" "data/REFRESH_WorkBC LMS _2023 Mar-%s.csv"
cat "data/REFRESH_WorkBC LMS _2023 Mar-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update_v2.php 2023 3 > "load/updates/monthly_labour_market_updates_2023_03.csv"
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/REFRESH_WorkBC LMS _2023_Apr.xlsx" "data/REFRESH_WorkBC LMS _2023 Apr-%s.csv"
cat "data/REFRESH_WorkBC LMS _2023 Apr-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update_v2.php 2023 4 > "load/updates/monthly_labour_market_updates_2023_04.csv"
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/REFRESH_WorkBC LMS _2023_May.xlsx" "data/REFRESH_WorkBC LMS _2023_May-%s.csv"
cat "data/REFRESH_WorkBC LMS _2023_May-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update_v2.php 2023 5 > "load/updates/monthly_labour_market_updates_2023_05.csv"
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/REFRESH_WorkBC LMS _2023_June.xlsx" "data/REFRESH_WorkBC LMS _2023_Jun-%s.csv"
cat "data/REFRESH_WorkBC LMS _2023_Jun-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update_v2.php 2023 6 > "load/updates/monthly_labour_market_updates_2023_06.csv"
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/REFRESH_WorkBC LMS _2023_July.xlsx" "data/REFRESH_WorkBC LMS _2023_Jul-%s.csv"
cat "data/REFRESH_WorkBC LMS _2023_Jul-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update_v2.php 2023 7 > "load/updates/monthly_labour_market_updates_2023_07.csv"
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/REFRESH_WorkBC LMS _2023_Aug_FIXED_10-18-23.xlsx" "data/REFRESH_WorkBC LMS _2023_Aug_FIXED_10-18-23-%s.csv"
cat "data/REFRESH_WorkBC LMS _2023_Aug_FIXED_10-18-23-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update_v2.php 2023 8 > "load/updates/monthly_labour_market_updates_2023_08.csv"
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/REFRESH_WorkBC LMS _Sept_2023_FIXED.xlsx" "data/REFRESH_WorkBC LMS _Sept_2023_FIXED-%s.csv"
cat "data/REFRESH_WorkBC LMS _Sept_2023_FIXED-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update_v3.php 2023 9 > "load/updates/monthly_labour_market_updates_2023_09.csv"
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/REFRESH_WorkBC LMS _Oct_2023.xlsx" "data/REFRESH_WorkBC LMS _Oct_2023-%s.csv"
cat "data/REFRESH_WorkBC LMS _Oct_2023-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update_v3.php 2023 10 > "load/updates/monthly_labour_market_updates_2023_10.csv"
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/REFRESH_WorkBC LMS _Nov_2023_FIXED.xlsx" "data/REFRESH_WorkBC LMS _Nov_2023-%s.csv"
cat "data/REFRESH_WorkBC LMS _Nov_2023-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update_v3.php 2023 11 > "load/updates/monthly_labour_market_updates_2023_11.csv"
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/REFRESH_WorkBC LMS _Dec_2023.xlsx" "data/REFRESH_WorkBC LMS _Dec_2023-%s.csv"
cat "data/REFRESH_WorkBC LMS _Dec_2023-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update_v3.php 2023 12 > "load/updates/monthly_labour_market_updates_2023_12.csv"
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/WorkBC LMS _JAN_2024.xlsx" "data/WorkBC LMS _JAN_2024-%s.csv"
cat "data/WorkBC LMS _JAN_2024-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update_v3.php 2024 01 > "load/updates/monthly_labour_market_updates_2024_01.csv"
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/WorkBC LMS _FEB_2024.xlsx" "data/WorkBC LMS _FEB_2024-%s.csv"
cat "data/WorkBC LMS _FEB_2024-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update_v3.php 2024 02 > "load/updates/monthly_labour_market_updates_2024_02.csv"
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/WorkBC LMS Mar_2024- FINAL.xlsx" "data/WorkBC LMS Mar_2024- FINAL-%s.csv"
cat "data/WorkBC LMS Mar_2024- FINAL-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update_v3.php 2024 03 > "load/updates/monthly_labour_market_updates_2024_03.csv"
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/WorkBC LMS Apr_2024.xlsx" "data/WorkBC LMS Apr_2024-%s.csv"
cat "data/WorkBC LMS Apr_2024-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update_v3.php 2024 04 > "load/updates/monthly_labour_market_updates_2024_04.csv"
ssconvert --export-type=Gnumeric_stf:stf_csv --export-file-per-sheet "data/WorkBC LMS May_2024.xlsx" "data/WorkBC LMS May_2024-%s.csv"
cat "data/WorkBC LMS May_2024-Sheet3.csv" | php csv_empty.php | php monthly_labour_market_update_v3.php 2024 05 > "load/updates/monthly_labour_market_updates_2024_05.csv"

# Load all data in the database.
for f in load/*.load; do pgloader -l workbc.lisp "$f"; done
psql -c 'DROP TABLE monthly_labour_market_updates'
for f in load/updates/*.csv; do SOURCE="/app/$f" pgloader -l workbc.lisp load/monthly_labour_market_updates.load; done
