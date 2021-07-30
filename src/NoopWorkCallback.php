<?php

/*
 * (c) 2021 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool;

use Exception;
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

    public function onException(?Throwable $throwable, string $message, string $class, string $trace): void
    {
        if ($this->throw) {
            if (null === $throwable) {
                throw new Exception(
                    sprintf(
                        "got Throwable of class '%s' with message '%s', but it could not be serialized\nTrace:\n%s",
                        $class,
                        $message,
                        $trace
                    )
                );
            }
            throw $throwable;
        }
    }
}
