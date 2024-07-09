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

const NOC_LEVEL_BROAD_CATEGORY = 1;
const NOC_LEVEL_MAJOR_GROUP = 2;
const NOC_LEVEL_SUBMAJOR_GROUP = 3;
const NOC_LEVEL_MINOR_GROUP = 4;
const NOC_LEVEL_UNIT_GROUP = 5;

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

const CSV_CONCORDANCE_2006_2011 = 'noc2011_unit_groups.csv';
const COLUMN_CONCORDANCE_NOC_2006 = 0;
const COLUMN_CONCORDANCE_NOC_2011 = 2;

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

// Build 2006-2011 concordance.
// During output of unit groups, we will insert all concording NOC 2006 for each incoming NOC 2016/2011.
// To build the concordance:
// - Map each NOC 2006 to all its NOC 2011
// - Remove any entry where NOC 2006 == NOC 2011
// - Invert the dictionary to map NOC 2011 to all its NOC 2006
$en = fopen_or_die(CSV_CONCORDANCE_2006_2011); fgetcsv($en);
$concordance_2006_2011 = [];
while (FALSE !== ($row_en = fgetcsv($en))) {
    if (empty($row_en[COLUMN_CONCORDANCE_NOC_2006])) continue;
    $noc2011 = str_pad($row_en[COLUMN_CONCORDANCE_NOC_2011], 4, '0', STR_PAD_LEFT);
    $noc2006 = str_pad($row_en[COLUMN_CONCORDANCE_NOC_2006], 4, '0', STR_PAD_LEFT);
    if (!array_key_exists($noc2006, $concordance_2006_2011)) {
        $concordance_2006_2011[$noc2006] = [$noc2011];
    }
    else {
        $concordance_2006_2011[$noc2006][] = $noc2011;
    }
}
$concordance_2006_2011 = array_filter($concordance_2006_2011, function($noc2011s, $noc2006) {
    return array_search($noc2006, $noc2011s) === false;
}, ARRAY_FILTER_USE_BOTH);
$concordance_2011_2006 = array_reduce(array_keys($concordance_2006_2011), function($concordance_2011_2006, $noc2006) use($concordance_2006_2011) {
    foreach ($concordance_2006_2011[$noc2006] as $noc2011) {
        if (!array_key_exists($noc2011, $concordance_2011_2006)) {
            $concordance_2011_2006[$noc2011] = [$noc2006];
        }
        else {
            $concordance_2011_2006[$noc2011][] = $noc2006;
        }
    }
    return $concordance_2011_2006;
}, []);

// Start output.
output_broad_categories();
output_major_groups();
output_submajor_groups();
output_minor_groups();
output_unit_groups($concordance_2011_2006);

function output_broad_categories() {
    global $structure_en;
    global $structure_fr;
    $concordance_en = array_map('str_getcsv', file_or_die(CSV_BROAD_CATEGORIES_ENGLISH)); array_shift($concordance_en);
    foreach ($structure_en as $row_en) {
        if ($row_en[COLUMN_STRUCTURE_LEVEL] != NOC_LEVEL_BROAD_CATEGORY) continue;
        $row_fr = $structure_fr[$row_en[COLUMN_STRUCTURE_CODE]];
        $noc2021 = str_pad($row_en[COLUMN_STRUCTURE_CODE], NOC_LEVEL_BROAD_CATEGORY, '0', STR_PAD_LEFT);

        // Find all NOC 2016 codes and TEER relating to this one in the concordance table.
        $noc2016 = join(',', array_reduce($concordance_en, function($noc2016, $entry_en) use($row_en) {
            if ($row_en[COLUMN_STRUCTURE_CODE] == $entry_en[COLUMN_BROAD_CATEGORIES_NOC_2021]) {
                if (strlen($entry_en[COLUMN_BROAD_CATEGORIES_NOC_2016])) {
                    $noc2016[] = str_pad($entry_en[COLUMN_BROAD_CATEGORIES_NOC_2016], NOC_LEVEL_BROAD_CATEGORY, '0', STR_PAD_LEFT);
                }
            }
            return $noc2016;
        }, []));

        // Write out the record.
        fputcsv(STDOUT, [
            $noc2021,
            $row_en[COLUMN_STRUCTURE_TITLE],
            $row_en[COLUMN_STRUCTURE_DEFINITION],
            $row_fr[COLUMN_STRUCTURE_TITLE],
            $row_fr[COLUMN_STRUCTURE_DEFINITION],
            NOC_LEVEL_BROAD_CATEGORY,
            NULL,
            NULL,
            $noc2016,
        ]);
    }
}

