<?php

$startTime = microtime(true);

$filename = '/mnt/s/Databases/db_epart_v2/digikey_all_data.json';
$filename = '/mnt/s/num.txt';
$lineNumber = 100 * 100000;

$file = new SplFileObject($filename);
$file->seek($lineNumber - 1);

$targetLine = $file->current();

echo $targetLine;

echo PHP_EOL . PHP_EOL;

echo round(microtime(true) - $startTime, 2) . 's' . PHP_EOL;
echo round((memory_get_usage() / 1024 / 1024), 2) . 'MB' . PHP_EOL;
