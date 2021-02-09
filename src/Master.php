<?php

/**
 * (c) 2018 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool;

use Jfnetwork\Parapool\Messenger\DuplexStreamMessenger;
use Jfnetwork\Parapool\Messenger\Message\MessageWorkDoneInterface;
use Jfnetwork\Parapool\Messenger\Message\WorkMessage;
use Jfnetwork\Parapool\Messenger\MessageHandler\MessageHandlerStorage;
use Jfnetwork\Parapool\Messenger\ResourceStream;
use Jfnetwork\Parapool\Messenger\Serializer\SerializerInterface;

use RuntimeException;

use function proc_get_status;
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
     * @var resource|null process resource
     */
    private $resource = null;
    private DuplexStreamMessenger $messenger;
    private ?WorkCallbackInterface $callback = null;

    public function __construct(
        string $spawnCommand,
        private int $workerId,
        private MessageHandlerStorage $messageHandlerStorage,
        private ?SerializerInterface $serializer = null,
    ) {
        $this->respawn($workerId, $spawnCommand);
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
        if ($message instanceof MessageWorkDoneInterface) {
            $callback = $this->callback ?? throw new RuntimeException('no callback O.o');
            $this->callback = null;
            if (null !== $message->getThrowable()) {
                $callback->onException($message->getThrowable());
                return;
            }
            $callback->onSuccess($message->getResult());
            return;
        }
        $this->messageHandlerStorage->handle($this->workerId, $message);
    }

    public function send(WorkCallbackInterface $callback, string $method, array $args = []): bool
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

    public function respawn(int $workerId, string $spawnCommand)
    {
        if (null !== $this->resource && proc_get_status($this->resource)['running']) {
            proc_terminate($this->resource);
        }
        $this->resource = proc_open(
            str_replace('{workerId}', $workerId, $spawnCommand),
            self::PIPES,
            $pipes
        );
        $this->messenger = new DuplexStreamMessenger(
            new ResourceStream($pipes[1]),
            new ResourceStream($pipes[0]),
            $this->serializer,
        );

        return $pipes;
    }
}
