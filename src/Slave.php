<?php

/**
 * (c) 2018 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool;

use http\Exception\UnexpectedValueException;
use Jfnetwork\Parapool\Messenger\DuplexStreamMessenger;
use Jfnetwork\Parapool\Messenger\Message\ThrowableMessage;
use Jfnetwork\Parapool\Messenger\Message\WorkMessage;
use Jfnetwork\Parapool\Messenger\Message\WorkResultMessage;
use Jfnetwork\Parapool\Messenger\ResourceStream;
use LogicException;
use RuntimeException;
use Throwable;

use function ob_clean;

use const STDIN;
use const STDOUT;

class Slave
{
    /**
     * @var SlaveCallableInterface[]
     */
    private array $callables = [];
    private SlaveLogger $logger;
    private DuplexStreamMessenger $messenger;

    public function __construct(int $workerId, SlaveCallableInterface ...$callables)
    {
        ob_start();

        $this->messenger = new DuplexStreamMessenger(
            new ResourceStream(STDIN),
            new ResourceStream(STDOUT),
        );

        $stopCallable = new StopCallable();
        $this->callables[$stopCallable->getName()] = $stopCallable;

        foreach ($callables as $callable) {
            $name = $callable->getName();
            if (isset($this->callables[$name])) {
                throw new LogicException("Callable with name {$name} exists already");
            }

            $this->callables[$name] = $callable;
        }

        $this->logger = new SlaveLogger($workerId, $this->messenger);
    }

    public function __destruct()
    {
        ob_clean();
    }

    public function loop(): void
    {
        while (true) {
            $message = $this->messenger->readBlocking();
            if (null === $message) {
                continue;
            }

            if ($message instanceof WorkMessage) {
                try {
                    $slaveCallable = $this->callables[$message->getMethod()] ?? null;
                    if (null === $slaveCallable) {
                        throw new RuntimeException("Method {$message->getMethod()} is not defined");
                    }
                    $result = $slaveCallable->execute($this->logger, $message->getArguments());
                    $this->messenger->write(new WorkResultMessage($result));
                } catch (Throwable $throwable) {
                    $this->messenger->write(new ThrowableMessage($throwable));
                }
                continue;
            }
            throw new UnexpectedValueException('fuck');
        }
    }
}
