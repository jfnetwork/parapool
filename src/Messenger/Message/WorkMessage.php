<?php

/*
 * (c) 2021 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool\Messenger\Message;

class WorkMessage implements MessageInterface
{
    private string $method;
    private array $arguments;

    public function __construct(string $method, array $arguments)
    {
        $this->arguments = $arguments;
        $this->method = $method;
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
