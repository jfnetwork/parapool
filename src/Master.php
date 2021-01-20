<?php

/**
 * (c) 2018 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool;

use Psr\Log\LoggerInterface;

use Throwable;

use function array_key_exists;
use function fclose;
use function fgets;
use function fwrite;
use function json_decode;
use function json_encode;
use function proc_close;
use function proc_open;
use function str_replace;
use function stream_set_blocking;
use function usleep;

class Master
{
    public const PIPES = [
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
     * @var null|callable current callback
     */
    private $callback;

    public function __construct(
        string $spawnCommand,
        private int $workerId,
        private LoggerInterface $logger,
    ) {
        $this->resource = proc_open(
            str_replace('{workerId}', $workerId, $spawnCommand),
            self::PIPES,
            $pipes
        );
        $this->stdinPipe = &$pipes[0];
        $this->stdoutPipe = &$pipes[1];
        $this->stderrPipe = &$pipes[2];
        stream_set_blocking($this->stdoutPipe, 0);
        stream_set_blocking($this->stderrPipe, 0);
    }

    public function __destruct()
    {
        $this->close();
    }

    public function close(): void
    {
        while (!empty($this->callback)) {
            $this->checkIfDone();
            usleep(10000);
        }
        $nop = static fn() => 0;
        $this->send($nop, 'stop');
        while (null !== $this->callback) {
            $this->checkIfDone();
            usleep(10000);
        }
        fclose($this->stdinPipe);
        fclose($this->stdoutPipe);
        proc_close($this->resource);
    }

    private function checkIfDone(): void
    {
        while ($data = fgets($this->stderrPipe)) {
            $dataParsed = json_decode($data, true);
            if (null === $dataParsed) {
                echo "not structured error: $data\n";
                continue;
            }
            $this->logger->log($dataParsed['level'], $dataParsed['message'], $dataParsed['context']);
        }
        if ($data = fgets($this->stdoutPipe)) {
            $dataParsed = json_decode($data, true);
            switch (true) {
                case empty($dataParsed):
                    $this->logger->error(
                        'M{workerId}: got something strange: {data}',
                        [
                            'workerId' => $this->workerId,
                            'data' => $data,
                        ]
                    );
                    break;
                case $dataParsed['error'] ?? false:
                    $this->logger->error(
                        'M{workerId}: received error: {error}',
                        [
                            'workerId' => $this->workerId,
                            'error' => $dataParsed['error'],
                        ]
                    );
                    break;
                case !array_key_exists('result', $dataParsed):
                    $this->logger->error(
                        'M{workerId}: no result received',
                        [
                            'workerId' => $this->workerId,
                        ]
                    );
                    break;
                default:
                    try {
                        ($this->callback)($dataParsed['result']);
                    } catch (Throwable $exception) {
                        $this->logger->error(
                            'M{workerId}: Exception: {message}',
                            [
                                'workerId' => $this->workerId,
                                'message' => $exception->getMessage(),
                            ]
                        );
                        break;
                    }
            }
            $this->callback = null;
        }
    }

    public function send(callable $callback, string $method, array $args = []): bool
    {
        if ($this->isRunning()) {
            return false;
        }
        fwrite(
            $this->stdinPipe,
            json_encode(
                [
                    'method' => $method,
                    'args' => $args,
                ]
            ) . "\n"
        );
        $this->callback = $callback;

        return true;
    }

    public function isRunning(): bool
    {
        $this->checkIfDone();

        return null !== $this->callback;
    }
}
