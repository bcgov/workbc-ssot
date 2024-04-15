<?php

/**
* Transform the EDM NAICS CSV dumps into a single CSV suitable for import.
*
* Usage: php industries.php /path/to/EDM/files/ > /path/to/output.csv
*/

$usage = "Usage: php industries.php /path/to/EDM/files/ > /path/to/output.csv";
if (count($argv) < 2) {
    die("Expecting 1 argument.\n" . $usage . PHP_EOL);
}
$dirname = rtrim($argv[1], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

const CSV_NAICS = 'EDM_NAICS.csv';
const COLUMN_NAICS_ID = 0;
const COLUMN_NAICS_SECTOR = 1;
const COLUMN_NAICS_TYPE = 2;
const COLUMN_NAICS_ENABLED = 3;
const CSV_NAICSCODES = 'EDM_NAICSCodes.csv';
const COLUMN_NAICSCODES_ID = 0;
const COLUMN_NAICSCODES_CODE = 1;

const INDUSTRY_MAP = [
    'accommodation and food services' => 'accommodation_food_services',
    'agriculture and fishing' => 'agriculture_fishing',
    'business, building and other support services' => 'business_building_other_support_services',
    'construction' => 'construction',
    'educational services' => 'educational_services',
    'finance, insurance and real estate' => 'finance_insurance_real_estate',
    'fishing, hunting and trapping' => 'fishing_hunting_trapping',
    'forestry, logging and support activities' => 'forestry_logging_support_activities',
    'health care and social assistance' => 'health_care_social_assistance',
    'information, culture and recreation' => 'information_culture_recreation',
    'manufacturing' => 'manufacturing',
    'utilities' => 'utilities',
    'mining and oil and gas extraction' => 'mining_oil_gas_extraction',
    'repair, personal and non-profit services' => 'other_private_services',
    'professional, scientific and technical services' => 'professional_scientific_technical_services',
    'public administration' => 'public_administration',
    'transportation and warehousing' => 'transportation_warehousing',
    'wholesale trade' => 'wholesale_trade',
    'retail trade' => 'retail_trade'
];

$naics = fopen_or_die(CSV_NAICS); fgetcsv($naics);
$naics_codes = fopen_or_die(CSV_NAICSCODES); fgetcsv($naics_codes);

// Read NAICS mapping into map.
$naics_map = [];
while (FALSE !== ($row = fgetcsv($naics_codes))) {
    $naics_id = $row[COLUMN_NAICSCODES_ID];
    if (!array_key_exists($naics_id, $naics_map)) {
        $naics_map[$naics_id] = $row[COLUMN_NAICSCODES_CODE];
    }
    else {
        $naics_map[$naics_id] .= "," . $row[COLUMN_NAICSCODES_CODE];
    }
}

// Read the NAICS list and output our industries.
while (FALSE !== ($row = fgetcsv($naics))) {
    if (!$row[COLUMN_NAICS_ID] || !$row[COLUMN_NAICS_ENABLED]) continue;
    $naics_id = $row[COLUMN_NAICS_ID];
    $industry_entry = strtolower($row[COLUMN_NAICS_SECTOR]);
    if (!array_key_exists($industry_entry, INDUSTRY_MAP)) {
        fwrite(STDERR, "Unexpected industry entry \"" . $row[COLUMN_NAICS_SECTOR] . "\". Aborting.\n");
        exit(1);
    }
    fputcsv(STDOUT, [
        INDUSTRY_MAP[$industry_entry],
        $row[COLUMN_NAICS_SECTOR],
        $row[COLUMN_NAICS_TYPE],
        $naics_map[$naics_id] ?? null
    ]);
}

function fopen_or_die($filename) {
    global $dirname;
    $path = $dirname . $filename;
    $fh = fopen($path, 'r');
    if (!$fh) {
        fwrite(STDERR, "File $path not found. Aborting.\n");
        exit(1);
    }
    return $fh;
}
