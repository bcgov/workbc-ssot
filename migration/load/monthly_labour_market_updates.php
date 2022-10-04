<?php

/**
 * Transform the WorkBC LMS data into a format digestible by pgloader.
 *
 * Usage: php labour_market_update year{YYYY} month{1..12} < /path/to/input.csv > /path/to/output.csv
 */

$usage = "Usage: php labour_market_update year{YYYY} month{1..12} < /path/to/input.csv > /path/to/output.csv";
if (count($argv) < 3) {
    die("Expecting 2 arguments.\n" . $usage . PHP_EOL);
}
stream_set_blocking(STDIN, 0);
$csv = [];
while ($row = fgetcsv(STDIN)) {
    if (!is_array($row)) {
        die("Expecting CSV file in stdin.\n" . $usage. PHP_EOL);
    }
    $csv[] = $row;
}
$header_map = [
    'year' => $argv[1],
    'month' => $argv[2],
    'bc_employment_trends_change_pct_total_employment' => [2,1],
    'bc_employment_trends_change_abs_total_employment' => [2,2],
    'bc_employment_trends_change_pct_full_time_jobs' => [3,1],
    'bc_employment_trends_change_abs_full_time_jobs' => [3,2],
    'bc_employment_trends_change_pct_part_time_jobs' => [4,1],
    'bc_employment_trends_change_abs_part_time_jobs' => [4,2],
    'bc_employment_trends_rate_change_pct_unemployment' => [6,1],
    'bc_employment_trends_rate_pct_unemployment' => [6,2],
    'bc_employment_trends_rate_change_pct_participation' => [7,1],
    'bc_employment_trends_rate_pct_participation' => [7,2],
    'regional_population_british_columbia' => [10,1],
    'regional_population_vancouver_island_and_coast' => [11,1],
    'regional_population_lower_mainland_southwest' => [12,1],
    'regional_population_thompson_okanagan' => [13,1],
    'regional_population_kootenay' => [14,1],
    'regional_population_cariboo' => [15,1],
    'regional_population_north_coast_and_nechako' => [16,1],
    'regional_population_northeast' => [17,1],
    'regional_unemployment_rate_pct_british_columbia' => [20,1],
    'regional_unemployment_total_british_columbia' => [20,2],
    'regional_unemployment_rate_pct_vancouver_island_coast' => [21,1],
    'regional_unemployment_total_vancouver_island_coast' => [21,2],
    'regional_unemployment_rate_pct_mainland_southwest' => [22,1],
    'regional_unemployment_total_mainland_southwest' => [22,2],
    'regional_unemployment_rate_pct_thompson_okanagan' => [23,1],
    'regional_unemployment_total_thompson_okanagan' => [23,2],
    'regional_unemployment_rate_pct_kootenay' => [24,1],
    'regional_unemployment_total_kootenay' => [24,2],
    'regional_unemployment_rate_pct_cariboo' => [25,1],
    'regional_unemployment_total_cariboo' => [25,2],
    'regional_unemployment_rate_pct_north_coast_and_nechako' => [26,1],
    'regional_unemployment_total_north_coast_and_nechako' => [26,2],
    'regional_unemployment_rate_pct_northeast' => [27,1],
    'regional_unemployment_total_northeast' => [27,2],
    'city_unemployment_rate_pct_kelowna' => [30,1],
    'city_unemployment_rate_pct_abbotsford_mission' => [31,1],
    'city_unemployment_rate_pct_vancouver' => [32,1],
    'city_unemployment_rate_pct_victoria' => [33,1],
    'industry_highlights_pct_accommodation_and_food_services' => [38,1],
    'industry_highlights_abs_accommodation_and_food_services' => [38,2],
    'industry_highlights_pct_agriculture' => [39,1],
    'industry_highlights_abs_agriculture' => [39,2],
    'industry_highlights_pct_construction' => [40,1],
    'industry_highlights_abs_construction' => [40,2],
    'industry_highlights_pct_educational_services' => [41,1],
    'industry_highlights_abs_educational_services' => [41,2],
    'industry_highlights_pct_finance_insurance_real_estate_rental' => [42,1],
    'industry_highlights_abs_finance_insurance_real_estate_rental' => [42,2],
    'industry_highlights_pct_health_care_and_social_assistance' => [43,1],
    'industry_highlights_abs_health_care_and_social_assistance' => [43,2],
    'industry_highlights_pct_manufacturing' => [44,1],
    'industry_highlights_abs_manufacturing' => [44,2],
    'industry_highlights_pct_other_primary' => [45,1],
    'industry_highlights_abs_other_primary' => [45,2],
    'industry_highlights_pct_other_services' => [46,1],
    'industry_highlights_abs_other_services' => [46,2],
    'industry_highlights_pct_professional_scientific_and_technical' => [47,1],
    'industry_highlights_abs_professional_scientific_and_technical' => [47,2],
    'industry_highlights_pct_public_administration' => [48,1],
    'industry_highlights_abs_public_administration' => [48,2],
    'industry_highlights_pct_transportation_and_warehousing' => [49,1],
    'industry_highlights_abs_transportation_and_warehousing' => [49,2],
    'industry_highlights_pct_utilities' => [50,1],
    'industry_highlights_abs_utilities' => [50,2],
    'industry_highlights_pct_wholesale_and_retail_trade' => [51,1],
    'industry_highlights_abs_wholesale_and_retail_trade' => [51,2],
];
fputcsv(STDOUT, array_keys($header_map));
$output = [];
foreach ($header_map as $header => $location) {
    if (is_array($location)) {
        $output[] = $csv[$location[0]][$location[1]];
    }
    else {
        $output[] = $location;
    }
}
fputcsv(STDOUT, $output);
