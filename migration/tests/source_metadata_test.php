<?php

/**
 * Usage: php migration/tests/source_metadata_test.php
 */

require dirname(__DIR__) . '/src/register_sources.php';

$tests = 0;
function metadata_check(bool $condition, string $label): void {
  global $tests;
  if (!$condition) {
    throw new RuntimeException("FAIL: $label");
  }
  $tests++;
}

function metadata_rows(string $csv): array {
  $stream = fopen('php://temp', 'r+');
  fwrite($stream, $csv);
  rewind($stream);
  $header = fgetcsv($stream);
  $rows = [];
  while (($row = fgetcsv($stream)) !== false) {
    $rows[] = array_combine($header, $row);
  }
  fclose($stream);
  return $rows;
}

$csv = <<<'CSV'
"label","endpoint","datapoint","filename","sheet","range","author","date","period"
"Monthly","updates",,"jan","Sheet3",,,"2026/02/01 08:00","2026/01/01 08:00"
"Monthly","updates",,"feb","Sheet3",,,"2026/03/01 08:00","2026/02/01 08:00"
"Special","updates","unemployment",,,,,,"1971/11/26 08:00"
"Annual","regional",,"old","Regional","A2-B3","Office","2025/01/01 08:00","2025/01/01 08:00"
"Annual","provincial",,"old","Provincial","A2-C3","Office","2025/01/01 08:00","2025/01/01 08:00"
CSV;
$upload = [
  'year' => 2026, 'month' => 2, 'date' => '2026/03/05 08:00', 'label' => 'Updated, "Office"',
  'datasets' => [['endpoint' => 'updates', 'sheet' => 'Sheet3', 'range' => null, 'match' => 'period']],
];
$updated = register_sources($csv, 'new, monthly.xlsx', $upload);
$rows = metadata_rows($updated);
metadata_check(count($rows) === 5 && $rows[0] === metadata_rows($csv)[0] && $rows[1] === metadata_rows($csv)[2],
  'monthly replacement preserves other periods and datapoint provenance');
metadata_check($rows[4]['filename'] === 'new, monthly' && $rows[4]['label'] === 'Updated, "Office"',
  'filenames and labels preserve CSV quoting');
metadata_check($updated === register_sources($updated, 'new, monthly.xlsx', $upload),
  'repeated registration is idempotent');
$annual = array_replace($upload, ['datasets' => [
  ['endpoint' => 'regional', 'sheet' => 'Regional', 'range' => 'A2:B3', 'match' => 'endpoint'],
  ['endpoint' => 'provincial', 'sheet' => 'Provincial', 'range' => 'A2:C3', 'match' => 'endpoint'],
]]);
$rows = metadata_rows(register_sources($csv, 'annual.xlsx', $annual));
metadata_check(count($rows) === 5 && array_slice($rows, 0, 3) === array_slice(metadata_rows($csv), 0, 3)
  && array_column(array_slice($rows, 3), 'filename') === ['annual', 'annual']
  && array_column(array_slice($rows, 3), 'range') === ['A2-B3', 'A2-C3'] && $rows[3]['author'] === 'Office',
  'snapshot workbook updates every dataset, normalizes ranges and preserves author');
$resolved = array_replace($annual, ['datasets' => [
  ['endpoint' => 'regional', 'sheet' => 'Regional', 'range' => 'B2:C4', 'match' => 'endpoint'],
]]);
$registered = metadata_rows(register_sources($csv, 'annual.xlsx', $resolved));
$range = end($registered)['range'];
$process = proc_open(['php', dirname(__DIR__) . '/src/csv_extract.php', '--range', $range],
  [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
fwrite($pipes[0], "a,b,c\n1,2,3\n4,5,6\n7,8,9\n10,11,12\n");
fclose($pipes[0]);
$output = stream_get_contents($pipes[1]);
$error = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$status = proc_close($process);
metadata_check($range === 'B2-C4' && $status === 0 && $output === "2,3\n5,6\n8,9\n",
  "resolved range survives registration and extracts the same rows: $error");
$rows = metadata_rows(register_sources($csv, 'march.XLSX', array_replace($upload, ['month' => 3])));
metadata_check(count($rows) === 6 && $rows[5]['period'] === '2026/03/01 08:00' && $rows[5]['filename'] === 'march',
  'new month is appended with a case-insensitive XLSX extension');
try {
  register_sources($csv, 'annual.xlsx', array_replace($annual, ['datasets' => [
    ['endpoint' => 'regional', 'sheet' => 'Regional', 'range' => 'invalid', 'match' => 'endpoint'],
  ]]));
  throw new RuntimeException('Invalid range was accepted.');
}
catch (InvalidArgumentException $expected) {
  metadata_check(true, 'invalid range rejected');
}
try {
  register_sources(str_replace('"label","endpoint"', '"endpoint","label"', $csv), 'annual.xlsx', $annual);
  throw new RuntimeException('Reordered header was accepted.');
}
catch (UnexpectedValueException $expected) {
  metadata_check(true, 'loader column ordering enforced');
}

$directory = sys_get_temp_dir() . '/ssot-metadata-' . bin2hex(random_bytes(8));
mkdir($directory, 0700);
$filename = 'new, monthly.xlsx';
$manifest = "$directory/sources.csv";
$workbook = "$directory/$filename";
$bytes = 'workbook bytes';
$event = ['client_payload' => [
  'filename' => $filename, 'workbook_sha' => sha1('blob ' . strlen($bytes) . "\0" . $bytes),
  'year' => $upload['year'], 'month' => $upload['month'], 'date' => $upload['date'],
  'metadata' => ['label' => $upload['label'], 'datasets' => $upload['datasets']],
]];
$run = function(array $event) use ($directory, $manifest): array {
  $process = proc_open(['php', dirname(__DIR__) . '/src/register_sources.php', $manifest, $directory],
    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
  fwrite($pipes[0], json_encode($event, JSON_THROW_ON_ERROR));
  fclose($pipes[0]);
  $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
  fclose($pipes[1]);
  fclose($pipes[2]);
  return [proc_close($process), $output];
};
try {
  file_put_contents($manifest, $csv);
  file_put_contents($workbook, $bytes);
  [$status, $output] = $run($event);
  metadata_check($status === 0 && file_get_contents($manifest) === $updated, "CLI registration: $output");
  [$status, $output] = $run($event);
  metadata_check($status === 0 && file_get_contents($manifest) === $updated, "CLI rerun: $output");
  $stale = $event;
  $stale['client_payload']['workbook_sha'] = str_repeat('0', 40);
  [$status, $output] = $run($stale);
  metadata_check($status === 1 && strpos($output, 'refusing stale metadata') !== false
    && file_get_contents($manifest) === $updated, 'stale uploads cannot modify the manifest');
  $invalid = $event;
  $invalid['client_payload']['metadata']['datasets'][0]['range'] = 'invalid';
  [$status] = $run($invalid);
  metadata_check($status === 1 && file_get_contents($manifest) === $updated, 'invalid metadata cannot modify the manifest');
  $invalid['client_payload']['filename'] = '../outside.xlsx';
  [$status] = $run($invalid);
  metadata_check($status === 1 && file_get_contents($manifest) === $updated, 'workbook paths are rejected');
}
finally {
  unlink($workbook);
  unlink($manifest);
  rmdir($directory);
}
echo "PASS: $tests source metadata checks.\n";
