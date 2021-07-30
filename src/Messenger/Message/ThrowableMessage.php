<?php

/*
 * (c) 2021 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool\Messenger\Message;

use Exception;
use Throwable;

use function get_class;
use function serialize;

final class ThrowableMessage implements MessageInterface
{
    private ?Throwable $throwable;
    private string $message;
    private string $class;
    private string $trace;

    public function __construct(Throwable $throwable)
    {
        $this->throwable = $throwable;
        $this->message = $throwable->getMessage();
        $this->class = get_class($throwable);
        $this->trace = $throwable->getTraceAsString();
    }

    public function getThrowable(): ?Throwable
    {
        return $this->throwable;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getTrace(): string
    {
        return $this->trace;
    }

    public function __sleep()
    {
        try {
            serialize($this->throwable);
        } catch (Exception $exception) {
            $this->throwable = null;
        }
        return ['message', 'class', 'trace', 'throwable'];
    }
}
