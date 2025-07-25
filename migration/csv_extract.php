<?php

/**
* Extract CSV rows based on given ranges.
*
* Usage: php csv_extract.php --range start1-end1 [--cols length] [--header[:offset]] [--range start2-end2 ...] < /path/to/input.csv > /path/to/output.csv
*/

$opts = getopt('', [
  'range:',
  'cols:',
  'header::'
]);
$usage = 'Usage: php csv_extract.php --range start1-end1 [--cols length] [--header] [--range start2-end2 ...] < /path/to/input.csv > /path/to/output.csv';
if (!array_key_exists('range', $opts)) {
  die("No ranges found\n" . $usage . PHP_EOL);
}
$opts['range'] = (array)$opts['range'];
$ranges = [];
foreach ($opts['range'] as $i => $range) {
  $r = explode('-', $range);
  $ranges[] = [
    'start' => intval($r[0])-1,
    'end' => empty($r[1]) ? PHP_INT_MAX : intval($r[1])-1,
  ];
}
$cols = array_key_exists('cols', $opts) ? intval($opts['cols']) : 0;
$header_offset = array_key_exists('header', $opts) && $opts['header'] !== false ? intval($opts['header']) : 1;
$headers = [];

stream_set_blocking(STDIN, TRUE);
$row = 0;
while (($csv = fgetcsv(STDIN)) !== FALSE) {
  foreach ($ranges as $range) {
    if ($row >= $range['start'] && $row <= $range['end']) {
      if (array_key_exists('header', $opts)) {
        array_unshift($csv, $headers[$range['start']-$header_offset]);
      }
      if (empty($cols)) {
        fputcsv(STDOUT, $csv);
      }
      else {
        fputcsv(STDOUT, array_slice($csv, 0, $cols + (array_key_exists('header', $opts) ? 1 : 0)));
      }
    }
  }
  $headers[$row] = array_shift($csv);
  $row++;
}
