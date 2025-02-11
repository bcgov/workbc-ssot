<?php

/**
 * Transform the NOC 2021 elements table into a CSV suitable for import.
 * https://www.statcan.gc.ca/en/subjects/standard/noc/2021/indexV1
 *
 * Usage: php titles.php path/to/noc_2021_version_1.0_-_elements.csv > /path/to/output.csv
 */

$usage = "Usage: php titles.php path/to/noc_2021_version_1.0_-_elements.csv > /path/to/output.csv";
if (count($argv) < 2) {
  die("Expecting 1 argument.\n" . $usage . PHP_EOL);
}
$filename = $argv[1];

const COLUMN_STRUCTURE_LEVEL = 0;
const COLUMN_STRUCTURE_NOC = 1;
const COLUMN_STRUCTURE_NOCLABEL = 2;
const COLUMN_STRUCTURE_TYPE = 3;
const COLUMN_STRUCTURE_JOBTITLE = 4;

$elements = fopen_or_die($filename); fgetcsv($elements);
$row = 2;
while (FALSE !== ($element = fgetcsv($elements))) {
  if (strcasecmp($element[COLUMN_STRUCTURE_TYPE], 'all examples') === 0) {
    $noc = in_array($element[COLUMN_STRUCTURE_NOC], [
      '00011', '00012', '00013', '00014', '00015'
    ]) ? '00018' : $element[COLUMN_STRUCTURE_NOC];
    fputcsv(STDOUT, [
      $noc,
      ucfirst(trim($element[COLUMN_STRUCTURE_JOBTITLE]))
    ]);
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
