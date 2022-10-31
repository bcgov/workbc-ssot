<?php

/**
 * Refill empty column cells with previous values in a CSV.
 *
 * Usage: php csv_refill.php --col col1 --col col2 < /path/to/input.csv > /path/to/output.csv
 */

$opts = getopt('', [
    'col:',
]);
$usage = 'Usage: php csv_refill.php --col col1 --col col2 < /path/to/input.csv > /path/to/output.csv';
if (!array_key_exists('col', $opts)) {
    die("Column specification not found\n" . $usage . PHP_EOL);
}
$opts['col'] = (array)$opts['col'];

$values = [];
stream_set_blocking(STDIN, 0);
while (($csv = fgetcsv(STDIN)) !== FALSE) {
    foreach ($opts['col'] as $col) {
        $col--; // 0-based arrays vs. 1-based rows
        if (empty($csv[$col]) && !is_numeric($csv[$col])) {
            $csv[$col] = $values[$col];
        }
        else {
            $values[$col] = $csv[$col];
        }
    }
    fputcsv(STDOUT, $csv);
}
