<?php
/**
 * (c) 2018 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool;

use Psr\Log\AbstractLogger;

/**
 * Class SlaveLogger
 */
class SlaveLogger extends AbstractLogger
{
    /**
     * @var resource
     */
    private $log;
    /**
     * @var int
     */
    private $workerId;

    /**
     * SlaveLogger constructor.
     *
     * @param int $workerId
     */
    public function __construct(int $workerId)
    {
        $this->log = \fopen('php://stderr', 'wb+');
        $this->workerId = $workerId;
    }

    /**
     * @inheritdoc
     */
    public function log($level, $message, array $context = [])
    {
        \fwrite($this->log, \json_encode([
            'level' => $level,
            'message' => "S{workerId}: $message",
            'context' => $context + ['workerId' => $this->workerId],
        ]));
    }
}
