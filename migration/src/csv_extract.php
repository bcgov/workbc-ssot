<?php

/**
 * Extract numeric row ranges or spreadsheet rectangles (inclusive, one-based).
 *
 * Usage: php src/csv_extract.php --range 'A2-H20;A25-H30' [--range A40-H45]
 *        [--column-offset 0] [--cols length] [--header[=offset]]
 */

function extract_column($letters) {
  $column = 0;
  foreach (str_split($letters) as $letter) {
    $column = $column * 26 + ord($letter) - ord('A') + 1;
  }
  return $column;
}

try {
  $opts = getopt('', ['range:', 'cols:', 'header::', 'column-offset:']);
  if (empty($opts['range'])) {
    throw new InvalidArgumentException('At least one --range is required.');
  }
  $offset = (int)($opts['column-offset'] ?? 0);
  $ranges = [];
  foreach (explode(';', implode(';', (array)$opts['range'])) as $specification) {
    if (preg_match('/^([A-Z]+)([1-9][0-9]*)[-:]([A-Z]+)([1-9][0-9]*)$/', $specification, $m)) {
      $range = [(int)$m[2], (int)$m[4], extract_column($m[1]) - 1 - $offset,
        extract_column($m[3]) - extract_column($m[1]) + 1];
    }
    elseif (preg_match('/^([1-9][0-9]*)(?:-([1-9][0-9]*))?$/', $specification, $m)) {
      $range = [(int)$m[1], isset($m[2]) ? (int)$m[2] : PHP_INT_MAX, 0, null];
    }
    else {
      throw new InvalidArgumentException("Invalid range: $specification");
    }
    if ($range[0] > $range[1] || $range[2] < 0 || ($range[3] !== null && $range[3] < 1)) {
      throw new InvalidArgumentException("Reversed range or invalid column offset: $specification");
    }
    $ranges[] = $range;
  }
  $headers = [];
  $row = 0;
  $written = 0;
  $header_offset = isset($opts['header']) && $opts['header'] !== false ? (int)$opts['header'] : 1;
  while (($csv = fgetcsv(STDIN)) !== false) {
    $row++;
    foreach ($ranges as [$start, $end, $column, $width]) {
      if ($row < $start || $row > $end) {
        continue;
      }
      $out = $width === null ? $csv : array_slice(array_pad($csv, $column + $width, ''), $column, $width);
      if (isset($opts['cols'])) {
        $out = array_slice($out, 0, (int)$opts['cols']);
      }
      if (array_key_exists('header', $opts)) {
        if (!array_key_exists($start - $header_offset, $headers)) {
          throw new RuntimeException('Requested header row is unavailable.');
        }
        array_unshift($out, $headers[$start - $header_offset]);
      }
      fputcsv(STDOUT, $out);
      $written++;
    }
    if (array_key_exists('header', $opts)) {
      $headers[$row] = $csv[0];
    }
  }
  if ($written === 0) {
    throw new RuntimeException('Extraction produced no rows.');
  }
}
catch (Throwable $error) {
  fwrite(STDERR, $error->getMessage() . PHP_EOL);
  exit(1);
}
