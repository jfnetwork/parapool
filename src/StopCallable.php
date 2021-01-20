<?php

/**
 * (c) 2018 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool;

use JetBrains\PhpStorm\NoReturn;
use Psr\Log\LoggerInterface;

use function fwrite;

use const STDOUT;

/**
 * @SuppressWarnings(PHPMD)
 */
class StopCallable implements SlaveCallableInterface
{
    public function getName(): string
    {
        return 'stop';
    }

    #[NoReturn]
    public function execute(LoggerInterface $logger, array $args): mixed
    {
        fwrite(STDOUT, "{\"result\":\"ok\"}\n");
        die;
    }
}
