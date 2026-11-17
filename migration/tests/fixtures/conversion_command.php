#!/usr/bin/env php
<?php

// Stand-ins for external tools; real PHP readers and transformations still run.
$command = basename($argv[0]);
$entry = ['command' => $command, 'args' => array_slice($argv, 1)];
if ($command === 'pgloader') {
  $entry['source'] = getenv('SOURCE');
  $entry['csv'] = $entry['source'] ? file_get_contents($entry['source']) : null;
}
file_put_contents(getenv('SSOT_TEST_LOG'), json_encode($entry, JSON_THROW_ON_ERROR) . "\n", FILE_APPEND);
if (getenv('SSOT_TEST_WAIT') === $command) {
  if (getenv('SSOT_TEST_IGNORE_TERM') === '1') {
    pcntl_signal(SIGTERM, SIG_IGN);
  }
  $pid_file = getenv('SSOT_TEST_CHILD');
  file_put_contents("$pid_file.tmp", (string)getmypid());
  rename("$pid_file.tmp", $pid_file);
  while (true) {
    usleep(100000);
  }
}
if (getenv('SSOT_TEST_FAIL') === $command) {
  fwrite(STDERR, "Fixture $command failure.\n");
  exit(1);
}
if ($command === 'ssconvert') {
  $sheets = json_decode(file_get_contents($argv[count($argv) - 2]), true, 512, JSON_THROW_ON_ERROR);
  foreach ($sheets as $name => $rows) {
    $stream = fopen(str_replace('%s', $name, $argv[count($argv) - 1]), 'w');
    foreach ($rows as $row) {
      fputcsv($stream, $row);
    }
    fclose($stream);
  }
}
