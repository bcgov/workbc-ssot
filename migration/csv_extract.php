<?php

/**
 * Split CSV into several files based on given ranges.
 *
 * Usage: php csv_extract.php --range [start1-end1] --range [start2-end2] ... < /path/to/input.csv > /path/to/output.csv
 */

$opts = getopt('', [
    'range:',
]);
$usage = 'Usage: php csv_extract.php --range [start1-end1] --range [start2-end2] ... < /path/to/input.csv > /path/to/output.csv';
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

stream_set_blocking(STDIN, TRUE);
$row = 0;
while (($csv = fgetcsv(STDIN)) !== FALSE) {
    foreach ($ranges as $range) {
        if ($row >= $range['start'] && $row <= $range['end']) {
            fputcsv(STDOUT, $csv);
        }
    }
    $row++;
}
