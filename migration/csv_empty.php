<?php

/**
* Remove empty rows from CSV.
*
* Usage: php csv_empty.php < /path/to/input.csv > /path/to/output.csv
*/

stream_set_blocking(STDIN, TRUE);
while (($csv = fgetcsv(STDIN)) !== FALSE) {
  // Skip empty rows.
  foreach ($csv as $cell) {
    if (!empty($cell) || is_numeric($cell)) {
      fputcsv(STDOUT, $csv);
      break;
    }
  }
}
