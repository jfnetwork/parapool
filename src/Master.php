<?php

/**
 * (c) 2018 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool;

use Jfnetwork\Parapool\Messenger\DuplexStreamMessenger;
use Jfnetwork\Parapool\Messenger\Message\LogMessage;
use Jfnetwork\Parapool\Messenger\Message\ThrowableMessage;
use Jfnetwork\Parapool\Messenger\Message\WorkMessage;
use Jfnetwork\Parapool\Messenger\Message\WorkResultMessage;
use Jfnetwork\Parapool\Messenger\ResourceStream;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

use function proc_open;
use function proc_terminate;
use function str_replace;
use function usleep;

class Master
{
    private const PIPES = [
        ['pipe', 'r'],
        ['pipe', 'w'],
    ];

    /**
     * @var resource process resource
     */
    private $resource;

    private DuplexStreamMessenger $messenger;

    /**
     * @var null|callable current callback
     */
    private $callback;

    public function __construct(
        string $spawnCommand,
        private int $workerId,
        private LoggerInterface $logger,
    ) {
        $this->resource = proc_open(
            str_replace('{workerId}', $workerId, $spawnCommand),
            self::PIPES,
            $pipes
        );
        $this->messenger = new DuplexStreamMessenger(
            new ResourceStream($pipes[1]),
            new ResourceStream($pipes[0]),
        );
    }

    public function __destruct()
    {
        $this->close();
    }

    public function close(): void
    {
        while (!empty($this->callback)) {
            $this->checkIfDone();
            usleep(10000);
        }
        proc_terminate($this->resource);
    }

    private function checkIfDone(): void
    {
        $message = $this->messenger->readUnblocking();
        if (null === $message) {
            return;
        }
        switch (true) {
            case $message instanceof LogMessage:
                $this->logger->log(
                    $message->getLevel(),
                    "M{$this->workerId}: {$message->getMessage()}",
                    $message->getContext()
                );
                return;
            case $message instanceof ThrowableMessage:
                $this->logger->error(
                    'M{workerId}: received error: {error}',
                    [
                        'workerId' => $this->workerId,
                        'error' => $message->getThrowable()->getMessage(),
                    ]
                );
                ($this->callback)(null, $message->getThrowable());
                break;
            case $message instanceof WorkResultMessage:
                try {
                    ($this->callback)($message->getResult(), null);
                } catch (Throwable $exception) {
                    $this->logger->error(
                        'M{workerId}: Exception: {message}',
                        [
                            'workerId' => $this->workerId,
                            'message' => $exception->getMessage(),
                        ]
                    );
                }
                break;
            default:
                throw new RuntimeException('unsupported message');
        }
        $this->callback = null;
    }

    public function send(callable $callback, string $method, array $args = []): bool
    {
        if ($this->isRunning()) {
            return false;
        }
        $this->messenger->write(new WorkMessage($method, $args));
        $this->callback = $callback;

        return true;
    }

    public function isRunning(): bool
    {
        $this->checkIfDone();

        return null !== $this->callback;
    }
}
