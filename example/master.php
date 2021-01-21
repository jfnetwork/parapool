#!/usr/bin/env php
<?php

require __DIR__.'/../vendor/autoload.php';

$pool = new \Jfnetwork\Parapool\Pool(__DIR__.'/slave.php {workerId}', new \Example\ExampleLogger());
$pool->setWorkerCount(12);

foreach (range(0, 100) as $i) {
    $random_bytes = random_bytes(2 ** 20);
    $hash = hash('SHA512', $random_bytes);
    $pool->send(
        (static fn($i) => static function ($result, ?Throwable $throwable = null) use ($i, $hash) {
            if (null === $throwable) {
                var_dump("$i^2 = {$result['num']}", $hash === $result['hash']);
                return;
            }
            var_dump($throwable);
        })($i),
        'test',
        ['num' => $i, 'much_data' => $random_bytes]
    );
}
$pool->send(function () {
    return false;
}, 'testFailed');
