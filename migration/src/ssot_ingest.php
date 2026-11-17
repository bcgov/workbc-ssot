<?php

/**
 * Execute conversion.json recipes for the inventory in load/sources.csv.
 */

final class IngestInterrupted extends RuntimeException {}

function ingest_run(array $command, ?string $input = null, ?string $output = null, array $environment = []): void {
  $process = null;
  $status = null;
  try {
    $process = proc_open($command, [
      0 => ['file', $input ?? '/dev/null', 'r'],
      1 => $output === null ? STDOUT : ['file', $output, 'w'],
      2 => STDERR,
    ], $pipes, null, array_merge(getenv(), $environment));
    if (!is_resource($process)) {
      throw new RuntimeException('Unable to start command: ' . implode(' ', $command));
    }
    do {
      $status = proc_get_status($process);
      if ($status['running']) {
        usleep(50000);
      }
    } while ($status['running']);
    if ($status['exitcode'] !== 0) {
      throw new RuntimeException('Command failed: ' . implode(' ', $command));
    }
  }
  finally {
    if (is_resource($process)) {
      pcntl_sigprocmask(SIG_BLOCK, [SIGINT, SIGTERM], $previous_mask);
      try {
        if ($status === null || $status['running']) {
          proc_terminate($process, SIGTERM);
          $deadline = microtime(true) + 2;
          do {
            $status = proc_get_status($process);
            if ($status['running']) {
              usleep(50000);
            }
          } while ($status['running'] && microtime(true) < $deadline);
          if ($status['running']) {
            proc_terminate($process, SIGKILL);
          }
        }
        proc_close($process);
      }
      finally {
        pcntl_sigprocmask(SIG_SETMASK, $previous_mask);
      }
    }
  }
}

function ingest_keys(array $value, array $allowed, array $required, string $label): void {
  if (array_diff(array_keys($value), $allowed) || array_diff($required, array_keys($value))) {
    throw new InvalidArgumentException("Invalid or missing fields in $label.");
  }
}

function ingest_path(string $path, string $directory): string {
  if (!str_starts_with($path, "$directory/") || preg_match('~(^|/)\.\.?(/|$)|[\\\\\x00]~', $path)) {
    throw new InvalidArgumentException("Expected a relative $directory/ path: $path");
  }
  $parent = realpath(dirname($path));
  $root = realpath($directory);
  if ($parent === false || $root === false || ($parent !== $root && !str_starts_with($parent, "$root/"))) {
    throw new InvalidArgumentException("Missing or invalid directory for $path");
  }
  return $directory . '/' . ltrim(substr($parent, strlen($root)) . '/' . basename($path), '/');
}

function ingest_expand(string $value, array $context): string {
  return preg_replace_callback('/\{([^{}]+)\}/', function($match) use ($context) {
    if (!isset($context[$match[1]]) || $context[$match[1]] === '') {
      throw new InvalidArgumentException("Unknown or unavailable placeholder: {$match[0]}");
    }
    return $context[$match[1]];
  }, $value);
}

function ingest_command(array $arguments, array $context): array {
  if (!array_is_list($arguments) || !$arguments || !is_string($arguments[0]) ||
    !preg_match('/^[a-z][a-z0-9_]*\.php$/', $arguments[0]) || !is_file(__DIR__ . '/' . $arguments[0])) {
    throw new InvalidArgumentException('Expected an existing PHP helper in src/.');
  }
  $command = [PHP_BINARY, __DIR__ . '/' . array_shift($arguments)];
  foreach ($arguments as $argument) {
    if (!is_string($argument)) {
      throw new InvalidArgumentException('Helper arguments must be strings.');
    }
    $command[] = ingest_expand($argument, $context);
  }
  return $command;
}

