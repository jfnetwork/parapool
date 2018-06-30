<?php
/**
 * (c) 2018 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool;

use Psr\Log\LoggerInterface;

/**
 * Class Master.
 */
class Master
{
    const PIPES = [
        ['pipe', 'r'],
        ['pipe', 'w'],
        ['pipe', 'w'],
        // ['file', 'php://stderr', 'w'],
    ];
    /**
     * @var resource process resource
     */
    private $resource;
    /**
     * @var resource stdin of worker
     */
    private $stdinPipe;
    /**
     * @var resource stdout of worker
     */
    private $stdoutPipe;
    /**
     * @var resource stdout of worker
     */
    private $stderrPipe;
    /**
     * @var callable current callback
     */
    private $callback;
    /**
     * @var int ID of Workers
     */
    private $workerId;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Master constructor.
     *
     * @param string          $spawnCommand
     * @param int             $workerId
     * @param LoggerInterface $logger
     */
    public function __construct(string $spawnCommand, int $workerId, LoggerInterface $logger)
    {
        $this->workerId = $workerId;
        $this->resource = \proc_open(
            \str_replace('{workerId}', $workerId, $spawnCommand),
            self::PIPES,
            $pipes
        );
        $this->stdinPipe = &$pipes[0];
        $this->stdoutPipe = &$pipes[1];
        $this->stderrPipe = &$pipes[2];
        \stream_set_blocking($this->stdoutPipe, 0);
        \stream_set_blocking($this->stderrPipe, 0);
        $this->logger = $logger;
    }

    /**
     * destruct.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * close all resources.
     */
    public function close()
    {
        while (!empty($this->callback)) {
            $this->checkIfDone();
            \usleep(10000);
        }
        $nop = function () {
        };
        $this->send($nop, 'stop');
        while (!empty($this->callback)) {
            $this->checkIfDone();
            \usleep(10000);
        }
        \fclose($this->stdinPipe);
        \fclose($this->stdoutPipe);
        \proc_close($this->resource);
    }

    /**
     * send work to worker.
     *
     * @param callable $callback called if work is done
     * @param string   $method   name of work
     * @param array    $args     arguments
     *
     * @return bool
     */
    public function send(callable $callback, string $method, array $args = []): bool
    {
        if ($this->isRunning()) {
            return false;
        }
        \fwrite(
            $this->stdinPipe,
            \json_encode(
                [
                    'method' => $method,
                    'args' => $args,
                ]
            )."\n"
        );
        $this->callback = $callback;

        return true;
    }

    /**
     * @return bool
     */
    public function isRunning(): bool
    {
        $this->checkIfDone();

        return !empty($this->callback);
    }

    /**
     * checks if the worker done.
     */
    private function checkIfDone()
    {
        while ($data = \fgets($this->stderrPipe)) {
            $dataParsed = \json_decode($data, true);
            if (null === $dataParsed) {
                echo "not structured error: $data\n";
                continue;
            }
            $this->logger->log($dataParsed['level'], $dataParsed['message'], $dataParsed['context']);
        }
        if ($data = \fgets($this->stdoutPipe)) {
            $dataParsed = \json_decode($data, true);
            switch (true) {
                case empty($dataParsed):
                    $this->logger->error('M{workerId}: got something strange: {data}', [
                        'workerId' => $this->workerId,
                        'data' => $data,
                    ]);
                    break;
                case $dataParsed['error'] ?? false:
                    $this->logger->error('M{workerId}: received error: {error}', [
                        'workerId' => $this->workerId,
                        'error' => $dataParsed['error'],
                    ]);
                    break;
                case !\array_key_exists('result', $dataParsed):
                    $this->logger->error('M{workerId}: no result received', [
                        'workerId' => $this->workerId,
                    ]);
                    break;
                default:
                    try {
                        ($this->callback)($dataParsed['result']);
                    } catch (\Throwable $exception) {
                        $this->logger->error('M{workerId}: Exception: {message}', [
                            'workerId' => $this->workerId,
                            'message' => $exception->getMessage(),
                        ]);
                        break;
                    }
            }
            unset($this->callback);
        }
    }
}
