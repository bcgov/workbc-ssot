<?php

/**
 * Fill empty column cells with previous values in a CSV.
 *
 * Usage: php csv_refill.php --col col1 --col col2 < /path/to/input.csv > /path/to/output.csv
 */

$opts = getopt('', [
    'col:',
]);
$usage = 'Usage: php csv_refill.php --col col1 --col col2 [...] < /path/to/input.csv > /path/to/output.csv';
if (!array_key_exists('col', $opts)) {
    die("Column specification not found\n" . $usage . PHP_EOL);
}
$opts['col'] = (array)$opts['col'];

$values = [];
$lines = 0;
stream_set_blocking(STDIN, TRUE);
while (($csv = fgetcsv(STDIN)) !== FALSE) {
    foreach ($opts['col'] as $col) {
        $col--; // 0-based arrays vs. 1-based rows
        if (trim($csv[$col]) === '-' || (empty($csv[$col]) && !is_numeric($csv[$col]))) {
            $csv[$col] = $values[$col] ?? null;
        }
        else {
            $values[$col] = $csv[$col];
        }
    }
    fputcsv(STDOUT, $csv);
}
