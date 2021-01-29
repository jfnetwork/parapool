<?php

/**
 * (c) 2018 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool;

use Jfnetwork\Parapool\Messenger\DuplexStreamMessenger;
use Jfnetwork\Parapool\Messenger\Message\LogMessage;
use Psr\Log\AbstractLogger;

class SlaveLogger extends AbstractLogger
{
    public function __construct(private DuplexStreamMessenger $duplexStreamMessenger)
    {
    }

    public function log($level, $message, array $context = []): void
    {
        $this->duplexStreamMessenger->write(new LogMessage($level, $message, $context));
    }
}
