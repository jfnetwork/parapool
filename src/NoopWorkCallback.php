<?php

/*
 * (c) 2021 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool;

use Throwable;

class NoopWorkCallback implements WorkCallbackInterface
{
    private bool $throw;

    public function __construct(bool $throw = true)
    {
        $this->throw = $throw;
    }

    public function onSuccess($result): void
    {
    }

    public function onException(Throwable $throwable = null): void
    {
        if ($this->throw) {
            throw $throwable;
        }
    }
}
