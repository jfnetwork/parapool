<?php

/*
 * (c) 2021 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool\Messenger\MessageHandler;

use Jfnetwork\Parapool\Messenger\Message\MessageInterface;

interface MessageHandlerInterface
{
    public function supports(MessageInterface $message): bool;

    public function handle(int $workerId, MessageInterface $message): void;
}
