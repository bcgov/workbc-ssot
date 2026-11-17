<?php

/**
 * Isolated driver integration tests: no Docker, Gnumeric or database required.
 *
 * Usage: php migration/tests/conversion_test.php
 */

$checks = 0;
set_error_handler(function($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});
function conversion_check(bool $condition, string $label): void {
  global $checks;
  if (!$condition) {
    throw new RuntimeException("FAIL: $label");
  }
  $checks++;
}

function conversion_remove(string $directory): void {
  foreach (new FilesystemIterator($directory) as $file) {
    if ($file->isDir() && !$file->isLink()) {
      conversion_remove($file->getPathname());
    }
    else {
      unlink($file->getPathname());
    }
  }
  rmdir($directory);
}

function conversion_manifest(string $directory, array $rows): void {
  $stream = fopen("$directory/load/sources.csv", 'w');
  fputcsv($stream, ['label', 'endpoint', 'datapoint', 'filename', 'sheet', 'range', 'author', 'date', 'period']);
  foreach ($rows as $row) {
    fputcsv($stream, array_replace(['Example', '', '', '', '', '', 'Fixture Office', '2026/03/01 08:00', '2026/01/01 08:00'], $row));
  }
  fclose($stream);
}

function conversion_fixture(callable $test): void {
  $directory = sys_get_temp_dir() . '/ssot-conversion-' . bin2hex(random_bytes(8));
  mkdir($directory, 0700);
  try {
    foreach (['src', 'data', 'data/lookup', 'load', 'load/updates', 'bin'] as $path) {
      mkdir("$directory/$path");
    }
    foreach (glob(dirname(__DIR__) . '/src/*.php') as $source) {
      copy($source, "$directory/src/" . basename($source));
    }
    copy(__DIR__ . '/fixtures/conversion_reader.php', "$directory/src/fixture_reader.php");
    foreach (['ssconvert', 'pgloader', 'psql'] as $command) {
      copy(__DIR__ . '/fixtures/conversion_command.php', "$directory/bin/$command");
      chmod("$directory/bin/$command", 0700);
    }
    file_put_contents("$directory/data/New, data.XLSX", json_encode([
      'Shared' => [['code', 'value'], ['#7', 'NA'], ['#8', '12'], ['#9', '5']],
      'Details' => [['name', 'value'], ['demo', '3']],
    ], JSON_THROW_ON_ERROR));
    foreach (['Jan' => 1, 'Feb' => 2] as $month => $number) {
      file_put_contents("$directory/data/Survey $month.xlsx", json_encode([
        'Data' => [['year', 'month', 'value'], ['2026', (string)$number, '10']],
      ], JSON_THROW_ON_ERROR));
    }
    file_put_contents("$directory/data/lookup/input.csv", "key,label\nx,Example\n");
    file_put_contents("$directory/data/raw.csv", "key,value\nx,4\n");
    $rows = [
      [1 => 'example_codes', 3 => 'New, data', 4 => 'Shared', 5 => 'A2-B3'],
      [1 => 'example_tail', 3 => 'New, data', 4 => 'Shared', 5 => 'A4-B4'],
      [1 => 'example_details', 3 => 'New, data', 4 => 'Details', 5 => 'A2-B2'],
      [1 => 'lookup', 3 => 'lookup/*'],
      [1 => 'csv_reader', 3 => 'raw.csv'],
      [1 => 'csv_direct', 3 => 'raw.csv'],
      [1 => 'survey_periods', 3 => 'Survey Jan', 4 => 'Data', 5 => 'A2-C2'],
      [1 => 'survey_periods', 3 => 'Survey Feb', 4 => 'Data', 5 => 'A2-C2', 8 => '2026/02/01 08:00'],
      [1 => 'example_codes', 2 => 'value', 8 => '1971/11/26 08:00'],
      [1 => 'reference_only', 3 => 'not-an-input'],
    ];
    $snapshot = function(string $name, int $order = 100): array {
      return [
        'source' => ['type' => 'xlsx'],
        'output' => "load/$name.csv",
        'steps' => [['csv_extract.php', '--range', '{range}']],
        'load' => ['file' => "load/$name.load", 'order' => $order, 'mode' => 'snapshot'],
      ];
    };
    $config = ['version' => 1, 'datasets' => [
      'example_codes' => $snapshot('example_codes', 30),
      'example_tail' => $snapshot('example_tail', 30),
      'example_details' => $snapshot('example_details', 20),
      'lookup' => $snapshot('lookup', 10),
      'csv_reader' => $snapshot('csv_reader'),
      'csv_direct' => $snapshot('csv_direct'),
      'survey_periods' => $snapshot('survey_periods', 200),
      'reference_only' => ['source' => ['type' => 'metadata']],
      'not_in_inventory' => $snapshot('not_in_inventory'),
    ]];
    $config['datasets']['example_codes']['prepare_requires'] = ['lookup'];
    array_push($config['datasets']['example_codes']['steps'],
      ['fixture_reader.php', '--require-lookup'],
      ['csv_trimpad.php', '--column', '1:L:0:0:#'],
      ['csv_trimpad.php', '--column', '1:L:3:0'],
      ['csv_null.php', '--loader', '{loader}']);
    $config['datasets']['lookup']['source'] = [
      'type' => 'directory', 'input' => 'data/lookup/', 'reader' => ['fixture_reader.php', '{input}'],
    ];
    $config['datasets']['lookup']['steps'] = [];
    foreach (['csv_reader', 'csv_direct'] as $endpoint) {
      $config['datasets'][$endpoint]['source'] = ['type' => 'csv'];
      $config['datasets'][$endpoint]['steps'] = [];
    }
    $config['datasets']['csv_reader']['source']['reader'] = ['csv_extract.php', '--range', '2'];
    $config['datasets']['survey_periods']['output'] = 'load/updates/survey_{year}_{month02}.csv';
    $config['datasets']['survey_periods']['load'] += [
      'table' => 'survey_periods', 'partition' => ['year' => '{year}', 'month' => '{month}'],
    ];
    $config['datasets']['survey_periods']['load']['mode'] = 'partition';
    foreach (array_keys($config['datasets']) as $endpoint) {
      file_put_contents("$directory/load/$endpoint.load",
        "load csv from '{{ SOURCE }}' (code, value [null if '__SSOT_NULL__']) into postgresql:///ssot\n");
    }
    file_put_contents("$directory/load/sources.load", "fixture metadata loader\n");
    $test($directory, $config, $rows);
  }
  finally {
    conversion_remove($directory);
  }
}