function output_major_groups() {
    global $structure_en;
    global $structure_fr;
    $concordance_en = array_map('str_getcsv', file_or_die(CSV_MAJOR_GROUPS_ENGLISH)); array_shift($concordance_en);
    foreach ($structure_en as $row_en) {
        if ($row_en[COLUMN_STRUCTURE_LEVEL] != NOC_LEVEL_MAJOR_GROUP) continue;
        $row_fr = $structure_fr[$row_en[COLUMN_STRUCTURE_CODE]];
        $noc2021 = str_pad($row_en[COLUMN_STRUCTURE_CODE], NOC_LEVEL_MAJOR_GROUP, '0', STR_PAD_LEFT);

        // Find all NOC 2016 codes and TEER relating to this one in the concordance table.
        $teer = NULL;
        $noc2016 = join(',', array_reduce($concordance_en, function($noc2016, $entry_en) use($row_en, &$teer) {
            if ($row_en[COLUMN_STRUCTURE_CODE] == $entry_en[COLUMN_MAJOR_GROUPS_NOC_2021]) {
                if (strlen($entry_en[COLUMN_MAJOR_GROUPS_NOC_2016])) {
                    $noc2016[] = str_pad($entry_en[COLUMN_MAJOR_GROUPS_NOC_2016], NOC_LEVEL_MAJOR_GROUP, '0', STR_PAD_LEFT);
                }
                $teer = $entry_en[COLUMN_MAJOR_GROUPS_NOC_2021_TEER];
            }
            return $noc2016;
        }, []));

        // Write out the record.
        fputcsv(STDOUT, [
            $noc2021,
            $row_en[COLUMN_STRUCTURE_TITLE],
            $row_en[COLUMN_STRUCTURE_DEFINITION],
            $row_fr[COLUMN_STRUCTURE_TITLE],
            $row_fr[COLUMN_STRUCTURE_DEFINITION],
            NOC_LEVEL_MAJOR_GROUP,
            $teer,
            substr($noc2021, 0, NOC_LEVEL_MAJOR_GROUP-1),
            $noc2016,
        ]);
    }
}

function output_submajor_groups() {
    global $structure_en;
    global $structure_fr;

    // There's no concordance for this level - read everything from the structure.
    foreach ($structure_en as $row_en) {
        if ($row_en[COLUMN_STRUCTURE_LEVEL] != NOC_LEVEL_SUBMAJOR_GROUP) continue;
        $row_fr = $structure_fr[$row_en[COLUMN_STRUCTURE_CODE]];
        $noc2021 = str_pad($row_en[COLUMN_STRUCTURE_CODE], NOC_LEVEL_SUBMAJOR_GROUP, '0', STR_PAD_LEFT);
        fputcsv(STDOUT, [
            $noc2021,
            $row_en[COLUMN_STRUCTURE_TITLE],
            $row_en[COLUMN_STRUCTURE_DEFINITION],
            $row_fr[COLUMN_STRUCTURE_TITLE],
            $row_fr[COLUMN_STRUCTURE_DEFINITION],
            NOC_LEVEL_SUBMAJOR_GROUP,
            NULL,
            substr($noc2021, 0, NOC_LEVEL_SUBMAJOR_GROUP-1),
            NULL
        ]);
    }
}

function output_minor_groups() {
    global $structure_en;
    global $structure_fr;
    $concordance_en = array_map('str_getcsv', file_or_die(CSV_MINOR_GROUPS_ENGLISH)); array_shift($concordance_en);
    foreach ($structure_en as $row_en) {
        if ($row_en[COLUMN_STRUCTURE_LEVEL] != NOC_LEVEL_MINOR_GROUP) continue;
        $row_fr = $structure_fr[$row_en[COLUMN_STRUCTURE_CODE]];
        $noc2021 = str_pad($row_en[COLUMN_STRUCTURE_CODE], NOC_LEVEL_MINOR_GROUP, '0', STR_PAD_LEFT);

        // Find all NOC 2016 codes and TEER relating to this one in the concordance table.
        $teer = NULL;
        $noc2016 = join(',', array_reduce($concordance_en, function($noc2016, $entry_en) use($row_en, &$teer) {
            if ($row_en[COLUMN_STRUCTURE_CODE] == $entry_en[COLUMN_MINOR_GROUPS_NOC_2021]) {
                if (strlen($entry_en[COLUMN_MINOR_GROUPS_NOC_2016])) {
                    $noc2016[] = str_pad($entry_en[COLUMN_MINOR_GROUPS_NOC_2016], NOC_LEVEL_MINOR_GROUP-1, '0', STR_PAD_LEFT);
                }
                $teer = $entry_en[COLUMN_MINOR_GROUPS_NOC_2021_TEER];
            }
            return $noc2016;
        }, []));

        // Write out the record.
        fputcsv(STDOUT, [
            $noc2021,
            $row_en[COLUMN_STRUCTURE_TITLE],
            $row_en[COLUMN_STRUCTURE_DEFINITION],
            $row_fr[COLUMN_STRUCTURE_TITLE],
            $row_fr[COLUMN_STRUCTURE_DEFINITION],
            NOC_LEVEL_MINOR_GROUP,
            $teer,
            substr($noc2021, 0, NOC_LEVEL_MINOR_GROUP-1),
            $noc2016,
        ]);
    }
}

