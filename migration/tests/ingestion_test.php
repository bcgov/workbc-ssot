<?php

/**
 * Focused extraction, normalization and source-selection regression tests.
 *
 * Usage: php migration/tests/ingestion_test.php
 */

chdir(dirname(__DIR__));
$tests = 0;
function check_command(array $command, string $input, int $status, string $expected, bool $exact = false): void {
  global $tests;
  $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
    $pipes, null, array_merge(getenv(), ['PGHOST' => '/nonexistent/ssot-ingestion-test', 'PGCONNECT_TIMEOUT' => '1']));
  fwrite($pipes[0], $input);
  fclose($pipes[0]);
  $output = stream_get_contents($pipes[1]);
  $error = stream_get_contents($pipes[2]);
  fclose($pipes[1]);
  fclose($pipes[2]);
  $actual = proc_close($process);
  if ($actual !== $status || ($exact ? $output !== $expected : strpos($output . $error, $expected) === false)) {
    throw new RuntimeException(implode(' ', $command) . ": exit $actual\n$output\n$error");
  }
  $tests++;
}

check_command(['php', 'src/csv_extract.php', '--range', 'B2-C2', '--range', 'B4-C4'],
  "h,h,h\na,b,c\nd,e,f\ng,h,i\n", 0, "b,c\nh,i\n");
check_command(['php', 'src/csv_extract.php', '--range', 'B2-C2;B4-C4'],
  "h,h,h\na,b,c\nd,e,f\ng,h,i\n", 0, "b,c\nh,i\n");
check_command(['php', 'src/csv_extract.php', '--range', 'B2:C2', '--column-offset', '1'],
  "h,h\nb,c\n", 0, "b,c\n");
check_command(['php', 'src/csv_extract.php', '--range', '4-2'], "a\nb\n", 1, 'Reversed range');
check_command(['php', 'src/csv_extract.php', '--range', 'A5-B6'], "a,b\n", 1, 'no rows');
check_command(['php', 'src/csv_null.php', '--loader', 'load/wages.load'],
  "#00123,title,NA,n/A,N.A.,-,   ,0,valid\n", 0,
  "#00123,title,__SSOT_NULL__,__SSOT_NULL__,__SSOT_NULL__,__SSOT_NULL__,__SSOT_NULL__,0,valid\n", true);
check_command(['bash', '-o', 'pipefail', '-c',
  'php src/csv_trimpad.php --column 1:L:0:0:# --column 2:L:0:0:# | php src/csv_null.php --loader load/career_trek.load'],
  ",##21220,185,title,title,episode,link,location,region,description\n" .
  "#00123,21#220,186,title,title,episode,link,location,region,description\n", 0,
  ",21220,185,title,title,episode,link,location,region,description\n" .
  "00123,21#220,186,title,title,episode,link,location,region,description\n", true);
check_command(['php', 'src/csv_null.php', '--loader', 'load/wages.load', '--pad-missing'],
  "00123,title,NA\n", 0, "00123,title," . implode(',', array_fill(0, 7, '__SSOT_NULL__')) . "\n", true);
check_command(['php', 'src/csv_null.php', '--loader', 'load/wages.load'],
  "00123,title,__SSOT_NULL__,1,1,1,1,1,source\n", 1, 'reserved SQL NULL marker');
check_command(['php', 'src/csv_null.php', '--loader', 'load/wages.load'],
  "00123,title\n", 1, 'fewer fields');
check_command(['php', 'src/csv_null.php', '--loader', 'load/occupational_interests.load'],
  "00123,option, N/A \n", 0, "00123,option,__SSOT_NULL__\n", true);
check_command(['php', 'src/ssot_ingest.php', '--file', 'not-registered.xlsx', '--dry-run'],
  '', 1, 'Unregistered workbook');
check_command(['php', 'src/ssot_ingest.php', '--file', 'WorkBC_Career_Profile_Data_2026-08-05.xlsx', '--dry-run'],
  '', 0, "WorkBC_Career_Profile_Data_2026-08-05.xlsx / Regional Outlook -> career_regional (A5-AC516)\n" .
  "WorkBC_Career_Profile_Data_2026-08-05.xlsx / Provincial Outlook -> career_provincial (A4-L515)\n", true);
check_command(['php', 'src/ssot_ingest.php', '--file', 'FYP - Categories, Areas of interest, and NOCs 2025-04-23.xlsx', '--dry-run'],
  '', 0, "FYP - Categories, Areas of interest, and NOCs 2025-04-23.xlsx / CONSOLIDATED LIST -> fyp_categories_interests (A2-F909)\n", true);
check_command(['php', 'src/ssot_ingest.php', '--file'], '', 1, 'incomplete argument');
try {
  chdir(sys_get_temp_dir());
  check_command(['php', dirname(__DIR__) . '/src/ssot_ingest.php',
    '--file', 'WorkBC_Career_Profile_Data_2026-08-05.xlsx', '--dry-run'],
    '', 0, "WorkBC_Career_Profile_Data_2026-08-05.xlsx / Regional Outlook -> career_regional (A5-AC516)\n" .
    "WorkBC_Career_Profile_Data_2026-08-05.xlsx / Provincial Outlook -> career_provincial (A4-L515)\n", true);
}
finally {
  chdir(dirname(__DIR__));
}
echo "PASS: $tests ingestion regression tests.\n";