function conversion_run(string $directory, array $config, array $rows, array $arguments = [], string $failure = '', ?callable $prepare = null): array {
  file_put_contents("$directory/conversion.json", json_encode($config, JSON_THROW_ON_ERROR));
  conversion_manifest($directory, $rows);
  file_put_contents("$directory/commands.jsonl", '');
  if ($prepare !== null) {
    $prepare($directory);
  }
  $process = proc_open([PHP_BINARY, "$directory/src/ssot_ingest.php", ...$arguments], [
    0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
  ], $pipes, '/', array_merge(getenv(), [
    'PATH' => "$directory/bin:" . getenv('PATH'), 'SSOT_TEST_LOG' => "$directory/commands.jsonl",
    'SSOT_TEST_FAIL' => $failure, 'SOURCE' => '',
    'PGHOST' => '/nonexistent/ssot-conversion-test', 'PGCONNECT_TIMEOUT' => '1',
  ]));
  $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
  fclose($pipes[1]);
  fclose($pipes[2]);
  $status = proc_close($process);
  $commands = array_map(fn($line) => json_decode($line, true, 512, JSON_THROW_ON_ERROR),
    file("$directory/commands.jsonl", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
  conversion_check(!glob("$directory/load/.ingest-*"), 'temporary conversion files cleaned on exit');
  return [$status, $output, $commands];
}

function conversion_cancel(string $directory, array $config, array $rows, string $command, int $signal, bool $ignore_term): void {
  file_put_contents("$directory/conversion.json", json_encode($config, JSON_THROW_ON_ERROR));
  conversion_manifest($directory, $rows);
  $process = proc_open([PHP_BINARY, "$directory/src/ssot_ingest.php", '--file', 'New, data'], [
    0 => ['file', '/dev/null', 'r'],
    1 => ['file', "$directory/output", 'w'],
    2 => ['file', "$directory/errors", 'w'],
  ], $pipes, '/', array_merge(getenv(), [
    'PATH' => "$directory/bin:" . getenv('PATH'), 'SSOT_TEST_LOG' => "$directory/commands.jsonl",
    'SSOT_TEST_WAIT' => $command, 'SSOT_TEST_CHILD' => "$directory/child.pid",
    'SSOT_TEST_IGNORE_TERM' => $ignore_term ? '1' : '0', 'SSOT_TEST_FAIL' => '', 'SOURCE' => '',
    'PGHOST' => '/nonexistent/ssot-conversion-test', 'PGCONNECT_TIMEOUT' => '1',
  ]));
  $child = null;
  try {
    $deadline = microtime(true) + 10;
    while (!is_file("$directory/child.pid") && microtime(true) < $deadline) {
      usleep(10000);
    }
    conversion_check(is_file("$directory/child.pid"), "$command child reaches the interruption point");
    $pid = (int)file_get_contents("$directory/child.pid");
    if ($pid < 2) {
      throw new RuntimeException('Invalid fixture child PID.');
    }
    $child = $pid;
    $status = proc_get_status($process);
    conversion_check(posix_kill($status['pid'], $signal), 'signal sent to the driver, not the child');
    $started = microtime(true);
    do {
      usleep(10000);
      $status = proc_get_status($process);
    } while ($status['running'] && microtime(true) - $started < 6);
    conversion_check(!$status['running'] && $status['exitcode'] === 128 + $signal,
      "$command cancellation exits promptly with signal status");
    conversion_check(!posix_kill($child, 0), "$command cancellation does not leave the active child running");
    conversion_check(!glob("$directory/load/.ingest-*"), 'interrupted pipeline cleans temporary conversion files');
    $output = file_get_contents("$directory/output") . file_get_contents("$directory/errors");
    conversion_check(str_contains($output, 'Ingestion interrupted by SIG') && !str_contains($output, 'SSOT INGESTION COMPLETED'),
      'cancellation is reported explicitly, never as success');
    $commands = file_get_contents("$directory/commands.jsonl");
    conversion_check(!str_contains($commands, 'load/sources.load'), 'cancellation prevents subsequent metadata loading');
    if ($ignore_term) {
      conversion_check(microtime(true) - $started >= 2, 'unresponsive child is killed after the grace period');
    }
  }
  finally {
    if ($child !== null && posix_kill($child, 0)) {
      posix_kill($child, SIGKILL);
    }
    $status = proc_get_status($process);
    if ($status['running']) {
      proc_terminate($process, SIGKILL);
    }
    proc_close($process);
  }
}

conversion_fixture(function($directory, $config, $rows) {
  [$status, $output, $commands] = conversion_run($directory, $config, $rows);
  conversion_check($status === 0, "new datasets ingest without driver edits: $output");
  $conversions = array_values(array_filter($commands, fn($command) => $command['command'] === 'ssconvert'));
  conversion_check(count($conversions) === 3, 'one conversion per workbook, not per dataset or sheet');
  conversion_check(count(array_filter($conversions, fn($command) => str_contains($command['args'][2], 'New, data.XLSX'))) === 1,
    'same workbook and shared sheet converted only once');
  conversion_check(file_get_contents("$directory/load/example_codes.csv") === "007,__SSOT_NULL__\n008,12\n",
    'steps execute in declared order with NULL normalization');
  conversion_check(file_get_contents("$directory/load/example_tail.csv") === "#9,5\n",
    'second dataset extracts its own range from the immutable shared sheet');
  conversion_check(file_get_contents("$directory/load/example_details.csv") === "demo,3\n",
    'different sheet uses its own raw CSV');
  conversion_check(file_get_contents("$directory/load/lookup.csv") === "key,label\nx,Example\n",
    'directory reader prepares a declared dependency');
  conversion_check(file_get_contents("$directory/load/csv_reader.csv") === "x,4\n",
    'CSV source supports a reader with input on stdin');
  conversion_check(file_get_contents("$directory/load/csv_direct.csv") === "key,value\nx,4\n"
    && is_file("$directory/data/raw.csv"), 'CSV source without reader or steps is copied, not moved');
  conversion_check(!is_file("$directory/load/not_in_inventory.csv"), 'JSON recipes cannot introduce inventory entries');
  $loads = array_values(array_filter($commands, fn($command) => $command['command'] === 'pgloader'));
  conversion_check(array_column($loads, 'args') === array_map(fn($file) => ['--on-error-stop', "load/$file.load"],
    ['lookup', 'example_details', 'example_codes', 'example_tail', 'csv_reader', 'csv_direct',
      'survey_periods', 'survey_periods', 'sources']), 'load order, stable ties, and metadata-last are explicit');
  conversion_check($loads[2]['csv'] === "007,__SSOT_NULL__\n008,12\n"
    && $loads[7]['csv'] === "2026,2,10\n", 'each loader receives its exact generated SOURCE');
  $sql = array_values(array_filter($commands, fn($command) => $command['command'] === 'psql'));
  conversion_check(count($sql) === 1 && end($sql[0]['args']) === 'DROP TABLE IF EXISTS "survey_periods"',
    'full rebuild resets a partitioned table once');
  [$status, $output] = conversion_run($directory, $config, $rows);
  conversion_check($status === 0 && file_get_contents("$directory/load/example_codes.csv") === "007,__SSOT_NULL__\n008,12\n",
    "full pipeline is repeatable: $output");
});

conversion_fixture(function($directory, $config, $rows) {
  [$status, $output, $commands] = conversion_run($directory, $config, $rows, ['--file', 'New, data.xlsx']);
  conversion_check($status === 0, "filtered fictitious workbook: $output");
  conversion_check(count(array_filter($commands, fn($command) => $command['command'] === 'ssconvert')) === 1,
    'filtered multi-dataset workbook is converted once');
  $loads = array_values(array_filter($commands, fn($command) => $command['command'] === 'pgloader'));
  conversion_check(count($loads) === 4 && !in_array(['--on-error-stop', 'load/lookup.load'], array_column($loads, 'args'), true),
    'preparation dependency is not loaded during filtered ingestion');
  conversion_check(is_file("$directory/load/lookup.csv") && !is_file("$directory/load/csv_direct.csv"),
    'filtered run prepares only its dependency and leaves unrelated outputs alone');
  conversion_check(!array_filter($commands, fn($command) => $command['command'] === 'psql'),
    'filtered snapshots do not reset partitioned tables');
  [$status, $output, $commands] = conversion_run($directory, $config, $rows, ['--file', 'New, data', '--dry-run']);
  conversion_check($status === 0 && !$commands && str_contains($output, '[prepare only]'),
    'dry-run validates and lists dependencies without running any external command');
});

conversion_fixture(function($directory, $config, $rows) {
  [$status, $output, $commands] = conversion_run($directory, $config, $rows,
    ['--file', 'Survey Feb.xlsx', '--file', 'Survey Feb.xlsx']);
  conversion_check($status === 0, "filtered partition: $output");
  $sql = array_values(array_filter($commands, fn($command) => $command['command'] === 'psql'));
  conversion_check(count($sql) === 1 && end($sql[0]['args']) === 'DELETE FROM "survey_periods" WHERE "year"=\'2026\' AND "month"=\'2\'',
    'selected partition deleted exactly once, with no full-table reset');
  conversion_check(!is_file("$directory/load/updates/survey_2026_01.csv")
    && file_get_contents("$directory/load/updates/survey_2026_02.csv") === "2026,2,10\n",
    'filtered ingestion leaves other periods untouched');
  [$status, $output, $commands] = conversion_run($directory, $config, $rows,
    ['--file', 'Survey Feb', '--file', 'Survey Jan.xlsx']);
  conversion_check($status === 0 && count(array_filter($commands, fn($command) => $command['command'] === 'psql')) === 2,
    "repeatable --file selects multiple distinct periods: $output");
});

$invalid = [
  'missing recipe' => [function(&$config, &$rows) { unset($config['datasets']['example_codes']); }, 'Missing conversion recipe'],
  'unknown source type' => [function(&$config, &$rows) { $config['datasets']['example_codes']['source']['type'] = 'excel'; }, 'Unknown source type'],
  'unknown recipe key' => [function(&$config, &$rows) { $config['datasets']['example_codes']['step'] = []; }, 'Invalid or missing fields'],
  'unknown load mode' => [function(&$config, &$rows) { $config['datasets']['example_codes']['load']['mode'] = 'append'; }, 'Invalid load settings'],
  'noninteger order' => [function(&$config, &$rows) { $config['datasets']['example_codes']['load']['order'] = '10'; }, 'Invalid load settings'],
  'unknown placeholder' => [function(&$config, &$rows) { $config['datasets']['example_codes']['steps'][] = ['csv_empty.php', '{typo}']; }, 'Unknown or unavailable placeholder'],
  'missing range' => [function(&$config, &$rows) { $rows[0][5] = ''; }, 'Unknown or unavailable placeholder'],
  'invalid range' => [function(&$config, &$rows) { $rows[0][5] = 'not-a-range'; }, 'Invalid range'],
  'reversed rows' => [function(&$config, &$rows) { $rows[0][5] = 'A9-B2'; }, 'Invalid range'],
  'reversed columns' => [function(&$config, &$rows) { $rows[0][5] = 'Z2-B3'; }, 'Invalid range'],
  'missing period' => [function(&$config, &$rows) { $rows[6][8] = ''; }, 'Unknown or unavailable placeholder'],
  'invalid date' => [function(&$config, &$rows) { $rows[6][8] = '2026/02/30 08:00'; }, 'Unknown or unavailable placeholder'],
  'missing loader' => [function(&$config, &$rows) { $config['datasets']['example_codes']['load']['file'] = 'load/missing.load'; }, 'Missing loader'],
  'missing helper' => [function(&$config, &$rows) { $config['datasets']['example_codes']['steps'][] = ['missing.php']; }, 'existing PHP helper'],
  'helper traversal' => [function(&$config, &$rows) { $config['datasets']['example_codes']['steps'][] = ['../src/csv_empty.php']; }, 'existing PHP helper'],
  'shell command forbidden' => [function(&$config, &$rows) { $config['datasets']['example_codes']['steps'][] = 'php csv_empty.php | cat'; }, 'Invalid transformation step'],
  'nonstring argument' => [function(&$config, &$rows) { $config['datasets']['example_codes']['steps'][] = ['csv_empty.php', 3]; }, 'arguments must be strings'],
  'null CSV input override' => [function(&$config, &$rows) { $config['datasets']['csv_direct']['source']['input'] = null; }, 'Invalid recipe values'],
  'null reader' => [function(&$config, &$rows) { $config['datasets']['csv_direct']['source']['reader'] = null; }, 'Invalid recipe values'],
  'null dependencies' => [function(&$config, &$rows) { $config['datasets']['example_codes']['prepare_requires'] = null; }, 'Invalid recipe values'],
  'output traversal' => [function(&$config, &$rows) { $config['datasets']['example_codes']['output'] = 'load/../escape.csv'; }, 'relative load/ path'],
  'missing output directory' => [function(&$config, &$rows) { $config['datasets']['example_codes']['output'] = 'load/absent/output.csv'; }, 'Missing or invalid directory'],
  'non-CSV output' => [function(&$config, &$rows) { $config['datasets']['example_codes']['output'] = 'load/output.load'; }, 'Output must be a CSV'],
  'conflicting output' => [function(&$config, &$rows) { $config['datasets']['example_tail']['output'] = 'load/example_codes.csv'; }, 'Conflicting output'],
  'equivalent output paths' => [function(&$config, &$rows) { $config['datasets']['example_tail']['output'] = 'load//example_codes.csv'; }, 'Conflicting output'],
  'duplicate snapshot source' => [function(&$config, &$rows) { $rows[] = array_replace($rows[0], [8 => '2025/01/01 08:00']); }, 'Conflicting output'],
  'conflicting partition' => [function(&$config, &$rows) {
    $rows[7][1] = 'other_periods';
    $rows[7][8] = '2026/01/01 08:00';
    $config['datasets']['other_periods'] = $config['datasets']['survey_periods'];
    $config['datasets']['other_periods']['output'] = 'load/other.csv';
  }, 'Conflicting partition'],
  'partition SQL identifier' => [function(&$config, &$rows) { $config['datasets']['survey_periods']['load']['table'] = 'table; DROP TABLE other'; }, 'require a table and partition'],
  'partition column identifier' => [function(&$config, &$rows) { $config['datasets']['survey_periods']['load']['partition'] = ['year;--' => '2026']; }, 'Invalid partition column'],
  'snapshot partition' => [function(&$config, &$rows) { $config['datasets']['example_codes']['load']['partition'] = ['year' => '2026']; }, 'cannot declare a partition'],
  'missing dependency recipe' => [function(&$config, &$rows) { $config['datasets']['example_codes']['prepare_requires'] = ['unknown']; }, 'Unknown preparation dependency'],
  'dependency outside inventory' => [function(&$config, &$rows) { $config['datasets']['example_codes']['prepare_requires'] = ['not_in_inventory']; }, 'exactly one inventory record'],
  'ambiguous dependency' => [function(&$config, &$rows) { $rows[] = array_replace($rows[3], [8 => '2025/01/01 08:00']); }, 'exactly one inventory record'],
  'dependency cycle' => [function(&$config, &$rows) { $config['datasets']['lookup']['prepare_requires'] = ['example_codes']; }, 'dependency cycle'],
  'missing workbook' => [function(&$config, &$rows) { $rows[0][3] = 'Absent'; }, 'ambiguous workbook'],
  'workbook path traversal' => [function(&$config, &$rows) { $rows[0][3] = '../New, data'; }, 'ambiguous workbook'],
  'sheet path traversal' => [function(&$config, &$rows) { $rows[0][4] = '../Shared'; }, 'Invalid workbook sheet'],
  'missing sheet name' => [function(&$config, &$rows) { $rows[0][4] = ''; }, 'Invalid workbook sheet'],
  'XLSX metadata override' => [function(&$config, &$rows) { $config['datasets']['example_codes']['source']['input'] = 'data/other.xlsx'; }, 'must come from sources.csv'],
  'directory without reader' => [function(&$config, &$rows) { unset($config['datasets']['lookup']['source']['reader']); }, 'require input and reader'],
  'metadata with execution' => [function(&$config, &$rows) { $config['datasets']['reference_only']['steps'] = []; }, 'Invalid or missing fields'],
  'unsupported JSON version' => [function(&$config, &$rows) { $config['version'] = 2; }, 'version 1'],
];
foreach ($invalid as $label => [$mutate, $expected]) {
  conversion_fixture(function($directory, $config, $rows) use ($label, $mutate, $expected) {
    $mutate($config, $rows);
    [$status, $output, $commands] = conversion_run($directory, $config, $rows);
    conversion_check($status === 1 && str_contains($output, $expected), "$label rejected explicitly: $output");
    conversion_check(!$commands, "$label rejected before conversion or database writes");
  });
}

conversion_fixture(function($directory, $config, $rows) {
  copy("$directory/data/New, data.XLSX", "$directory/data/New, data.xlsx");
  [$status, $output, $commands] = conversion_run($directory, $config, $rows);
  conversion_check($status === 1 && str_contains($output, 'ambiguous workbook') && !$commands,
    'case-insensitive extension collisions fail rather than choosing an arbitrary workbook');
});

conversion_fixture(function($directory, $config, $rows) {
  $rows[] = $rows[0];
  [$status, $output, $commands] = conversion_run($directory, $config, $rows);
  conversion_check($status === 0 && count(array_filter($commands,
    fn($command) => $command['command'] === 'pgloader' && end($command['args']) === 'load/example_codes.load')) === 1,
    "identical inventory records are not loaded twice: $output");
});

conversion_fixture(function($directory, $config, $rows) {
  symlink("$directory/data/New, data.XLSX", "$directory/data/Alias.xlsx");
  $rows[1][3] = 'Alias';
  [$status, $output, $commands] = conversion_run($directory, $config, $rows,
    ['--file', 'New, data', '--file', 'Alias']);
  conversion_check($status === 0 && count(array_filter($commands, fn($command) => $command['command'] === 'ssconvert')) === 1,
    "conversion caching uses resolved workbook paths, not manifest spelling: $output");
});

conversion_fixture(function($directory, $config, $rows) {
  $config['datasets']['example_tail']['steps'] = [];
  [$status, $output] = conversion_run($directory, $config, $rows, ['--file', 'New, data']);
  conversion_check($status === 0
    && file_get_contents("$directory/load/example_tail.csv") === "code,value\n#7,NA\n#8,12\n#9,5\n"
    && file_get_contents("$directory/load/example_codes.csv") === "007,__SSOT_NULL__\n008,12\n",
    "an untransformed dataset copies the raw shared sheet without consuming it: $output");
});

conversion_fixture(function($directory, $config, $rows) {
  $config['datasets']['example_details']['prepare_requires'] = ['lookup'];
  [$status, $output] = conversion_run($directory, $config, $rows, ['--file', 'New, data', '--dry-run']);
  conversion_check($status === 0 && substr_count($output, ' -> lookup ') === 1,
    "shared preparation dependencies are planned once: $output");
  [$status, $output, $commands] = conversion_run($directory, $config, $rows, ['--help']);
  conversion_check($status === 0 && str_contains($output, 'Usage:') && !$commands, 'help is read-only');
});

foreach (['ssconvert', 'pgloader', 'psql', 'reader', 'step', 'missing sheet'] as $failure) {
  conversion_fixture(function($directory, $config, $rows) use ($failure) {
    if ($failure === 'reader') {
      $config['datasets']['lookup']['source']['reader'] = ['fixture_reader.php', '--fail'];
    }
    elseif ($failure === 'step') {
      $config['datasets']['example_codes']['steps'][] = ['fixture_reader.php', '--fail'];
    }
    elseif ($failure === 'missing sheet') {
      $rows[0][4] = 'Absent';
    }
    [$status, $output, $commands] = conversion_run($directory, $config, $rows, [], $failure);
    conversion_check($status === 1, "$failure failure propagates: $output");
    conversion_check(!in_array(['--on-error-stop', 'load/sources.load'], array_column($commands, 'args'), true),
      "$failure failure never reports success or refreshes metadata");
    if (in_array($failure, ['ssconvert', 'reader', 'step', 'missing sheet'], true)) {
      conversion_check(!array_filter($commands, fn($command) => in_array($command['command'], ['psql', 'pgloader'], true)),
        "$failure stops preparation before all database loading");
    }
  });
}

foreach ([
  ['--file', 'unknown.xlsx'],
  ['--file'],
  ['--file', '--dry-run'],
  ['--file', '../New, data.xlsx'],
  ['--unknown'],
] as $arguments) {
  conversion_fixture(function($directory, $config, $rows) use ($arguments) {
    [$status, $output, $commands] = conversion_run($directory, $config, $rows, $arguments);
    conversion_check($status === 1 && !$commands, 'invalid CLI arguments fail without writes: ' . implode(' ', $arguments));
  });
}

conversion_fixture(function($directory, $config, $rows) {
  $config['datasets']['example_codes']['steps'][] = ['fixture_reader.php', '--require-lookup', '$(touch SHOULD_NOT_EXIST)'];
  [$status, $output] = conversion_run($directory, $config, $rows, ['--file', 'New, data']);
  conversion_check($status === 0 && !is_file("$directory/SHOULD_NOT_EXIST"), "arguments are never shell-evaluated: $output");
});

foreach ([
  'invalid JSON' => fn($directory) => file_put_contents("$directory/conversion.json", '{broken'),
  'missing JSON' => fn($directory) => unlink("$directory/conversion.json"),
  'reordered CSV header' => function($directory) {
  $csv = file_get_contents("$directory/load/sources.csv");
  file_put_contents("$directory/load/sources.csv", str_replace('label,endpoint', 'endpoint,label', $csv));
  },
  'malformed CSV record' => fn($directory) => file_put_contents("$directory/load/sources.csv", "too,few,columns\n", FILE_APPEND),
  'missing CSV input' => fn($directory) => unlink("$directory/data/raw.csv"),
  'missing metadata loader' => fn($directory) => unlink("$directory/load/sources.load"),
  'symlink output' => fn($directory) => symlink("$directory/data/raw.csv", "$directory/load/example_codes.csv"),
] as $label => $prepare) {
  conversion_fixture(function($directory, $config, $rows) use ($label, $prepare) {
  [$status, $output, $commands] = conversion_run($directory, $config, $rows, [], '', $prepare);
  conversion_check($status === 1 && str_contains($output, 'ERROR:') && !$commands, "$label rejected before writes: $output");
  });
}

conversion_fixture(function($directory, $config, $rows) {
  $config['datasets']['survey_periods']['load']['partition'] = ['label' => "O'Brien"];
  [$status, $output, $commands] = conversion_run($directory, $config, $rows, ['--file', 'Survey Jan.xlsx']);
  $sql = array_values(array_filter($commands, fn($command) => $command['command'] === 'psql'));
  conversion_check($status === 0 && end($sql[0]['args']) === 'DELETE FROM "survey_periods" WHERE "label"=\'O\'\'Brien\'',
  "partition values are SQL-quoted, not interpolated as SQL: $output");
});

foreach ([
  ['ssconvert', SIGINT, false],
  ['pgloader', SIGINT, false],
  ['ssconvert', SIGTERM, false],
  ['pgloader', SIGTERM, true],
] as [$command, $signal, $ignore_term]) {
  conversion_fixture(function($directory, $config, $rows) use ($command, $signal, $ignore_term) {
    conversion_cancel($directory, $config, $rows, $command, $signal, $ignore_term);
  });
}

echo "PASS: $checks conversion pipeline checks.\n";
