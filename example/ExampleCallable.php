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

use function hash;
use function usleep;

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
    public function execute(array $args): array
    {
        $this->logger->critical('got {number}', [
            'number' => $args['num'],
        ]);
        if (13 === $args['num']) {
            throw new InvalidArgumentException('oh no, 13!');
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
