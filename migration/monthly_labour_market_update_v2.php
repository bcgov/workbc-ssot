<?php

/**
 * Transform the WorkBC LMS data into a format digestible by pgloader.
 * This version is to be used for monthly updates that do not include city unemployment information.
 *
 * Usage: php monthly_labour_market_update_no_city.php year{YYYY} month{1..12} < /path/to/input.csv > /path/to/output.csv
 */

$usage = "Usage: php monthly_labour_market_update year{YYYY} month{1..12} < /path/to/input.csv > /path/to/output.csv";
if (count($argv) < 3) {
    die("Expecting 2 arguments.\n" . $usage . PHP_EOL);
}
stream_set_blocking(STDIN, TRUE);
$csv = [];
while ($row = fgetcsv(STDIN)) {
    if (!is_array($row)) {
        die("Expecting CSV file in stdin.\n" . $usage . PHP_EOL);
    }
    $csv[] = $row;
}
$header_map = [
    'year' => $argv[1],
    'month' => $argv[2],

    'total_employed' => [1,1],
    'total_unemployed' => [30,1],
    'total_unemployed_previous' => [30,0],

    'employment_by_age_group_15_24' => [4,2],
    'employment_by_age_group_25_54' => [5,2],
    'employment_by_age_group_55' => [6,2],
    'employment_by_age_group_15_24_previous' => [4,1],
    'employment_by_age_group_25_54_previous' => [5,1],
    'employment_by_age_group_55_previous' => [6,1],

    'employment_by_gender_women' => [8,2],
    'employment_by_gender_men' => [9,2],
    'employment_by_gender_women_previous' => [8,1],
    'employment_by_gender_men_previous' => [9,1],

    'employment_change_pct_total_employment' => [13,1],
    'employment_change_abs_total_employment' => [13,2],
    'employment_change_pct_full_time_jobs' => [14,1],
    'employment_change_abs_full_time_jobs' => [14,2],
    'employment_change_pct_part_time_jobs' => [15,1],
    'employment_change_abs_part_time_jobs' => [15,2],

    'employment_rate_change_pct_unemployment' => [17,1],
    'employment_rate_pct_unemployment' => [17,2],
    'employment_rate_change_pct_participation' => [18,1],
    'employment_rate_pct_participation' => [18,2],

    'population_british_columbia' => [20,1],
    'population_vancouver_island_coast' => [21,1],
    'population_mainland_southwest' => [22,1],
    'population_thompson_okanagan' => [23,1],
    'population_kootenay' => [24,1],
    'population_cariboo' => [25,1],
    'population_north_coast_nechako' => [26,1],
    'population_northeast' => [27,1],

    'unemployment_pct_british_columbia' => [33,1],
    'unemployment_pct_british_columbia_previous' => [33,4],
    'total_jobs_british_columbia' => [33,2],
    'unemployment_pct_vancouver_island_coast' => [34,1],
    'unemployment_pct_vancouver_island_coast_previous' => [34,4],
    'total_jobs_vancouver_island_coast' => [34,2],
    'unemployment_pct_mainland_southwest' => [35,1],
    'unemployment_pct_mainland_southwest_previous' => [35,4],
    'total_jobs_mainland_southwest' => [35,2],
    'unemployment_pct_thompson_okanagan' => [36,1],
    'unemployment_pct_thompson_okanagan_previous' => [36,4],
    'total_jobs_thompson_okanagan' => [36,2],
    'unemployment_pct_kootenay' => [37,1],
    'unemployment_pct_kootenay_previous' => [37,4],
    'total_jobs_kootenay' => [37,2],
    'unemployment_pct_cariboo' => [38,1],
    'unemployment_pct_cariboo_previous' => [38,4],
    'total_jobs_cariboo' => [38,2],
    'unemployment_pct_north_coast_nechako' => [39,1],
    'unemployment_pct_north_coast_nechako_previous' => [39,4],
    'total_jobs_north_coast_nechako' => [39,2],
    'unemployment_pct_northeast' => [40,1],
    'unemployment_pct_northeast_previous' => [40,4],
    'total_jobs_northeast' => [40,2],

    'industry_pct_accommodation_food_services' => [43,1],
    'industry_abs_accommodation_food_services' => [43,2],
    'industry_pct_agriculture_fishing' => [44,1],
    'industry_abs_agriculture_fishing' => [44,2],
    'industry_pct_construction' => [45,1],
    'industry_abs_construction' => [45,2],
    'industry_pct_educational_services' => [46,1],
    'industry_abs_educational_services' => [46,2],
    'industry_pct_finance_insurance_real_estate' => [47,1],
    'industry_abs_finance_insurance_real_estate' => [47,2],
    'industry_pct_health_care_social_assistance' => [48,1],
    'industry_abs_health_care_social_assistance' => [48,2],
    'industry_pct_manufacturing' => [49,1],
    'industry_abs_manufacturing' => [49,2],
    'industry_pct_other_primary' => [50,1],
    'industry_abs_other_primary' => [50,2],
    'industry_pct_other_private_services' => [51,1],
    'industry_abs_other_private_services' => [51,2],
    'industry_pct_professional_scientific_technical_services' => [52,1],
    'industry_abs_professional_scientific_technical_services' => [52,2],
    'industry_pct_public_administration' => [53,1],
    'industry_abs_public_administration' => [53,2],
    'industry_pct_transportation_warehousing' => [54,1],
    'industry_abs_transportation_warehousing' => [54,2],
    'industry_pct_utilities' => [55,1],
    'industry_abs_utilities' => [55,2],
    'industry_pct_wholesale_retail_trade' => [56,1],
    'industry_abs_wholesale_retail_trade' => [56,2],
];
fputcsv(STDOUT, array_keys($header_map));
$output = [];
foreach ($header_map as $header => $location) {
    if (is_array($location)) {
        $value = str_replace(',', '', trim($csv[$location[0]][$location[1]]));
        if (empty($value) && !is_numeric($value)) {
            fwrite(STDERR, "Empty value found for {$header} at {$location[0]},{$location[1]}\n");
        }
        if (!is_numeric($value)) {
            fwrite(STDERR, "Non-numeric value found for {$header} at {$location[0]},{$location[1]}\n");
            $value = NULL;
        }
        $output[] = $value;
    }
    else {
        $output[] = $location;
    }
}
fputcsv(STDOUT, $output);
