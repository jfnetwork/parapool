<?php

/*
 * (c) 2021 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool\Messenger\MessageHandler;

use Jfnetwork\Parapool\Messenger\Message\LogMessage;
use Jfnetwork\Parapool\Messenger\Message\MessageInterface;
use LogicException;
use Psr\Log\LoggerInterface;

class LogMessageHandler implements MessageHandlerInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function supports(MessageInterface $message): bool
    {
        return $message instanceof LogMessage;
    }

    public function handle(int $workerId, MessageInterface $message): void
    {
        if (!$message instanceof LogMessage) {
            throw new LogicException('something wrong, $message should be of type LogMessage');
        }
        $this->logger->log(
            $message->getLevel(),
            "M{$workerId}: {$message->getMessage()}",
            $message->getContext()
        );
    }
}
