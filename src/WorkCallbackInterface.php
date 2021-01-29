<?php

/*
 * (c) 2021 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool;

use Throwable;

interface WorkCallbackInterface
{
    public function callback($result, ?Throwable $throwable = null): void;
}