function ingest_recipes(): array {
  $config = json_decode(file_get_contents('conversion.json'), true, 512, JSON_THROW_ON_ERROR);
  if (!is_array($config) || ($config['version'] ?? null) !== 1 || !is_array($config['datasets'] ?? null)) {
    throw new InvalidArgumentException('Expected conversion.json version 1 and a datasets object.');
  }
  ingest_keys($config, ['version', 'datasets'], ['version', 'datasets'], 'conversion.json');
  foreach ($config['datasets'] as $endpoint => $recipe) {
    if (!preg_match('/^[a-z][a-z0-9_]*$/', $endpoint) || !is_array($recipe) || !is_array($recipe['source'] ?? null)) {
      throw new InvalidArgumentException("Invalid recipe for $endpoint");
    }
    ingest_keys($recipe, ['source', 'prepare_requires', 'output', 'steps', 'load'], ['source'], $endpoint);
    $source = $recipe['source'];
    ingest_keys($source, ['type', 'input', 'reader'], ['type'], "$endpoint source");
    if (!in_array($source['type'], ['xlsx', 'csv', 'directory', 'metadata'], true)) {
      throw new InvalidArgumentException("Unknown source type for $endpoint");
    }
    if ($source['type'] === 'metadata') {
      ingest_keys($recipe, ['source'], ['source'], "$endpoint metadata recipe");
      ingest_keys($source, ['type'], ['type'], "$endpoint metadata source");
      continue;
    }
    ingest_keys($recipe, ['source', 'prepare_requires', 'output', 'steps', 'load'],
      ['source', 'output', 'steps', 'load'], $endpoint);
    if ($source['type'] === 'xlsx' && count($source) !== 1) {
      throw new InvalidArgumentException("XLSX input and sheet must come from sources.csv: $endpoint");
    }
    if ($source['type'] === 'directory' && (!isset($source['input']) || !is_array($source['reader'] ?? null))) {
      throw new InvalidArgumentException("Directory sources require input and reader: $endpoint");
    }
    if ((array_key_exists('input', $source) && !is_string($source['input'])) ||
      (array_key_exists('reader', $source) && !is_array($source['reader'])) ||
      !is_string($recipe['output']) || !is_array($recipe['steps']) || !array_is_list($recipe['steps']) ||
      !is_array($recipe['load']) ||
      (array_key_exists('prepare_requires', $recipe) && !is_array($recipe['prepare_requires'])) ||
      !array_is_list($recipe['prepare_requires'] ?? [])) {
      throw new InvalidArgumentException("Invalid recipe values for $endpoint");
    }
    $load = $recipe['load'];
    ingest_keys($load, ['file', 'order', 'mode', 'table', 'partition'], ['file', 'order', 'mode'], "$endpoint load");
    if (!is_string($load['file']) || !is_int($load['order']) || !in_array($load['mode'], ['snapshot', 'partition'], true)) {
      throw new InvalidArgumentException("Invalid load settings for $endpoint");
    }
    if ($load['mode'] === 'partition') {
      if (!is_string($load['table'] ?? null) || !preg_match('/^[a-z][a-z0-9_]*$/', $load['table']) ||
        empty($load['partition']) || !is_array($load['partition'])) {
        throw new InvalidArgumentException("Partition loads require a table and partition: $endpoint");
      }
      foreach ($load['partition'] as $column => $value) {
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $column) || !is_string($value)) {
          throw new InvalidArgumentException("Invalid partition column or value: $endpoint");
        }
      }
    }
    elseif (isset($load['table']) || isset($load['partition'])) {
      throw new InvalidArgumentException("Snapshot loads cannot declare a partition: $endpoint");
    }
    foreach ($recipe['prepare_requires'] ?? [] as $dependency) {
      if (!is_string($dependency) || !isset($config['datasets'][$dependency])) {
        throw new InvalidArgumentException("Unknown preparation dependency for $endpoint");
      }
    }
  }
  return $config['datasets'];
}

