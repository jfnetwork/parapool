#!/usr/bin/env php8.0
<?php

use Example\ExampleLogger;
use Jfnetwork\Parapool\Messenger\MessageHandler\LogMessageHandler;
use Jfnetwork\Parapool\Messenger\MessageHandler\MessageHandlerStorage;
use Jfnetwork\Parapool\NoopWorkCallback;
use Jfnetwork\Parapool\Pool;
use Jfnetwork\Parapool\WorkCallbackInterface;

require __DIR__ . '/../vendor/autoload.php';

$msgHandlerStorage = new MessageHandlerStorage(
    new LogMessageHandler(new ExampleLogger()),
);

$pool = new Pool(__DIR__ . '/slave.php {workerId}', $msgHandlerStorage);
$pool->setWorkerCount(4);

foreach (range(0, 100) as $i) {
    $random_bytes = random_bytes(1 << 20);
    $hash = hash('SHA512', $random_bytes);
    $pool->send(
        new class ($i, $hash) implements WorkCallbackInterface
        {
            private int $num;
            private string $hash;

            public function __construct(int $num, string $hash)
            {
                $this->hash = $hash;
                $this->num = $num;
            }

            public function onSuccess($result): void
            {
                var_dump("{$this->num}^2 = {$result['num']}", $this->hash === $result['hash']);
            }

            public function onException(?Throwable $throwable, string $message, string $class, string $trace): void
            {
                var_dump($this->num, $throwable, $message, $class, $trace);
            }
        },
        'test',
        ['num' => $i, 'much_data' => $random_bytes]
    );
}
$pool->waitUntilDone();
$pool->send(
    new NoopWorkCallback(),
    'test',
    ['throw_with_callable' => true]
);
$pool->send(
    new class () implements WorkCallbackInterface
    {
        public function onSuccess($result): void
        {
            var_dump($result);
        }

        public function onException(?Throwable $throwable, string $message, string $class, string $trace): void
        {
            var_dump($throwable, $message, $class, $trace);
        }
    },
    'testFailed'
);
$pool->waitUntilDone();
echo "done\n";
