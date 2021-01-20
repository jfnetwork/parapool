<?php

/**
 * (c) 2018 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool;

use InvalidArgumentException;
use LogicException;
use Psr\Log\LoggerInterface;

use function count;
use function usleep;

class Pool
{
    /**
     * @var Master[] $pool
     */
    private array $pool = [];

    private int $currentWorker = 0;

    public function __construct(
        private string $spawnCommand,
        private LoggerInterface $logger,
    ) {
    }

    public function setWorkerCount(int $workerCount): void
    {
        if ($workerCount < 0) {
            throw new InvalidArgumentException('Workers count may not be negative');
        }

        while ($workerCount > count($this->pool)) {
            $this->pool[] = new Master($this->spawnCommand, count($this->pool), $this->logger);
        }
        while ($workerCount < count($this->pool)) {
            $this->pool[count($this->pool) - 1]->close();
            unset($this->pool[count($this->pool) - 1]);
        }
    }

    public function send(callable $callback, string $method, array $args = []): void
    {
        $count = count($this->pool);
        if ($count < 1) {
            throw new LogicException('The pool has no workers');
        }
        while (true) {
            for ($counter = 0; $counter < $count; ++$counter) {
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
            usleep(1000);
        }
    }

    public function __destruct()
    {
        $this->waitUntilDone();
    }

    public function waitUntilDone(): void
    {
        do {
            $runningJobs = $this->countRunningJobs();
        } while ($runningJobs > 0);
    }

    public function countRunningJobs(): int
    {
        $runningJobs = 0;
        foreach ($this->pool as $parent) {
            if ($parent->isRunning()) {
                ++$runningJobs;
            }
        }

        return $runningJobs;
    }
}