function ingest_record(array $record, array $recipe, array $workbooks): array {
  $endpoint = $record['endpoint'];
  $source = $recipe['source'];
  $context = ['range' => $record['range']];
  $date = DateTimeImmutable::createFromFormat('!Y/m/d H:i', $record['period']);
  if ($date && $date->format('Y/m/d H:i') === $record['period']) {
    $context += ['year' => $date->format('Y'), 'month' => $date->format('n'), 'month02' => $date->format('m')];
  }
  $loader = ingest_path($recipe['load']['file'], 'load');
  if (!is_file($loader) || !is_readable($loader)) {
    throw new InvalidArgumentException("Missing loader for $endpoint: $loader");
  }
  $context['loader'] = $loader;
  if ($source['type'] === 'xlsx') {
    $stem = preg_replace('/\.xlsx$/i', '', $record['filename']);
    if (basename($stem) !== $stem || strpos($stem, '\\') !== false || count($workbooks[$stem] ?? []) !== 1) {
      throw new InvalidArgumentException("Invalid, missing or ambiguous workbook: {$record['filename']}");
    }
    if ($record['sheet'] === '' || preg_match('~[/\\\\\x00]~', $record['sheet'])) {
      throw new InvalidArgumentException("Invalid workbook sheet for $endpoint");
    }
    if ($record['range'] !== '') {
      foreach (explode(';', $record['range']) as $range) {
        if (!preg_match('/^([A-Z]+)([1-9][0-9]*)[-:]([A-Z]+)([1-9][0-9]*)$/', $range, $match) ||
          (int)$match[2] > (int)$match[4] || strlen($match[1]) > strlen($match[3]) ||
          (strlen($match[1]) === strlen($match[3]) && strcmp($match[1], $match[3]) > 0)) {
          throw new InvalidArgumentException("Invalid range for $endpoint: $range");
        }
      }
    }
    $input = $workbooks[$stem][0];
  }
  else {
    $input = ingest_path($source['input'] ?? 'data/' . $record['filename'], 'data');
    if (($source['type'] === 'directory' ? !is_dir($input) : !is_file($input)) || !is_readable($input)) {
      throw new InvalidArgumentException("Missing or unreadable input for $endpoint: $input");
    }
  }
  $context['input'] = $input;
  $output = ingest_path(ingest_expand($recipe['output'], $context), 'load');
  if (!str_ends_with($output, '.csv') || is_dir($output) || is_link($output)) {
    throw new InvalidArgumentException("Output must be a CSV: $output");
  }
  $steps = [];
  foreach ($recipe['steps'] as $step) {
    if (!is_array($step)) {
      throw new InvalidArgumentException("Invalid transformation step for $endpoint");
    }
    $steps[] = ingest_command($step, $context);
  }
  $partition = [];
  foreach ($recipe['load']['partition'] ?? [] as $column => $value) {
    $partition[$column] = ingest_expand($value, $context);
  }
  return $record + [
    'recipe' => $recipe, 'input' => $input, 'output' => $output, 'loader' => $loader,
    'steps' => $steps, 'partition' => $partition,
    'reader' => isset($source['reader']) ? ingest_command($source['reader'], $context) : null,
  ];
}

