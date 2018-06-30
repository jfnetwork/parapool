<?php
/**
 * (c) 2018 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool;

use Psr\Log\LoggerInterface;

/**
 * Class Pool
 */
class Pool
{
    /**
     * @var Master[] $pool
     */
    private $pool = [];
    /**
     * @var string
     */
    private $spawnCommand;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var int
     */
    private $currentWorker = 0;

    /**
     * Pool constructor.
     *
     * @param string          $spawnCommand
     * @param LoggerInterface $logger
     */
    public function __construct(string $spawnCommand, LoggerInterface $logger)
    {
        $this->spawnCommand = $spawnCommand;
        $this->logger = $logger;
    }

    /**
     * @param int $workerCount
     */
    public function setWorkerCount(int $workerCount)
    {
        if ($workerCount < 0) {
            throw new \InvalidArgumentException('Workers count may not be negative');
        }

        while ($workerCount > \count($this->pool)) {
            $this->pool[] = new Master($this->spawnCommand, \count($this->pool), $this->logger);
        }
        while ($workerCount < \count($this->pool)) {
            $this->pool[\count($this->pool) - 1]->close();
            unset($this->pool[\count($this->pool) - 1]);
        }
    }
    /**
     * @param callable $callback
     * @param string   $method
     * @param array    $args
     */
    public function send(callable $callback, string $method, array $args = [])
    {
        $count = \count($this->pool);
        if ($count < 1) {
            throw new \LogicException('The pool has no workers');
        }
        while (true) {
            for ($counter = 0; $counter < $count; $counter++) {
                $checkWorker = $counter + $this->currentWorker;
                if ($checkWorker >= $count) {
                    $checkWorker -= $count;
                }
                if ($this->pool[$checkWorker]->send($callback, $method, $args)) {
                    $this->currentWorker++;
                    if ($this->currentWorker >= $count) {
                        $this->currentWorker -= $count;
                    }
                    break 2;
                }
            }
            \usleep(1000);
        }
    }

    /**
     * @return void
     */
    public function waitUntilDone()
    {
        do {
            $runningJobs = $this->countRunningJobs();
        } while ($runningJobs > 0);
    }

    /**
     * @return int
     */
    public function countRunningJobs() : int
    {
        $runningJobs = 0;
        foreach ($this->pool as $parent) {
            if ($parent->isRunning()) {
                $runningJobs++;
            }
        }

        return $runningJobs;
    }

    /**
     * Pool destructor.
     */
    public function __destruct()
    {
        $this->waitUntilDone();
    }
}
