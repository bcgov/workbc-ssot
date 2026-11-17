<?php

try {
  if (($argv[1] ?? '') === '--fail') {
    throw new RuntimeException('Fixture reader failure.');
  }
  if (($argv[1] ?? '') === '--require-lookup') {
    if (!is_file('load/lookup.csv') || file_get_contents('load/lookup.csv') !== "key,label\nx,Example\n") {
      throw new RuntimeException('Lookup was not prepared first.');
    }
    stream_copy_to_stream(STDIN, STDOUT);
  }
  else {
    $path = $argv[1];
    if (is_dir($path)) {
      $path .= '/input.csv';
    }
    if (!is_file($path)) {
      throw new RuntimeException("Missing fixture input: $path");
    }
    readfile($path);
  }
}
catch (Throwable $error) {
  fwrite(STDERR, $error->getMessage() . "\n");
  exit(1);
}