function ingest_plan(array $files): array {
  $recipes = ingest_recipes();
  $stream = fopen('load/sources.csv', 'r');
  try {
    $header = fgetcsv($stream);
    if ($header !== ['label', 'endpoint', 'datapoint', 'filename', 'sheet', 'range', 'author', 'date', 'period']) {
      throw new InvalidArgumentException('Unexpected sources.csv header/order; it must match sources.load.');
    }
    $inventory = [];
    $by_endpoint = [];
    $selected = [];
    $found = [];
    while (($row = fgetcsv($stream)) !== false) {
      if (count($row) !== count($header)) {
        throw new InvalidArgumentException('Malformed source metadata record.');
      }
      $record = array_combine($header, $row);
      if ($record['datapoint'] !== '') {
        continue;
      }
      $endpoint = $record['endpoint'];
      if (!isset($recipes[$endpoint])) {
        throw new InvalidArgumentException("Missing conversion recipe for $endpoint");
      }
      if ($recipes[$endpoint]['source']['type'] === 'metadata') {
        continue;
      }
      $key = json_encode($row, JSON_THROW_ON_ERROR);
      if (isset($inventory[$key])) {
        continue;
      }
      $inventory[$key] = $record + ['index' => count($inventory)];
      $by_endpoint[$endpoint][] = $key;
      $stem = preg_replace('/\.xlsx$/i', '', $record['filename']);
      if (!$files || ($recipes[$endpoint]['source']['type'] === 'xlsx' && in_array($stem, $files, true))) {
        $selected[$key] = true;
        $found[] = $stem;
      }
    }
  }
  finally {
    fclose($stream);
  }
  if (array_diff($files, $found)) {
    throw new InvalidArgumentException('Unregistered workbook(s): ' . implode(', ', array_diff($files, $found)));
  }
  if (!$selected) {
    throw new InvalidArgumentException('No ingestion records selected.');
  }
  $workbooks = [];
  foreach (new DirectoryIterator('data') as $file) {
    if ($file->isFile() && strtolower($file->getExtension()) === 'xlsx') {
      $workbooks[$file->getBasename('.' . $file->getExtension())][] = $file->getRealPath();
    }
  }
  $planned = [];
  $visiting = [];
  $outputs = [];
  $partitions = [];
  $visit = function($key) use (&$visit, &$planned, &$visiting, &$outputs, &$partitions, $inventory, $by_endpoint, $recipes, $workbooks, $selected): void {
    if (isset($planned[$key])) {
      return;
    }
    $record = $inventory[$key];
    $endpoint = $record['endpoint'];
    if (isset($visiting[$key])) {
      throw new InvalidArgumentException("Preparation dependency cycle at $endpoint");
    }
    $visiting[$key] = true;
    foreach ($recipes[$endpoint]['prepare_requires'] ?? [] as $dependency) {
      if (count($by_endpoint[$dependency] ?? []) !== 1) {
        throw new InvalidArgumentException("Preparation dependency needs exactly one inventory record: $dependency");
      }
      $visit($by_endpoint[$dependency][0]);
    }
    $record = ingest_record($record, $recipes[$endpoint], $workbooks);
    if (isset($outputs[$record['output']])) {
      throw new InvalidArgumentException("Conflicting output: {$record['output']}");
    }
    $outputs[$record['output']] = true;
    if ($record['partition']) {
      $identity = $record['partition'];
      ksort($identity);
      $identity = $record['recipe']['load']['table'] . ':' . json_encode($identity, JSON_THROW_ON_ERROR);
      if (isset($partitions[$identity])) {
        throw new InvalidArgumentException("Conflicting partition for $endpoint");
      }
      $partitions[$identity] = true;
    }
    $record['selected'] = isset($selected[$key]);
    $planned[$key] = $record;
    unset($visiting[$key]);
  };
  foreach (array_keys($selected) as $key) {
    $visit($key);
  }
  if (!is_readable('load/sources.load')) {
    throw new InvalidArgumentException('Missing source metadata loader.');
  }
  return array_values($planned);
}

function ingest_execute(array $records, bool $filtered, string $temporary): void {
  $converted = [];
  foreach ($records as $i => $record) {
    $input = $record['input'];
    if ($record['recipe']['source']['type'] === 'xlsx') {
      if (!isset($converted[$input])) {
        $prefix = "$temporary/workbook-" . count($converted);
        echo 'Converting ' . basename($input) . "\n";
        ingest_run(['ssconvert', '--export-type=Gnumeric_stf:stf_csv', '--export-file-per-sheet', $input, "$prefix-%s.csv"]);
        $converted[$input] = $prefix;
      }
      $input = $converted[$input] . "-{$record['sheet']}.csv";
      if (!is_file($input)) {
        throw new RuntimeException("Workbook sheet missing: {$record['filename']} / {$record['sheet']}");
      }
    }
    if ($record['reader'] !== null) {
      $next = "$temporary/reader-$i.csv";
      ingest_run($record['reader'], $record['recipe']['source']['type'] === 'csv' ? $input : null, $next);
      $input = $next;
    }
    foreach ($record['steps'] as $j => $step) {
      $next = "$temporary/step-$i-$j.csv";
      ingest_run($step, $input, $next);
      $input = $next;
    }
    // Copy, never move, because another dataset may share the same raw sheet.
    if (!copy($input, $record['output'])) {
      throw new RuntimeException("Unable to publish {$record['output']}");
    }
  }
  $loads = array_values(array_filter($records, fn($record) => $record['selected']));
  usort($loads, fn($a, $b) => ($a['recipe']['load']['order'] <=> $b['recipe']['load']['order']) ?: ($a['index'] <=> $b['index']));
  $reset = [];
  foreach ($loads as $record) {
    $load = $record['recipe']['load'];
    if ($load['mode'] === 'partition') {
      $table = '"' . $load['table'] . '"';
      if ($filtered) {
        $conditions = [];
        foreach ($record['partition'] as $column => $value) {
          $conditions[] = '"' . $column . "\"='" . str_replace("'", "''", $value) . "'";
        }
        ingest_run(['psql', '-X', '-v', 'ON_ERROR_STOP=1', '-c', "DELETE FROM $table WHERE " . implode(' AND ', $conditions)]);
      }
      elseif (!isset($reset[$table])) {
        ingest_run(['psql', '-X', '-v', 'ON_ERROR_STOP=1', '-c', "DROP TABLE IF EXISTS $table"]);
        $reset[$table] = true;
      }
    }
    ingest_run(['pgloader', '--on-error-stop', $record['loader']], null, null, ['SOURCE' => getcwd() . '/' . $record['output']]);
  }
  ingest_run(['pgloader', '--on-error-stop', 'load/sources.load']);
  echo "SSOT INGESTION COMPLETED.\n";
}

