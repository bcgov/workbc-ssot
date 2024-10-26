<?php

/**
 * Inject CSV columns that can be used as unique keys for a row.
 *
 * Usage: php csv_rowkeys --col col1 [--col col2 ...] < /path/to/input.csv > /path/to/output.csv
 */

$opts = getopt('', [
  'col:'
]);
$usage = 'Usage: php csv_rowkeys --col col1 [--col col2 ...] < /path/to/input.csv > /path/to/output.csv';
if (!array_key_exists('col', $opts)) {
  die("Column specification not found\n" . $usage . PHP_EOL);
}
$opts['col'] = (array)$opts['col'];

$keys = [];
stream_set_blocking(STDIN, TRUE);
while (($csv = fgetcsv(STDIN)) !== FALSE) {
  $out = $csv;
  foreach ($opts['col'] as $col) {
    $col--; // 0-based arrays vs. 1-based rows
    $key = parameterize($csv[$col]);
    if (array_key_exists($key, $keys)) {
      array_splice($out, $col, 0, $key . '_' . $keys[$key]);
      $keys[$key]++;
    }
    else {
      array_splice($out, $col, 0, $key);
      $keys[$key] = 1;
    }
  }
  fputcsv(STDOUT, $out);
}

function parameterize($string, $sep = '_') {
  # Convert to ASCII.
  $parameterized_string = iconv('UTF-8', 'ASCII', $string);

  # Get rid of anything thats not a valid letter, number, dash and underscore and
  # replace with a dash.
  $parameterized_string = preg_replace("/[^a-z]/i", $sep, $parameterized_string);

  # Remove connected dashes so we don't end up with -- anyhwere.
  $parameterized_string = preg_replace("/$sep{2,}/", $sep, $parameterized_string);

  # Remove any trailing spaces from the string.
  $parameterized_string = preg_replace("/^$sep|$sep$/", '', $parameterized_string);

  # Downcase the string.
  return strtolower($parameterized_string);
}
