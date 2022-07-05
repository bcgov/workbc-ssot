<?php

/**
 * Remove newlines from CSV header line.
 *
 * Usage: php csv_header.php < /path/to/input.csv > /path/to/output.csv
 */

stream_set_blocking(STDIN, 0);
$csv = fgetcsv(STDIN);
if (!is_array($csv)) {
    die("Expecting CSV file in stdin.\n");
}
fputcsv(STDOUT, array_map(fn($cell) => str_replace(array("\r", "\n"), ' ', $cell), $csv));
stream_copy_to_stream(STDIN, STDOUT);
