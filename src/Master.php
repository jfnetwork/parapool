<?php

/**
 * (c) 2018 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool;

use Jfnetwork\Parapool\Messenger\DuplexStreamMessenger;
use Jfnetwork\Parapool\Messenger\Message\ThrowableMessage;
use Jfnetwork\Parapool\Messenger\Message\WorkMessage;
use Jfnetwork\Parapool\Messenger\Message\WorkResultMessage;
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
    private $resource;
    private DuplexStreamMessenger $messenger;
    private ?WorkCallbackInterface $callback = null;
    private int $workerId;
    private MessageHandlerStorage $msgHandlerStorage;
    private ?SerializerInterface $serializer;

    public function __construct(
        string $spawnCommand,
        int $workerId,
        MessageHandlerStorage $msgHandlerStorage,
        ?SerializerInterface $serializer = null
    ) {
        $this->serializer = $serializer;
        $this->msgHandlerStorage = $msgHandlerStorage;
        $this->workerId = $workerId;
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

    private function checkIfDone(): void
    {
        $message = $this->messenger->readUnblocking();
        if (null === $message) {
            return;
        }
        if ($message instanceof WorkResultMessage) {
            $this->getAndUnsetCallback()->onSuccess($message->getResult());
            return;
        }
        if ($message instanceof ThrowableMessage) {
            $this->getAndUnsetCallback()->onException(
                $message->getThrowable(),
                $message->getMessage(),
                $message->getClass(),
                $message->getTrace()
            );
            return;
        }
        $this->msgHandlerStorage->handle($this->workerId, $message);
    }

    private function getAndUnsetCallback(): WorkCallbackInterface
    {
        if (empty($this->callback)) {
            throw new RuntimeException("M{$this->workerId}: no callback O.o");
        }
        $callback = $this->callback;
        $this->callback = null;
        return $callback;
    }
}
