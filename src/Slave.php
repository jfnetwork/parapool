<?php
/**
 * (c) 2018 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool;

use function is_array;
use Psr\Log\LoggerInterface;

/**
 * Class Slave
 */
class Slave
{
    /**
     * @var SlaveCallableInterface[]
     */
    private $callables = [];
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Slave constructor.
     *
     * @param int                    $workerId
     * @param SlaveCallableInterface ...$callables
     */
    public function __construct(int $workerId, SlaveCallableInterface ...$callables)
    {
        $stopCallable = new StopCallable();
        $this->callables[$stopCallable->getName()] = $stopCallable;

        foreach ($callables as $callable) {
            $name = $callable->getName();
            if (isset($this->callables[$name])) {
                throw new \LogicException(\sprintf('Callable with name %s exists already', $name));
            }

            $this->callables[$name] = $callable;
        }

        $this->logger = new SlaveLogger($workerId);
    }

    /**
     * @return void
     */
    public function loop()
    {
        $input = \fopen('php://stdin', 'rb');
        while (true) {
            $data = \fgets($input);

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
            } catch (\Throwable $exception) {
                $class = \get_class($exception);
                $this->error("Exception {$class}: {$exception->getMessage()}");
                continue;
            }

            $output = json_encode(['result' => $result]);
            if (!$output) {
                throw new \UnexpectedValueException('JSON returned nothing');
            }
            echo "$output\n";
        }
    }

    /**
     * @param string $error
     */
    private function error(string $error)
    {
        \fwrite($this->output, \json_encode(['error' => $error])."\n");
    }

    // private function dump(...$args)
    // {
    //     ob_start();
    //     var_dump(...$args);
    //     $this->logger->critical(ob_get_clean());
    // }
}
