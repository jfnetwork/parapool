<?php

/*
 * (c) 2021 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool;

use Throwable;

interface WorkCallbackInterface
{
    public function onSuccess(mixed $result): void;
    public function onException(Throwable $throwable = null): void;
}
