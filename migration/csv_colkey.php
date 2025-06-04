<?php

/**
* Inject CSV column that can be used as a unique key based on the value of another column. Also supports B.C. Industry / Region keys.
*
* Usage: php csv_colkey --column N [--industries | --regions] < /path/to/input.csv > /path/to/output.csv
*/

$opts = getopt('', [
  'column:',
  'industries',
  'regions',
]);
$usage = 'Usage: php csv_colkey --column N [--industries | --regions] < /path/to/input.csv > /path/to/output.csv';
if (!array_key_exists('column', $opts)) {
  die("Column specification not found\n" . $usage . PHP_EOL);
}
$opts['column'] = (array)$opts['column'];

// Load industries table if needed.
const COL_INDUSTRY_KEY = 0;
const COL_INDUSTRY_NAME = 1;
const INDUSTRY_REGEX = "/with|and|[^a-z]/i";
if (array_key_exists('industries', $opts)) {
  $industries = array_map(function($industry) {
    $industry[COL_INDUSTRY_NAME] = preg_replace(INDUSTRY_REGEX, '', $industry[COL_INDUSTRY_NAME]);
    return $industry;
  }, array_map('str_getcsv', file('load/industries.csv')));
}

// Load regions if needed.
const COL_REGION_KEY = 0;
const COL_REGION_NAME = 1;
const REGION_REGEX = "/and|[^a-z]/i";
if (array_key_exists('regions', $opts)) {
  $regions_csv = [
    ['british_columbia', 'British Columbia'],
    ['cariboo', 'Cariboo'],
    ['kootenay', 'Kootenay'],
    ['northeast', 'North East'],
    ['mainland_southwest', 'Mainland South West'],
    ['thompson_okanagan', 'Thompson Okanagan'],
    ['vancouver_island_coast', 'Vancouver Island Coast'],
    ['north_coast_nechako', 'North Coast & Nechako']
  ];
  $regions = array_map(function($region) {
    $region[COL_REGION_NAME] = preg_replace(REGION_REGEX, '', $region[COL_REGION_NAME]);
    return $region;
  }, $regions_csv);
}

$keys = [];
stream_set_blocking(STDIN, TRUE);
while (($csv = fgetcsv(STDIN)) !== FALSE) {
  $out = $csv;
  foreach ($opts['column'] as $col) {
    $col--; // 0-based arrays vs. 1-based rows

    if (array_key_exists('industries', $opts)) {
      $key = industrialize($csv[$col]);
    }
    else if (array_key_exists('regions', $opts)) {
      $key = regionalize($csv[$col]);
    }
    else {
      $key = parameterize($csv[$col]);
    }
    if (array_key_exists($key, $keys)) {
      array_splice($out, $col, 0, $key . '_' . $keys[$key]);
      $keys[$key]++;
    }
    else {
      array_splice($out, $col, 0, $key);
      $keys[$key] = 1;
    }
  }
  fputcsv(STDOUT, $out);
}

function parameterize($string, $sep = '_') {
  # Convert to ASCII.
  $parameterized_string = iconv('UTF-8', 'ASCII', $string);

  # Remove numbers between brackets.
  $parameterized_string = preg_replace("/\([0-9,]+\)/i", $sep, $parameterized_string);

  # Get rid of anything thats not a valid letter, number, dash and underscore and
  # replace with a dash.
  $parameterized_string = preg_replace("/[^a-z0-9]/i", $sep, $parameterized_string);

  # Remove connected dashes so we don't end up with -- anyhwere.
  $parameterized_string = preg_replace("/$sep{2,}/", $sep, $parameterized_string);

  # Remove any trailing spaces from the string.
  $parameterized_string = preg_replace("/^$sep|$sep$/", '', $parameterized_string);

  # Downcase the string.
  return strtolower($parameterized_string);
}

function industrialize($string) {
  global $industries;
  $string_key = preg_replace(INDUSTRY_REGEX, '', $string);
  $industry = array_filter($industries, function($industry) use($string_key) {
    return strcasecmp($string_key, $industry[COL_INDUSTRY_NAME]) === 0;
  });
  return empty($industry) ? parameterize($string) : array_shift($industry)[COL_INDUSTRY_KEY];
}


function regionalize($string) {
  global $regions;
  $string_key = preg_replace(REGION_REGEX, '', $string);
  $region = array_filter($regions, function($region) use($string_key) {
    return strcasecmp($string_key, $region[COL_REGION_NAME]) === 0;
  });
  return empty($region) ? parameterize($string) : array_shift($region)[COL_REGION_KEY];
}
