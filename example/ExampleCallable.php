<?php

/**
 * (c) 2018 jfnetwork GmbH.
 */

namespace Example;

use Exception;
use InvalidArgumentException;
use Jfnetwork\Parapool\SlaveCallableInterface;
use Psr\Log\LoggerInterface;

use function random_int;
use function usleep;

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
        if (13 === $args['num']) {
            throw new InvalidArgumentException('oh no, 13!');
        }

        $result = $args['num'] ** 2;

        $logger->info('input: {input}, output: {output}', [
            'input' => $args['num'],
            'output' => $result,
        ]);

        echo "some shit\n"; // all output will be suppressed

        usleep(random_int(500000, 2000000));

        return $result;
    }
}
