<?php

/**
 * Split CSV into several files based on given ranges.
 *
 * php csv_split.php --range [start1-end1] --range [start2-end2] ... --output /path/to/output1.csv --output /path/to/output2.csv ... < /path/to/input.csv
 */

$opts = getopt('', [
    'range:',
    'output:',
]);
$usage = 'Usage: php csv_split --range [start1-end1] --output /path/to/output1.csv --range [start2-end2] --output /path/to/output2.csv ... < /path/to/input.csv';
if (!array_key_exists('range', $opts) || !array_key_exists('output', $opts)) {
    die("Ranges or outputs not found\n" . $usage . PHP_EOL);
}
$opts['range'] = (array)$opts['range'];
$opts['output'] = (array)$opts['output'];
if (count($opts['range']) != count($opts['output'])) {
    die("Ranges and outputs should be equal\n" . $usage . PHP_EOL);
}

$ranges = [];
foreach ($opts['range'] as $i => $range) {
    $r = explode('-', $range);
    $ranges[] = [
        'start' => intval($r[0])-1,
        'end' => intval($r[1])-1,
        'output' => $opts['output'][$i],
    ];
}

stream_set_blocking(STDIN, 0);
$row = 0;
$file = NULL;
while (($csv = fgetcsv(STDIN)) !== FALSE) {
    foreach ($ranges as $range) {
        if ($row == $range['start']) {
            $file = fopen($range['output'], 'w');
        }
        if ($row >= $range['start'] && $row <= $range['end'] && !empty($file)) {
            fputcsv($file, $csv);
        }
        if ($row == $range['end']) {
            fclose($file);
            $file = NULL;
        }
    }
    $row++;
}
if (!empty($file)) {
    fclose($file);
}
