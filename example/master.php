#!/usr/bin/env php
<?php

use Psr\Log\AbstractLogger;

require __DIR__.'/../vendor/autoload.php';

$pool = new \Jfnetwork\Parapool\Pool(__DIR__.'/slave.php {workerId}', new class extends AbstractLogger
{
    public function log($level, $message, array $context = [])
    {
        echo "[$level] ".\strtr($message, (function (array $pairs) {
            $result = [];

            foreach ($pairs as $key => $value) {
                $result["{{$key}}"] = $value;
            }

            return $result;
        })($context))."\n";
    }
});
$pool->setWorkerCount(10);

for ($i = 0; $i < 21; $i++) {
    $pool->send((function ($i) {
        return function ($result) use ($i) {
            echo $i, '^2 = ', $result, PHP_EOL;
        };
    })($i), 'test', ['num' => $i]);
}
