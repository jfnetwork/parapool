<?php

/*
 * (c) 2021 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool;

use Throwable;

interface WorkCallbackInterface
{
    public function onSuccess($result): void;
    public function onException(?Throwable $throwable, string $message, string $class, string $trace): void;
}
