<?php

/*
 * (c) 2021 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool\Messenger\MessageHandler;

use Jfnetwork\Parapool\Messenger\Message\MessageInterface;
use RuntimeException;

class MessageHandlerStorage
{
    /**
     * @var array<MessageHandlerInterface>
     */
    private array $messageHandlers;

    public function __construct(MessageHandlerInterface ...$messageHandlers)
    {
        $this->messageHandlers = $messageHandlers;
    }

    public function handle(int $workerId, MessageInterface $message): void
    {
        foreach ($this->messageHandlers as $messageHandler) {
            if ($messageHandler->supports($message)) {
                $messageHandler->handle($workerId, $message);
                return;
            }
        }
        throw new RuntimeException('unsupported message');
    }
}
