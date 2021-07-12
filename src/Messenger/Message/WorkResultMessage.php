<?php

/*
 * (c) 2021 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool\Messenger\Message;

use Throwable;

class WorkResultMessage implements MessageInterface, MessageWorkDoneInterface
{
    private $result;

    public function __construct($result)
    {
        $this->result = $result;
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    public function getThrowable(): ?Throwable
    {
        return null;
    }
}
