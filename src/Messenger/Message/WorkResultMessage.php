<?php

/*
 * (c) 2021 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool\Messenger\Message;

use Throwable;

class WorkResultMessage implements MessageInterface, MessageWorkDoneInterface
{
    public function __construct(private mixed $result)
    {
    }

    public function getResult(): mixed
    {
        return $this->result;
    }

    public function getThrowable(): ?Throwable
    {
        return null;
    }
}
