<?php

/**
 * Register dispatch metadata after verifying the uploaded workbook.
 *
 * Usage: php src/register_sources.php load/sources.csv data < event.json
 */

function register_sources(string $csv, string $filename, array $upload): string {
  if (basename($filename) !== $filename || strpos($filename, '\\') !== FALSE || !preg_match('/\.xlsx$/i', $filename)) {
    throw new \InvalidArgumentException('Expected an XLSX workbook filename without a directory.');
  }
  $year = (int) ($upload['year'] ?? 0);
  $month = (int) ($upload['month'] ?? 0);
  $date = \DateTimeImmutable::createFromFormat('!Y/m/d H:i', $upload['date'] ?? '');
  if (!checkdate($month, 1, $year) || !$date || $date->format('Y/m/d H:i') !== $upload['date']) {
    throw new \InvalidArgumentException('Invalid source period or update date.');
  }
  if (empty($upload['datasets'])) {
    throw new \InvalidArgumentException('The upload must identify its datasets.');
  }
  $stream = fopen('php://temp', 'r+');
  try {
    fwrite($stream, $csv);
    rewind($stream);
    $header = fgetcsv($stream);
    $expected = ['label', 'endpoint', 'datapoint', 'filename', 'sheet', 'range', 'author', 'date', 'period'];
    if ($header !== $expected) {
      throw new \UnexpectedValueException('Invalid sources.csv header.');
    }
    $records = [];
    while (($row = fgetcsv($stream)) !== FALSE) {
      if (count($row) !== count($header)) {
        throw new \UnexpectedValueException('Invalid sources.csv record.');
      }
      $records[] = array_combine($header, $row);
    }
  }
  finally {
    fclose($stream);
  }
  $period = sprintf('%04d/%02d/01 08:00', $year, $month);
  foreach ($upload['datasets'] as $entry) {
    if (empty($entry['endpoint']) || empty($entry['sheet'])) {
      throw new \InvalidArgumentException('Each dataset must identify its endpoint and sheet.');
    }
    $range = str_replace(':', '-', $entry['range'] ?? '');
    if ($range !== '' && !preg_match('/^[A-Z]+[1-9][0-9]*-[A-Z]+[1-9][0-9]*(;[A-Z]+[1-9][0-9]*-[A-Z]+[1-9][0-9]*)*$/', $range)) {
      throw new \InvalidArgumentException('Invalid dataset range.');
    }
    // Older queued uploads omitted the match rule: only monthly records had no range.
    $match = $entry['match'] ?? ($range === '' ? 'period' : 'endpoint');
    if (!in_array($match, ['endpoint', 'period'], TRUE)) {
      throw new \InvalidArgumentException('Invalid source matching rule.');
    }
    $template = array_fill_keys($header, '');
    $remaining = [];
    foreach ($records as $record) {
      if ($record['endpoint'] === $entry['endpoint'] && $record['datapoint'] === '') {
        $template = $record;
        if ($match === 'endpoint' || $record['period'] === $period) {
          continue;
        }
      }
      $remaining[] = $record;
    }
    $record = array_replace($template, [
      'endpoint' => $entry['endpoint'],
      'datapoint' => '',
      'filename' => substr($filename, 0, -5),
      'sheet' => $entry['sheet'],
      'range' => $range,
      'period' => $period,
      'date' => $upload['date'],
    ]);
    foreach (['label', 'author'] as $field) {
      if (array_key_exists($field, $upload) && $upload[$field] !== NULL) {
        $record[$field] = $upload[$field];
      }
    }
    $remaining[] = $record;
    $records = $remaining;
  }
  $render = static function (array $row): string {
    return implode(',', array_map(static function ($value) {
      return $value === '' ? '' : '"' . str_replace('"', '""', $value) . '"';
    }, $row)) . "\n";
  };
  $result = $render($header);
  foreach ($records as $record) {
    $result .= $render(array_map(static fn($column) => $record[$column], $header));
  }
  return $result;
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== __FILE__) {
  return;
}

$temporary = null;
try {
  set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
  });
  if (count($argv) !== 3) {
    throw new InvalidArgumentException('Usage: php src/register_sources.php sources.csv data-directory < event.json');
  }
  $event = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
  $payload = $event['client_payload'] ?? null;
  if (!is_array($payload) || !is_array($payload['metadata'] ?? null)) {
    throw new InvalidArgumentException('Expected client_payload.metadata in the dispatch event.');
  }
  $filename = $payload['filename'] ?? null;
  if (!is_string($filename) || basename($filename) !== $filename || strpos($filename, '\\') !== false) {
    throw new InvalidArgumentException('Expected a workbook filename without a directory.');
  }
  $sha = $payload['workbook_sha'] ?? null;
  if (!is_string($sha) || !preg_match('/^[a-f0-9]{40}$/i', $sha)) {
    throw new InvalidArgumentException('Expected the uploaded workbook Git blob SHA.');
  }
  $workbook = $argv[2] . '/' . $filename;
  if (!is_file($workbook) || !is_readable($workbook)) {
    throw new RuntimeException("Workbook missing or unreadable: $filename");
  }
  $hash = hash_init('sha1');
  hash_update($hash, 'blob ' . filesize($workbook) . "\0");
  if (!hash_update_file($hash, $workbook) || !hash_equals(strtolower($sha), hash_final($hash))) {
    throw new RuntimeException('Workbook differs from the dispatched upload; refusing stale metadata.');
  }
  $upload = array_replace($payload['metadata'], [
    'year' => $payload['year'] ?? null,
    'month' => $payload['month'] ?? null,
    'date' => $payload['date'] ?? null,
  ]);
  $manifest = $argv[1];
  $updated = register_sources(file_get_contents($manifest), $filename, $upload);
  $temporary = tempnam(dirname($manifest), '.sources-');
  if (file_put_contents($temporary, $updated) !== strlen($updated)) {
    throw new RuntimeException('Unable to write the updated source metadata.');
  }
  chmod($temporary, fileperms($manifest) & 0777);
  rename($temporary, $manifest);
  $temporary = null;
  echo "Source metadata registered for $filename.\n";
}
catch (Throwable $error) {
  fwrite(STDERR, 'ERROR: ' . $error->getMessage() . PHP_EOL);
  $failed = true;
}
finally {
  if ($temporary !== null && is_file($temporary)) {
    unlink($temporary);
  }
}
exit(isset($failed) ? 1 : 0);
