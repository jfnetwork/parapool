<?php

/*
 * (c) 2021 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool\Messenger\Message;

use Throwable;

interface MessageWorkDoneInterface
{
    public function getResult();

    public function getThrowable(): ?Throwable;
}
