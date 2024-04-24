<?php

/**
 * Transform the NOC 2021 concordance tables into a single CSV suitable for import.
 * https://noc.esdc.gc.ca/Versions/ConcordanceTables
 *
 * Usage: php nocs.php /path/to/concordance/files/ > /path/to/output.csv
 */

$usage = "Usage: php nocs.php /path/to/concordance/files/ > /path/to/output.csv";
if (count($argv) < 2) {
    fwrite(STDERR, "Expecting 1 argument.\n" . $usage . PHP_EOL);
}
$dirname = rtrim($argv[1], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

const COLUMN_OUTPUT_NOC_2021 = 0;
const COLUMN_OUTPUT_ENGLISH_LABEL = 1;
const COLUMN_OUTPUT_ENGLISH_DEFINITION = 2;
const COLUMN_OUTPUT_FRENCH_LABEL = 3;
const COLUMN_OUTPUT_FRENCH_DEFINITION = 4;
const COLUMN_OUTPUT_TYPE = 5;
const COLUMN_OUTPUT_TEER_LEVEL = 6;
const COLUMN_OUTPUT_PARENT_NOC = 7;
const COLUMN_OUTPUT_NOC_2016 = 8;

const CSV_STRUCTURE_ENGLISH = 'noc_2021_version_1.0_-_classification_structure.csv';
const CSV_STRUCTURE_FRENCH = 'cnp_2021_version_1.0_-_structure_de_la_classification.csv';
const COLUMN_STRUCTURE_LEVEL = 0;
const COLUMN_STRUCTURE_LEVELNAME = 1;
const COLUMN_STRUCTURE_CODE = 2;
const COLUMN_STRUCTURE_TITLE = 3;
const COLUMN_STRUCTURE_DEFINITION = 4;

const NOC_TYPE_BROAD_CATEGORY = 1;
const NOC_TYPE_MAJOR_GROUP = 2;
const NOC_TYPE_SUBMAJOR_GROUP = 3;
const NOC_TYPE_MINOR_GROUP = 4;
const NOC_TYPE_UNIT_GROUP = 5;

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

// Read overall structure.
$en = fopen_or_die(CSV_STRUCTURE_ENGLISH); fgetcsv($en);
$fr = fopen_or_die(CSV_STRUCTURE_FRENCH); fgetcsv($fr);
$row = 2;
$structure_en = [];
$structure_fr = [];
while (FALSE !== ($row_en = fgetcsv($en))) {
    $row_fr = fgetcsv($fr);
    if (
        FALSE === $row_fr ||
        $row_fr[COLUMN_STRUCTURE_CODE] !== $row_en[COLUMN_STRUCTURE_CODE]
    ) {
        fwrite(STDERR, "Unexpected mismatch between English and French concordances at " . CSV_STRUCTURE_FRENCH . " row $row. Aborting.\n");
        exit(1);
    }
    $structure_en[$row_en[COLUMN_STRUCTURE_CODE]] = $row_en;
    $structure_fr[$row_fr[COLUMN_STRUCTURE_CODE]] = $row_fr;
}

// Start output.
output_broad_categories();
output_major_groups();
output_submajor_groups();
output_minor_groups();
output_unit_groups();

function output_broad_categories() {
    global $structure_en;
    global $structure_fr;
    $en = fopen_or_die(CSV_BROAD_CATEGORIES_ENGLISH); fgetcsv($en);
    $fr = fopen_or_die(CSV_BROAD_CATEGORIES_FRENCH); fgetcsv($fr);
    $row = 2;
    while (FALSE !== ($row_en = fgetcsv($en))) {
        $row_fr = fgetcsv($fr);
        if (
            FALSE === $row_fr ||
            $row_fr[COLUMN_BROAD_CATEGORIES_NOC_2021] !== $row_en[COLUMN_BROAD_CATEGORIES_NOC_2021]
        ) {
            fwrite(STDERR, "Unexpected mismatch between English and French concordances at " . CSV_BROAD_CATEGORIES_FRENCH . " row $row. Aborting.\n");
            exit(1);
        }
        if (!array_key_exists($row_en[COLUMN_BROAD_CATEGORIES_NOC_2021], $structure_en)) {
            fwrite(STDERR, "NOC " . $row_en[COLUMN_BROAD_CATEGORIES_NOC_2021] . " not found at " . CSV_STRUCTURE_ENGLISH . ". Ignoring definitions.\n");
            $definition_en = NULL;
            $definition_fr = NULL;
        }
        else {
            $definition_en = $structure_en[$row_en[COLUMN_BROAD_CATEGORIES_NOC_2021]][COLUMN_STRUCTURE_DEFINITION];
            $definition_fr = $structure_fr[$row_en[COLUMN_BROAD_CATEGORIES_NOC_2021]][COLUMN_STRUCTURE_DEFINITION];
        }
        fputcsv(STDOUT, [
            $row_en[COLUMN_BROAD_CATEGORIES_NOC_2021],
            $row_en[COLUMN_BROAD_CATEGORIES_NOC_2021_LABEL],
            $definition_en,
            $row_fr[COLUMN_BROAD_CATEGORIES_NOC_2021_LABEL],
            $definition_fr,
            NOC_TYPE_BROAD_CATEGORY,
            NULL,
            NULL,
            $row_en[COLUMN_BROAD_CATEGORIES_NOC_2016],
        ]);
        $row++;
    }
}

function output_major_groups() {
    global $structure_en;
    global $structure_fr;
    $en = fopen_or_die(CSV_MAJOR_GROUPS_ENGLISH); fgetcsv($en);
    $fr = fopen_or_die(CSV_MAJOR_GROUPS_FRENCH); fgetcsv($fr);
    $row = 2;
    while (FALSE !== ($row_en = fgetcsv($en))) {
        $row_fr = fgetcsv($fr);
        if (
            FALSE === $row_fr ||
            $row_fr[COLUMN_MAJOR_GROUPS_NOC_2021] !== $row_en[COLUMN_MAJOR_GROUPS_NOC_2021]
        ) {
            fwrite(STDERR, "Unexpected mismatch between English and French concordances at " . CSV_MAJOR_GROUPS_FRENCH . " row $row. Aborting.\n");
            exit(1);
        }
        $noc2021 = str_pad($row_en[COLUMN_MAJOR_GROUPS_NOC_2021], 2, '0', STR_PAD_LEFT);
        if (!array_key_exists($noc2021, $structure_en)) {
            fwrite(STDERR, "NOC " . $noc2021 . " not found at " . CSV_STRUCTURE_ENGLISH . ". Ignoring definitions.\n");
            $definition_en = NULL;
            $definition_fr = NULL;
        }
        else {
            $definition_en = $structure_en[$noc2021][COLUMN_STRUCTURE_DEFINITION];
            $definition_fr = $structure_fr[$noc2021][COLUMN_STRUCTURE_DEFINITION];
        }
        $noc2016 = empty($row_en[COLUMN_MAJOR_GROUPS_NOC_2016]) && $row_en[COLUMN_MAJOR_GROUPS_NOC_2016] !== '0' ? NULL : str_pad($row_en[COLUMN_MAJOR_GROUPS_NOC_2016], 2, '0', STR_PAD_LEFT);
        fputcsv(STDOUT, [
            $noc2021,
            $row_en[COLUMN_MAJOR_GROUPS_NOC_2021_LABEL],
            $definition_en,
            $row_fr[COLUMN_MAJOR_GROUPS_NOC_2021_LABEL],
            $definition_fr,
            NOC_TYPE_MAJOR_GROUP,
            $row_en[COLUMN_MAJOR_GROUPS_NOC_2021_TEER],
            substr($noc2021, 0, 1),
            $noc2016
        ]);
        $row++;
    }
}

function output_submajor_groups() {
    global $structure_en;
    global $structure_fr;

    // There's no concordance for this level - read everything from the structure.
    foreach ($structure_en as $row_en) {
        if ($row_en[COLUMN_STRUCTURE_LEVEL] != NOC_TYPE_SUBMAJOR_GROUP) continue;
        $noc2021 = $row_en[COLUMN_STRUCTURE_CODE];
        $row_fr = $structure_fr[$noc2021];
        fputcsv(STDOUT, [
            $noc2021,
            $row_en[COLUMN_STRUCTURE_TITLE],
            $row_en[COLUMN_STRUCTURE_DEFINITION],
            $row_fr[COLUMN_STRUCTURE_TITLE],
            $row_fr[COLUMN_STRUCTURE_DEFINITION],
            NOC_TYPE_SUBMAJOR_GROUP,
            NULL,
            substr($noc2021, 0, 2),
            NULL
        ]);
    }
}

function output_minor_groups() {
    global $structure_en;
    global $structure_fr;
    $en = fopen_or_die(CSV_MINOR_GROUPS_ENGLISH); fgetcsv($en);
    $fr = fopen_or_die(CSV_MINOR_GROUPS_FRENCH); fgetcsv($fr);
    $row = 2;
    while (FALSE !== ($row_en = fgetcsv($en))) {
        $row_fr = fgetcsv($fr);
        if (
            FALSE === $row_fr ||
            $row_fr[COLUMN_MINOR_GROUPS_NOC_2021] !== $row_en[COLUMN_MINOR_GROUPS_NOC_2021]
        ) {
            fwrite(STDERR, "Unexpected mismatch between English and French concordances at " . CSV_MINOR_GROUPS_FRENCH . " row $row. Aborting.\n");
            exit(1);
        }
        $noc2021 = str_pad($row_en[COLUMN_MINOR_GROUPS_NOC_2021], 4, '0', STR_PAD_LEFT);
        if (!array_key_exists($noc2021, $structure_en)) {
            fwrite(STDERR, "NOC " . $noc2021 . " not found at " . CSV_STRUCTURE_ENGLISH . ". Ignoring definitions.\n");
            $definition_en = NULL;
            $definition_fr = NULL;
        }
        else {
            $definition_en = $structure_en[$noc2021][COLUMN_STRUCTURE_DEFINITION];
            $definition_fr = $structure_fr[$noc2021][COLUMN_STRUCTURE_DEFINITION];
        }
        $noc2016 = empty($row_en[COLUMN_MINOR_GROUPS_NOC_2016]) ? NULL : str_pad($row_en[COLUMN_MINOR_GROUPS_NOC_2016], 3, '0', STR_PAD_LEFT);
        fputcsv(STDOUT, [
            $noc2021,
            $row_en[COLUMN_MINOR_GROUPS_NOC_2021_LABEL],
            $definition_en,
            $row_fr[COLUMN_MINOR_GROUPS_NOC_2021_LABEL],
            $definition_fr,
            NOC_TYPE_MINOR_GROUP,
            $row_en[COLUMN_MINOR_GROUPS_NOC_2021_TEER],
            substr($noc2021, 0, 3),
            $noc2016
        ]);
        $row++;
    }
}

function output_unit_groups() {
    global $structure_en;
    global $structure_fr;
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
            fwrite(STDERR, "Unexpected mismatch between English and French concordances at " . CSV_UNIT_GROUPS_FRENCH . " row $row. Aborting.\n");
            exit(1);
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
            if (!array_key_exists($noc2021, $structure_en)) {
                fwrite(STDERR, "NOC " . $noc2021 . " not found at " . CSV_STRUCTURE_ENGLISH . ". Ignoring definitions.\n");
                $definition_en = NULL;
                $definition_fr = NULL;
            }
            else {
                $definition_en = $structure_en[$noc2021][COLUMN_STRUCTURE_DEFINITION];
                $definition_fr = $structure_fr[$noc2021][COLUMN_STRUCTURE_DEFINITION];
            }
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
                    $definition_en,
                    $row_fr[COLUMN_UNIT_GROUPS_NOC_2021_LABEL],
                    $definition_fr,
                    NOC_TYPE_UNIT_GROUP,
                    $row_en[COLUMN_UNIT_GROUPS_NOC_2021_TEER],
                    substr($noc2021, 0, 4),
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
    if (!$fh) {
        fwrite(STDERR, "File $path not found. Aborting.\n");
        exit(1);
    }
    return $fh;
}
