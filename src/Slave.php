<?php

/**
 * (c) 2018 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool;

use Jfnetwork\Parapool\Exception\MethodNotFoundException;
use Jfnetwork\Parapool\Messenger\DuplexStreamMessenger;
use Jfnetwork\Parapool\Messenger\Message\ThrowableMessage;
use Jfnetwork\Parapool\Messenger\Message\WorkMessage;
use Jfnetwork\Parapool\Messenger\Message\WorkResultMessage;
use LogicException;
use Throwable;
use UnexpectedValueException;

use function ob_clean;

class Slave
{
    /**
     * @var array<SlaveCallableInterface>
     */
    private array $callables = [];

    public function __construct(private DuplexStreamMessenger $messenger, SlaveCallableInterface ...$callables)
    {
        ob_start();

        foreach ($callables as $callable) {
            $name = $callable->getName();
            if (isset($this->callables[$name])) {
                throw new LogicException("Callable with name {$name} exists already");
            }

            $this->callables[$name] = $callable;
        }
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
                        throw new MethodNotFoundException("Method {$message->getMethod()} is not defined");
                    }
                    $result = $slaveCallable->execute($message->getArguments());
                    $this->messenger->write(new WorkResultMessage($result));
                } catch (Throwable $throwable) {
                    $this->messenger->write(new ThrowableMessage($throwable));
                }
                continue;
            }
            throw new UnexpectedValueException('unknown message type');
        }
    }
}
