<?php

define('START_TIME', microtime(true));

function createFiberCurlRequest($url)
{
    $fiber = new Fiber(function () use ($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        curl_close($ch);
        echo round((microtime(true) - START_TIME), 2) . 'S' . PHP_EOL;

        Fiber::suspend();

        echo PHP_EOL . PHP_EOL . '[FIBER] ' . $url;
        var_dump($response);
        echo PHP_EOL;
    });

    $fiber->start();

    return $fiber->resume();
}



$urls = [
    'https://api.inreon.net/part_detail?id=17282315',
    'https://api.inreon.net/part_detail?id=17282329',
    'https://api.inreon.net/part_detail?id=17282313',
];
$fibers = [];


$fiber = createFiberCurlRequest($urls[0]);
var_dump($fiber);

echo round((microtime(true) - START_TIME), 2) . 'S' . PHP_EOL;

foreach ($urls as $url) {
    // $fibers[] = createFiberCurlRequest($url);
}
