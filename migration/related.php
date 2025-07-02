<?php

/**
 * Extract related NOCs from the NOC 2021 elements table into a CSV suitable for import.
 * https://www.statcan.gc.ca/en/subjects/standard/noc/2021/indexV1
 *
 * Usage: php related.php path/to/noc_2021_version_1.0_-_elements.csv > /path/to/output.csv
 */

$usage = "Usage: php related.php path/to/noc_2021_version_1.0_-_elements.csv > /path/to/output.csv";
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
$ngrams = [];
$deferred = [];
$seen = [];
while (FALSE !== ($element = fgetcsv($elements))) {
  $noc = in_array($element[COLUMN_STRUCTURE_NOC], [
    '00011', '00012', '00013', '00014', '00015'
  ]) ? '00018' : $element[COLUMN_STRUCTURE_NOC];
  $seen[$noc] ??= [];

  // Insert NOC in 4-gram structure.
  $ngram = substr($noc, 0, 4);
  $ngrams[$ngram] ??= []; $ngrams[$ngram][] = $noc;

  // Detect exlcusions which represent related NOCs.
  if (strcasecmp($element[COLUMN_STRUCTURE_TYPE], 'exclusion(s)') === 0) {
    $related = $element[COLUMN_STRUCTURE_JOBTITLE];
    $matches = null;
    if (!preg_match('/\(see\s+(\d+)\s+/i', $related, $matches)) {
      fwrite(STDERR, "Related NOC not found in exclusion: \"{$related}\". Aborting.\n");
      exit(1);
    }
    $related_noc = in_array($matches[1], [
      '00011', '00012', '00013', '00014', '00015'
    ]) ? '00018' : $matches[1];

    // If we've already seen this related NOC, ignore it.
    if (in_array($related_noc, $seen[$noc])) {
      // Do nothing.
    }
    // If it's a 5-digit NOC, output it immediately.
    else if (strlen($related_noc) === 5) {
      fputcsv(STDOUT, [
        $noc,
        $related_noc,
      ]);
      $seen[$noc][] = $related_noc;
    }
    // If it's a 4-digit NOC, defer it until we've built the full 4-grams structure.
    else if (strlen($related_noc) === 4) {
      $deferred[$noc] ??= []; $deferred[$noc][] = $related_noc;
    }
    else {
      fwrite(STDERR, "Unexpected NOC found in exclusion: \"{$related}\". Aborting.\n");
      exit(1);
    }
  }
}

// We've now built the full 4-grams structure. Output the deferred NOCs.
foreach ($deferred as $noc => $related_ngrams) {
  foreach (array_unique($related_ngrams) as $ngram) {
    foreach (array_unique($ngrams[$ngram]) as $related_noc) {
      if (in_array($related_noc, $seen[$noc])) {
        // Do nothing.
      }
      else {
        fputcsv(STDOUT, [
          $noc,
          $related_noc,
        ]);
        $seen[$noc][] = $related_noc;
      }
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
