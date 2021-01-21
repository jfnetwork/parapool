<?php

/*
 * (c) 2021 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool\Messenger\Message;

class WorkMessage implements MessageInterface
{
    public function __construct(private string $method, private array $arguments)
    {
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }
}
