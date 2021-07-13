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
    private DuplexStreamMessenger $streamMessenger;

    public function __construct(DuplexStreamMessenger $streamMessenger)
    {
        $this->streamMessenger = $streamMessenger;
    }

    public function log($level, $message, array $context = []): void
    {
        $this->streamMessenger->write(new LogMessage($level, $message, $context));
    }
}
