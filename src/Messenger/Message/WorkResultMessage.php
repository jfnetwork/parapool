<?php

/*
 * (c) 2021 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool\Messenger\Message;

class WorkResultMessage implements MessageInterface
{
    public function __construct(private mixed $result)
    {
    }

    public function getResult(): mixed
    {
        return $this->result;
    }
}
