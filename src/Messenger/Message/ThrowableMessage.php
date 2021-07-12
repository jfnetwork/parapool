<?php

/*
 * (c) 2021 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool\Messenger\Message;

use Throwable;

class ThrowableMessage implements MessageInterface, MessageWorkDoneInterface
{
    private Throwable $throwable;

    public function __construct(Throwable $throwable)
    {
        $this->throwable = $throwable;
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return null;
    }

    public function getThrowable(): Throwable
    {
        return $this->throwable;
    }
}
