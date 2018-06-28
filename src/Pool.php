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
    protected $pool = [];
    /**
     * @var string
     */
    private $spawnCommand;
    /**
     * @var LoggerInterface
     */
    private $logger;

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
        static $currentWorker = 0;
        while (true) {
            for ($counter = 0; $counter < $count; $counter++) {
                $checkWorker = $counter + $currentWorker;
                if ($checkWorker >= $count) {
                    $checkWorker -= $count;
                }
                if ($this->pool[$checkWorker]->send($callback, $method, $args)) {
                    $currentWorker++;
                    if ($currentWorker >= $count) {
                        $currentWorker -= $count;
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
        while (true) {
            foreach ($this->pool as $parent) {
                if ($parent->isRunning()) {
                    \usleep(1000);
                    continue 2;
                }
            }
            break;
        }
    }

    /**
     * @return int
     */
    public function checkPoolForDoneJobs() : int
    {
        $doneJobs = 0;
        foreach ($this->pool as $parent) {
            if ($parent->isRunning()) {
                $doneJobs++;
            }
        }

        return $doneJobs;
    }
}
