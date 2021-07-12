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
    private DuplexStreamMessenger $duplexStreamMessenger;

    public function __construct(DuplexStreamMessenger $duplexStreamMessenger)
    {
        $this->duplexStreamMessenger = $duplexStreamMessenger;
    }

    public function log($level, $message, array $context = []): void
    {
        $this->duplexStreamMessenger->write(new LogMessage($level, $message, $context));
    }
}
