<?php
/**
 * (c) 2018 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool;

use Psr\Log\LoggerInterface;

/**
 * Class StopCallable
 */
class StopCallable implements SlaveCallableInterface
{
    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'stop';
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function execute(LoggerInterface $logger, array $args)
    {
        \file_put_contents('php://stdout', json_encode(
            [
                'result' => 'ok',
            ]
        )."\n");
        die;
    }
}
