<?php

/*
 * (c) 2021 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool\Messenger\Message;

class LogMessage implements MessageInterface
{
    private string $level;
    private string $message;
    private array $context;

    public function __construct(string $level, string $message, array $context = [])
    {
        $this->context = $context;
        $this->message = $message;
        $this->level = $level;
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
