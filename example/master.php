#!/usr/bin/env php
<?php

require __DIR__.'/../vendor/autoload.php';

$pool = new \Jfnetwork\Parapool\Pool(__DIR__.'/slave.php {workerId}', new \Example\ExampleLogger());
$pool->setWorkerCount(10);

for ($i = 0; $i < 101; ++$i) {
    $pool->send((function ($i) {
        return function ($result) use ($i) {
            echo "$i^2 = $result\n";
        };
    })($i), 'test', ['num' => $i]);
}
$pool->send(function () {
    return false;
}, 'testFailed');
