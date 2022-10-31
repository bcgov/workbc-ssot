<?php

/**
 * Remove newlines from CSV header lines.
 *
 * Usage: php csv_header.php [header-lines] < /path/to/input.csv > /path/to/output.csv
 */

stream_set_blocking(STDIN, 0);
$header_lines = count($argv) > 1 ? intval($argv[1]) : 1;
for ($lines = 0; $lines < $header_lines; $lines++) {
    $csv = fgetcsv(STDIN);
    if (!is_array($csv)) {
        die("Expecting CSV file in stdin.\n");
    }
    fputcsv(STDOUT, array_map(fn($cell) => str_replace(array("\r", "\n"), ' ', $cell), $csv));
}
stream_copy_to_stream(STDIN, STDOUT);