function ingest_main(array $arguments): int {
  $temporary = null;
  $signal_handlers = [];
  $async_signals = null;
  set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
  });
  try {
    if (!extension_loaded('pcntl')) {
      throw new RuntimeException('The PHP PCNTL extension is required for interruptible ingestion.');
    }
    $async_signals = pcntl_async_signals(true);
    foreach ([SIGINT, SIGTERM] as $signal) {
      $signal_handlers[$signal] = pcntl_signal_get_handler($signal);
      pcntl_signal($signal, function(int $signal): void {
        pcntl_signal(SIGINT, SIG_IGN);
        pcntl_signal(SIGTERM, SIG_IGN);
        throw new IngestInterrupted('Ingestion interrupted by ' . ($signal === SIGINT ? 'SIGINT' : 'SIGTERM') . '.', 128 + $signal);
      });
    }
    chdir(dirname(__DIR__));
    $files = [];
    $dry_run = false;
    for ($i = 1; $i < count($arguments); $i++) {
      if ($arguments[$i] === '--help') {
        echo "Usage: php src/ssot_ingest.php [--file Workbook.xlsx ...] [--dry-run]\n";
        return 0;
      }
      if ($arguments[$i] === '--dry-run') {
        $dry_run = true;
      }
      elseif ($arguments[$i] === '--file' && isset($arguments[$i + 1]) &&
        $arguments[$i + 1] !== '' && !str_starts_with($arguments[$i + 1], '--')) {
        $filename = $arguments[++$i];
        if (basename($filename) !== $filename || strpos($filename, '\\') !== false) {
          throw new InvalidArgumentException('Expected a workbook filename without a directory.');
        }
        $files[] = preg_replace('/\.xlsx$/i', '', $filename);
      }
      else {
        throw new InvalidArgumentException('Unknown or incomplete argument: ' . $arguments[$i]);
      }
    }
    $records = ingest_plan(array_unique($files));
    foreach ($records as $record) {
      $filename = $record['recipe']['source']['type'] === 'xlsx'
        ? basename($record['input']) . ' / ' . $record['sheet'] : $record['filename'];
      echo "$filename -> {$record['endpoint']} ({$record['range']})" . ($record['selected'] ? '' : ' [prepare only]') . "\n";
    }
    if (!$dry_run) {
      $temporary = 'load/.ingest-' . bin2hex(random_bytes(8));
      mkdir($temporary, 0700);
      ingest_execute($records, (bool)$files, $temporary);
    }
    return 0;
  }
  catch (Throwable $error) {
    fwrite(STDERR, 'ERROR: ' . $error->getMessage() . PHP_EOL);
    return $error instanceof IngestInterrupted ? $error->getCode() : 1;
  }
  finally {
    if ($temporary !== null && is_dir($temporary)) {
      foreach (new DirectoryIterator($temporary) as $file) {
        if (!$file->isDot()) {
          unlink($file->getPathname());
        }
      }
      rmdir($temporary);
    }
    foreach ($signal_handlers as $signal => $handler) {
      pcntl_signal($signal, $handler);
    }
    if ($async_signals !== null) {
      pcntl_async_signals($async_signals);
    }
    restore_error_handler();
  }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
  exit(ingest_main($argv));
}
