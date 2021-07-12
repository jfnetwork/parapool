<?php

/**
 * (c) 2018 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool;

use InvalidArgumentException;
use Jfnetwork\Parapool\Messenger\MessageHandler\MessageHandlerStorage;
use LogicException;

use function count;
use function usleep;

class Pool
{
    /**
     * @var Master[] $pool
     */
    private array $pool = [];
    private int $currentWorker = 0;
    private string $spawnCommand;
    private MessageHandlerStorage $messageHandlerStorage;

    public function __construct(string $spawnCommand, MessageHandlerStorage $messageHandlerStorage)
    {
        $this->messageHandlerStorage = $messageHandlerStorage;
        $this->spawnCommand = $spawnCommand;
    }

    public function setWorkerCount(int $workerCount): void
    {
        if ($workerCount < 0) {
            throw new InvalidArgumentException('Workers count may not be negative');
        }

        while ($workerCount > count($this->pool)) {
            $this->pool[] = new Master($this->spawnCommand, count($this->pool), $this->messageHandlerStorage);
        }
        while ($workerCount < count($this->pool)) {
            $this->pool[count($this->pool) - 1]->close();
            unset($this->pool[count($this->pool) - 1]);
        }
    }

    public function send(WorkCallbackInterface $callback, string $method, array $args = []): void
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
