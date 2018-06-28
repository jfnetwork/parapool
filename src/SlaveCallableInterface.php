<?php
/**
 * (c) 2018 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool;

use Psr\Log\LoggerInterface;

/**
 * Interface SlaveCallableInterface
 */
interface SlaveCallableInterface
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param LoggerInterface $logger
     * @param array           $args
     *
     * @return mixed
     */
    public function execute(LoggerInterface $logger, array $args);
}
