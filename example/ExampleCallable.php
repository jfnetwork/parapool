<?php

/**
 * (c) 2018 jfnetwork GmbH.
 */

namespace Example;

use Exception;
use InvalidArgumentException;
use JetBrains\PhpStorm\ArrayShape;
use Jfnetwork\Parapool\SlaveCallableInterface;
use Jfnetwork\Parapool\SlaveLogger;

use function array_sum;
use function ini_set;
use function memory_get_peak_usage;

class TestException extends Exception
{
    public $callable;

    public function __construct($message = '')
    {
        $this->callable = static fn() => 1;
        parent::__construct($message);
    }
}

class ExampleCallable implements SlaveCallableInterface
{
    private SlaveLogger $logger;

    public function __construct(SlaveLogger $logger)
    {
        $this->logger = $logger;
    }

    public function getName(): string
    {
        return 'test';
    }

    /**
     * @throws Exception
     */
    #[ArrayShape(['num' => "int|mixed", 'hash' => "string"])]
    public function execute(array $args)
    {
        if (!empty($args['throw_with_callable'])) {
            throw new TestException('callable!');
        }
        $this->logger->critical('got {number}', [
            'number' => $args['num'],
        ]);
        if (13 === $args['num']) {
            throw new InvalidArgumentException('oh no, 13!');
        }
        if (69 === $args['num']) {
            ini_set('memory_limit', memory_get_peak_usage() + 2000000);
            $arr = range(0, 1000000);
            return  ['num' => array_sum($arr), 'hash' => 'foo'];
        }

        $result = $args['num'] ** 2;

        $this->logger->info('input: {input}, output: {output}', [
            'input' => $args['num'],
            'output' => $result,
        ]);

        echo "some shit\n"; // all output will be suppressed
        usleep(random_int(500000, 2000000));

        return [
            'num' => $result,
            'hash' => hash('SHA512', $args['much_data']),
        ];
    }
}
