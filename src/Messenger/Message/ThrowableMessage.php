<?php

/*
 * (c) 2021 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool\Messenger\Message;

use Throwable;

class ThrowableMessage implements MessageInterface
{
    public function __construct(private Throwable $throwable)
    {
    }

    public function getThrowable(): Throwable
    {
        return $this->throwable;
    }
}
