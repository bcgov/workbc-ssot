<?php

/**
 * Transform the WorkBC LMS data into a format digestible by pgloader.
 *
 * Usage: php src/monthly_labour_market_update.php year{YYYY} month{1..12} < /path/to/input.csv > /path/to/output.csv
 */

$usage = "Usage: php src/monthly_labour_market_update.php year{YYYY} month{1..12} < /path/to/input.csv > /path/to/output.csv";
if (count($argv) < 3) {
    die("Expecting 2 arguments.\n" . $usage . PHP_EOL);
}

// Invoke correct script based on report date.
$year = intval($argv[1]);
$month = intval($argv[2]);
if ($year < 2023 || ($year == 2023 && $month < 3)) {
    include __DIR__ . '/monthly_labour_market_update_v1.php';
}
else if ($year == 2023 && $month <= 8) {
    include __DIR__ . '/monthly_labour_market_update_v2.php';
}
else {
    include __DIR__ . '/monthly_labour_market_update_v3.php';
}
