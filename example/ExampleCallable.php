<?php

/**
 * (c) 2018 jfnetwork GmbH.
 */

namespace Example;

use Exception;
use InvalidArgumentException;
use Jfnetwork\Parapool\SlaveCallableInterface;
use Psr\Log\LoggerInterface;

use function hash;

class ExampleCallable implements SlaveCallableInterface
{
    public function getName(): string
    {
        return 'test';
    }

    /**
     * @throws Exception
     */
    public function execute(LoggerInterface $logger, array $args): mixed
    {
        $logger->critical('got {number}', [
            'number' => $args['num'],
        ]);
        if (13 === $args['num']) {
            throw new InvalidArgumentException('oh no, 13!');
        }

        $result = $args['num'] ** 2;

        $logger->info('input: {input}, output: {output}', [
            'input' => $args['num'],
            'output' => $result,
        ]);

        echo "some shit\n"; // all output will be suppressed

        // $stop = microtime(true) + random_int(500000, 2000000) / 1000000;
        // $someShit = random_bytes(512);
        // while ($stop > microtime(true)) {
        //     $someShit = hash('SHA512', $someShit);
        // }

        return [
            'num' => $result,
            'hash' => hash('SHA512', $args['much_data']),
        ];
    }
}
