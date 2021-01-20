<?php
/**
 * (c) 2018 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool;

use LogicException;

use Throwable;

use UnexpectedValueException;

use function fgets;
use function fopen;
use function fwrite;
use function get_class;
use function is_array;
use function json_encode;
use function ob_clean;

class Slave
{
    /**
     * @var SlaveCallableInterface[]
     */
    private array $callables = [];

    private SlaveLogger $logger;

    /**
     * @var resource
     */
    private $output;

    public function __construct(int $workerId, SlaveCallableInterface ...$callables)
    {
        ob_start();

        $stopCallable = new StopCallable();
        $this->callables[$stopCallable->getName()] = $stopCallable;

        foreach ($callables as $callable) {
            $name = $callable->getName();
            if (isset($this->callables[$name])) {
                throw new LogicException("Callable with name {$name} exists already");
            }

            $this->callables[$name] = $callable;
        }

        $this->logger = new SlaveLogger($workerId);
        $this->output = fopen('php://stdout', 'wb');
    }

    public function __destruct()
    {
        ob_clean();
    }

    public function loop(): void
    {
        $input = fopen('php://stdin', 'rb');
        while (true) {
            $data = fgets($input);

            $dataParsed = json_decode($data, true);
            if (empty($dataParsed) || !is_array($dataParsed)) {
                $this->error("got something strange: $data");
                continue;
            }

            if (!isset($dataParsed['method'])) {
                $this->error('no method received');
                continue;
            }

            $callable = $this->callables[$dataParsed['method']] ?? null;
            if (null === $callable) {
                $this->error("unknown method: {$dataParsed['method']}");
                continue;
            }
            try {
                $result = $callable->execute($this->logger, $dataParsed['args'] ?? []);
            } catch (Throwable $exception) {
                $class = $exception::class;
                $this->error("Exception {$class}: {$exception->getMessage()}");
                continue;
            }

            $output = json_encode(['result' => $result]);
            if (!$output) {
                throw new UnexpectedValueException('JSON returned nothing');
            }
            fwrite($this->output, "$output\n");
        }
    }

    private function error(string $error): void
    {
        fwrite($this->output, json_encode(['error' => $error]) . "\n");
    }

    // private function dump(...$args)
    // {
    //     ob_start();
    //     var_dump(...$args);
    //     $this->logger->critical(ob_get_clean());
    // }
}
