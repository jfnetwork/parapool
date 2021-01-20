<?php

/**
 * (c) 2018 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool;

use Psr\Log\AbstractLogger;

use function fwrite;
use function json_encode;

class SlaveLogger extends AbstractLogger
{
    /**
     * @var resource
     */
    private $log;

    public function __construct(private int $workerId)
    {
        $this->log = \fopen('php://stderr', 'wb+');
    }

    public function log($level, $message, array $context = [])
    {
        fwrite($this->log, json_encode([
            'level' => $level,
            'message' => "S{workerId}: $message",
            'context' => $context + ['workerId' => $this->workerId],
        ]) . "\n");
    }
}