function output_unit_groups($concordance_2011_2006) {
    global $structure_en;
    global $structure_fr;
    $concordance_en = array_map('str_getcsv', file_or_die(CSV_UNIT_GROUPS_ENGLISH)); array_shift($concordance_en);
    foreach ($structure_en as $row_en) {
        if ($row_en[COLUMN_STRUCTURE_LEVEL] != NOC_LEVEL_UNIT_GROUP) continue;
        $row_fr = $structure_fr[$row_en[COLUMN_STRUCTURE_CODE]];
        $noc2021 = str_pad($row_en[COLUMN_STRUCTURE_CODE], NOC_LEVEL_UNIT_GROUP, '0', STR_PAD_LEFT);

        // Special cases: 00011 -> 00018, ignore 00012-00015
        if ($noc2021 === '00011') {
            $noc2021 = '00018';
            $row_en[COLUMN_STRUCTURE_TITLE] = 'Senior managers - public and private sector';
            $row_fr[COLUMN_STRUCTURE_TITLE] = 'Cadres supérieurs / cadres supérieures - secteur public et privé';
            $row_en[COLUMN_STRUCTURE_DEFINITION] = <<<EOT
Senior managers in the public sector oversee operations in government departments and work with their middle managers to develop goals and policies according to legislation. Senior managers in the private sector work in industries such as telecommunications, finance, insurance, real estate, data processing and business services. They work with their middle managers to develop goals and policies and sometimes work with a board of directors.
EOT;
            $row_fr[COLUMN_STRUCTURE_DEFINITION] = <<<EOT
Les cadres supérieurs du secteur public supervisent les opérations des ministères et travaillent avec leurs cadres intermédiaires pour élaborer des objectifs et des politiques conformément à la législation. Les cadres supérieurs du secteur privé travaillent dans des secteurs tels que les télécommunications, la finance, les assurances, l'immobilier, le traitement des données et les services aux entreprises. Ils travaillent avec leurs cadres intermédiaires pour élaborer des objectifs et des politiques et travaillent parfois avec un conseil d'administration.
EOT;
            $noc2016 = '0012,0013,0014,0015,0016';
            $teer = 0;
        }
        else {
            // Find all NOC 2016 codes and TEER relating to this one in the concordance table.
            $teer = NULL;
            $noc2016 = join(',', array_reduce($concordance_en, function($noc2016, $entry_en) use($row_en, &$teer) {
                if ($row_en[COLUMN_STRUCTURE_CODE] == $entry_en[COLUMN_UNIT_GROUPS_NOC_2021]) {
                    if (strlen($entry_en[COLUMN_UNIT_GROUPS_NOC_2016])) {
                        $noc2016[] = str_pad($entry_en[COLUMN_UNIT_GROUPS_NOC_2016], NOC_LEVEL_UNIT_GROUP-1, '0', STR_PAD_LEFT);
                    }
                    $teer = $entry_en[COLUMN_UNIT_GROUPS_NOC_2021_TEER];
                }
                return $noc2016;
            }, []));
        }

        // Write out the record.
        if (!in_array($noc2021, [
            '00012', '00013', '00014', '00015'
        ])) {
            fputcsv(STDOUT, [
                $noc2021,
                $row_en[COLUMN_STRUCTURE_TITLE],
                $row_en[COLUMN_STRUCTURE_DEFINITION],
                $row_fr[COLUMN_STRUCTURE_TITLE],
                $row_fr[COLUMN_STRUCTURE_DEFINITION],
                NOC_LEVEL_UNIT_GROUP,
                $teer,
                substr($noc2021, 0, NOC_LEVEL_UNIT_GROUP-1),
                $noc2016,
            ]);
        }
    }
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

function file_or_die($filename) {
    global $dirname;
    $path = $dirname . $filename;
    $file = file($path);
    if (!$file) {
        fwrite(STDERR, "File $path not found. Aborting.\n");
        exit(1);
    }
    return $file;
}
