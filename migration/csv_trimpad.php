<?php

/**
* Pad CSV columns.
*
* Usage: php csv_trimpad.php --column col:dir{RLB}:len:str[:trim]  ... < /path/to/input.csv > /path/to/output.csv
*/

$opts = getopt('', [
  'column:',
]);
$usage = 'Usage: php csv_trimpad.php --column col:dir{RLB}:len:str[:trim]  ... < /path/to/input.csv > /path/to/output.csv';
if (!array_key_exists('column', $opts)) {
  die("No column specification found\n" . $usage . PHP_EOL);
}
$opts['column'] = (array)$opts['column'];
$columns = [];
foreach ($opts['column'] as $i => $column) {
  $c = explode(':', $column);
  if (count($c) != 4 && count($c) != 5) {
    die("Column specification invalid \"{$column}\"\n" . $usage . PHP_EOL);
  }
  $columns[] = [
    'col' => intval($c[0])-1,
    'dir' => strtoupper($c[1]) == 'B' ? STR_PAD_BOTH : (strtoupper($c[1]) == 'R' ? STR_PAD_RIGHT : STR_PAD_LEFT),
    'len' => intval($c[2]),
    'str' => $c[3],
    'trim' => empty($c[4]) ? false : [
      'fn' => strtoupper($c[1]) == 'B' ? 'trim' : (strtoupper($c[1]) == 'R' ? 'rtrim' : 'ltrim'),
      'chars' => $c[4]
    ]
  ];
}

stream_set_blocking(STDIN, TRUE);
while (($csv = fgetcsv(STDIN)) !== FALSE) {
  foreach ($columns as $column) {
    if (array_key_exists($column['col'], $csv)) {
      if (!empty($column['trim'])) {
        $csv[$column['col']] = $column['trim']['fn']($csv[$column['col']], $column['trim']['chars']);
      }
      $csv[$column['col']] = str_pad($csv[$column['col']], $column['len'], $column['str'], $column['dir']);
    }
  }
  fputcsv(STDOUT, $csv);
}
