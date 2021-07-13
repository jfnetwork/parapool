<?php

/**
 * (c) 2018 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool;

interface SlaveCallableInterface
{
    public function getName(): string;
    public function execute(array $args);
}
