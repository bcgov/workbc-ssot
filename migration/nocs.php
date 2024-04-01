<?php

/**
 * Transform the NOC 2021 concordance tables into a single CSV suitable for import.
 * https://noc.esdc.gc.ca/Versions/ConcordanceTables
 *
 * Usage: php nocs.php /path/to/concordance/files/ > /path/to/output.csv
 */

$usage = "Usage: php nocs.php /path/to/concordance/files/ > /path/to/output.csv";
if (count($argv) < 2) {
    die("Expecting 1 argument.\n" . $usage . PHP_EOL);
}
$dirname = rtrim($argv[1], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

const COLUMN_OUTPUT_NOC_2021 = 0;
const COLUMN_OUTPUT_ENGLISH_LABEL = 1;
const COLUMN_OUTPUT_FRENCH_LABEL = 2;
const COLUMN_OUTPUT_TYPE = 3;
const COLUMN_OUTPUT_TEER_LEVEL = 4;
const COLUMN_OUTPUT_PARENT_NOC = 5;
const COLUMN_OUTPUT_NOC_2016 = 6;
const NOC_TYPE_BROAD_CATEGORY = 1;
const NOC_TYPE_MAJOR_GROUP = 2;
const NOC_TYPE_MINOR_GROUP = 3;
const NOC_TYPE_UNIT_GROUP = 4;
const CSV_BROAD_CATEGORIES_ENGLISH = 'noc2021_broad_occupational_categories.csv';
const CSV_BROAD_CATEGORIES_FRENCH = 'noc2021_broad_occupational_categories.fr.csv';
const COLUMN_BROAD_CATEGORIES_NOC_2016 = 0;
const COLUMN_BROAD_CATEGORIES_NOC_2021 = 2;
const COLUMN_BROAD_CATEGORIES_NOC_2021_LABEL = 3;
const CSV_MAJOR_GROUPS_ENGLISH = 'noc2021_major_groups.csv';
const CSV_MAJOR_GROUPS_FRENCH = 'noc2021_major_groups.fr.csv';
const COLUMN_MAJOR_GROUPS_NOC_2016 = 0;
const COLUMN_MAJOR_GROUPS_NOC_2021 = 3;
const COLUMN_MAJOR_GROUPS_NOC_2021_LABEL = 4;
const COLUMN_MAJOR_GROUPS_NOC_2021_TEER = 5;
const CSV_MINOR_GROUPS_ENGLISH = 'noc2021_minor_groups.csv';
const CSV_MINOR_GROUPS_FRENCH = 'noc2021_minor_groups.fr.csv';
const COLUMN_MINOR_GROUPS_NOC_2016 = 0;
const COLUMN_MINOR_GROUPS_NOC_2021 = 3;
const COLUMN_MINOR_GROUPS_NOC_2021_LABEL = 4;
const COLUMN_MINOR_GROUPS_NOC_2021_TEER = 5;
const CSV_UNIT_GROUPS_ENGLISH = 'noc2021_unit_groups.csv';
const CSV_UNIT_GROUPS_FRENCH = 'noc2021_unit_groups.fr.csv';
const COLUMN_UNIT_GROUPS_NOC_2016 = 3;
const COLUMN_UNIT_GROUPS_NOC_2021 = 0;
const COLUMN_UNIT_GROUPS_NOC_2021_LABEL = 1;
const COLUMN_UNIT_GROUPS_NOC_2021_TEER = 2;

// Start output.
output_broad_categories();
output_major_groups();
output_minor_groups();
output_unit_groups();

function output_broad_categories() {
    $en = fopen_or_die(CSV_BROAD_CATEGORIES_ENGLISH); fgetcsv($en);
    $fr = fopen_or_die(CSV_BROAD_CATEGORIES_FRENCH); fgetcsv($fr);
    $row = 2;
    while (FALSE !== ($row_en = fgetcsv($en))) {
        $row_fr = fgetcsv($fr);
        if (
            FALSE === $row_fr ||
            $row_fr[COLUMN_BROAD_CATEGORIES_NOC_2021] !== $row_en[COLUMN_BROAD_CATEGORIES_NOC_2021]
        ) {
            die("Unexpected mismatch between English and French concordances at " . CSV_BROAD_CATEGORIES_FRENCH . " row $row. Aborting.\n");
        }
        fputcsv(STDOUT, [
            $row_en[COLUMN_BROAD_CATEGORIES_NOC_2021],
            $row_en[COLUMN_BROAD_CATEGORIES_NOC_2021_LABEL],
            $row_fr[COLUMN_BROAD_CATEGORIES_NOC_2021_LABEL],
            NOC_TYPE_BROAD_CATEGORY,
            NULL,
            NULL,
            $row_en[COLUMN_BROAD_CATEGORIES_NOC_2016],
        ]);
        $row++;
    }
}

function output_major_groups() {
    $en = fopen_or_die(CSV_MAJOR_GROUPS_ENGLISH); fgetcsv($en);
    $fr = fopen_or_die(CSV_MAJOR_GROUPS_FRENCH); fgetcsv($fr);
    $row = 2;
    while (FALSE !== ($row_en = fgetcsv($en))) {
        $row_fr = fgetcsv($fr);
        if (
            FALSE === $row_fr ||
            $row_fr[COLUMN_MAJOR_GROUPS_NOC_2021] !== $row_en[COLUMN_MAJOR_GROUPS_NOC_2021]
        ) {
            die("Unexpected mismatch between English and French concordances at " . CSV_MAJOR_GROUPS_FRENCH . " row $row. Aborting.\n");
        }
        $noc2021 = str_pad($row_en[COLUMN_MAJOR_GROUPS_NOC_2021], 2, '0', STR_PAD_LEFT);
        $noc2016 = empty($row_en[COLUMN_MAJOR_GROUPS_NOC_2016]) ? NULL : str_pad($row_en[COLUMN_MAJOR_GROUPS_NOC_2016], 2, '0', STR_PAD_LEFT);
        fputcsv(STDOUT, [
            $noc2021,
            $row_en[COLUMN_MAJOR_GROUPS_NOC_2021_LABEL],
            $row_fr[COLUMN_MAJOR_GROUPS_NOC_2021_LABEL],
            NOC_TYPE_MAJOR_GROUP,
            $row_en[COLUMN_MAJOR_GROUPS_NOC_2021_TEER],
            substr($noc2021, 0, 1),
            $noc2016
        ]);
        $row++;
    }
}

function output_minor_groups() {
    $en = fopen_or_die(CSV_MINOR_GROUPS_ENGLISH); fgetcsv($en);
    $fr = fopen_or_die(CSV_MINOR_GROUPS_FRENCH); fgetcsv($fr);
    $row = 2;
    while (FALSE !== ($row_en = fgetcsv($en))) {
        $row_fr = fgetcsv($fr);
        if (
            FALSE === $row_fr ||
            $row_fr[COLUMN_MINOR_GROUPS_NOC_2021] !== $row_en[COLUMN_MINOR_GROUPS_NOC_2021]
        ) {
            die("Unexpected mismatch between English and French concordances at " . CSV_MINOR_GROUPS_FRENCH . " row $row. Aborting.\n");
        }
        $noc2021 = str_pad($row_en[COLUMN_MINOR_GROUPS_NOC_2021], 4, '0', STR_PAD_LEFT);
        $noc2016 = empty($row_en[COLUMN_MINOR_GROUPS_NOC_2016]) ? NULL : str_pad($row_en[COLUMN_MINOR_GROUPS_NOC_2016], 3, '0', STR_PAD_LEFT);
        fputcsv(STDOUT, [
            $noc2021,
            $row_en[COLUMN_MINOR_GROUPS_NOC_2021_LABEL],
            $row_fr[COLUMN_MINOR_GROUPS_NOC_2021_LABEL],
            NOC_TYPE_MINOR_GROUP,
            $row_en[COLUMN_MINOR_GROUPS_NOC_2021_TEER],
            substr($noc2021, 0, 2),
            $noc2016
        ]);
        $row++;
    }
}

function output_unit_groups() {
    $en = fopen_or_die(CSV_UNIT_GROUPS_ENGLISH); fgetcsv($en);
    $fr = fopen_or_die(CSV_UNIT_GROUPS_FRENCH); fgetcsv($fr);
    $row = 2;
    $current_noc = [];
    while (FALSE !== ($row_en = fgetcsv($en))) {
        $row_fr = fgetcsv($fr);
        if (
            FALSE === $row_fr ||
            $row_fr[COLUMN_UNIT_GROUPS_NOC_2021] !== $row_en[COLUMN_UNIT_GROUPS_NOC_2021]
        ) {
            die("Unexpected mismatch between English and French concordances at " . CSV_UNIT_GROUPS_FRENCH . " row $row. Aborting.\n");
        }

        $noc2016 = empty($row_en[COLUMN_UNIT_GROUPS_NOC_2016]) ? NULL : str_pad($row_en[COLUMN_UNIT_GROUPS_NOC_2016], 4, '0', STR_PAD_LEFT);

        // If NOC 2021 is empty, add the incoming NOC 2016 to the current NOC.
        if (empty($row_en[COLUMN_UNIT_GROUPS_NOC_2021])) {
            $current_noc[COLUMN_OUTPUT_NOC_2016] .= ',' . $noc2016;
        }
        else {
            // Done with previous NOC: flush it.
            if (!empty($current_noc)) fputcsv(STDOUT, $current_noc);

            // Start a new NOC.
            // Special cases: 00011 -> 00018, ignore 00012-00015
            $noc2021 = str_pad($row_en[COLUMN_UNIT_GROUPS_NOC_2021], 5, '0', STR_PAD_LEFT);
            if ($noc2021 === '00011') {
                $noc2021 = '00018';
                $row_en[COLUMN_UNIT_GROUPS_NOC_2021_LABEL] = 'Senior managers - public and private sector';
                $row_fr[COLUMN_UNIT_GROUPS_NOC_2021_LABEL] = 'Cadres supérieurs / cadres supérieures - secteur public et privé';
            }
            if (in_array($noc2021, [
                '00012', '00013', '00014', '00015'
            ])) {
                $current_noc = [];
            } else {
                $current_noc = [
                    $noc2021,
                    $row_en[COLUMN_UNIT_GROUPS_NOC_2021_LABEL],
                    $row_fr[COLUMN_UNIT_GROUPS_NOC_2021_LABEL],
                    NOC_TYPE_UNIT_GROUP,
                    $row_en[COLUMN_UNIT_GROUPS_NOC_2021_TEER],
                    substr($noc2021, 0, 3),
                    $noc2016
                ];
            }
        }
        $row++;
    }

    // Flush last NOC.
    if (!empty($current_noc)) fputcsv(STDOUT, $current_noc);
}

function fopen_or_die($filename) {
    global $dirname;
    $path = $dirname . $filename;
    $fh = fopen($path, 'r');
    if (!$fh) die("File $path not found. Aborting.\n");
    return $fh;
}
