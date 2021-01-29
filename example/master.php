#!/usr/bin/env php8.0
<?php

use Example\ExampleLogger;
use Jfnetwork\Parapool\Messenger\MessageHandler\LogMessageHandler;
use Jfnetwork\Parapool\Messenger\MessageHandler\MessageHandlerStorage;
use Jfnetwork\Parapool\Pool;
use Jfnetwork\Parapool\WorkCallbackInterface;

require __DIR__ . '/../vendor/autoload.php';

$messageHandlerStorage = new MessageHandlerStorage(
    new LogMessageHandler(new ExampleLogger()),
);

$pool = new Pool(__DIR__ . '/slave.php {workerId}', $messageHandlerStorage);
$pool->setWorkerCount(12);

foreach (range(0, 100) as $i) {
    $random_bytes = random_bytes(2 ** 20);
    $hash = hash('SHA512', $random_bytes);
    $pool->send(
        new class ($i, $hash) implements WorkCallbackInterface
        {
            public function __construct(private int $num, private string $hash)
            {
            }

            public function callback($result, ?Throwable $throwable = null): void
            {
                if (null === $throwable) {
                    var_dump("{$this->num}^2 = {$result['num']}", $this->hash === $result['hash']);
                    return;
                }
                var_dump($throwable);
            }
        },
        'test',
        ['num' => $i, 'much_data' => $random_bytes]
    );
}
$pool->send(
    new class () implements WorkCallbackInterface
    {
        public function callback($result, ?Throwable $throwable = null): void
        {
        }
    },
    'testFailed'
);
