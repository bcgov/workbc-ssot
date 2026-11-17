<?php

/**
 * Normalize unavailable CSV values to the loader's SQL NULL marker.
 *
 * Usage: php src/csv_null.php --loader load/dataset.load [--pad-missing]
 */

try {
  $opts = getopt('', ['loader:', 'pad-missing']);
  if (empty($opts['loader']) || !is_readable($opts['loader'])) {
    throw new InvalidArgumentException('A readable --loader is required.');
  }
  $load = file_get_contents($opts['loader']);
  if (!preg_match("/from\\s+'[^']*'\\s*\\((.*?)\\)\\s*into/s", $load, $match)) {
    throw new RuntimeException('Cannot read loader input columns.');
  }
  $columns = preg_split('/,(?![^\[]*\])/', $match[1]);
  while (($csv = fgetcsv(STDIN)) !== false) {
    if (array_key_exists('pad-missing', $opts)) {
      $csv = array_pad($csv, count($columns), '');
    }
    foreach ($columns as $i => $column) {
      if (!array_key_exists($i, $csv)) {
        throw new RuntimeException('CSV has fewer fields than the loader requires.');
      }
      if (strpos($column, "null if '__SSOT_NULL__'") !== false) {
        $value = strpos($column, 'trim both whitespace') !== false ? trim($csv[$i]) : $csv[$i];
        if ($value === '__SSOT_NULL__') {
          throw new RuntimeException('Input collides with reserved SQL NULL marker.');
        }
        if (in_array(strtolower($value), ['na', 'n/a', 'n.a.', '-'], true) || trim($value, ' ') === '') {
          $csv[$i] = '__SSOT_NULL__';
        }
      }
    }
    fputcsv(STDOUT, $csv);
  }
}
catch (Throwable $error) {
  fwrite(STDERR, $error->getMessage() . PHP_EOL);
  exit(1);
}
