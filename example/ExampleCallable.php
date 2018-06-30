<?php
/**
 * (c) 2018 jfnetwork GmbH.
 */

namespace Example;

use Jfnetwork\Parapool\SlaveCallableInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ExampleCallable
 */
class ExampleCallable implements SlaveCallableInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'test';
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function execute(LoggerInterface $logger, array $args)
    {
        if (13 === $args['num']) {
            throw new \InvalidArgumentException('oh no, 13!');
        }

        $result = $args['num'] ** 2;

        $logger->info('input: {input}, output: {output}', [
            'input' => $args['num'],
            'output' => $result,
        ]);

        echo "some shit\n"; // all output will be suppressed

        \usleep(\random_int(500000, 2000000));

        return $result;
    }
}
