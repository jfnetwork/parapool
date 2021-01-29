<?php
/*
 * (c) 2021 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool\Messenger\MessageHandler;


use Jfnetwork\Parapool\Messenger\Message\LogMessage;
use Jfnetwork\Parapool\Messenger\Message\MessageInterface;
use Psr\Log\LoggerInterface;

class LogMessageHandler implements MessageHandlerInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function supports(MessageInterface $message): bool
    {
        return $message instanceof LogMessage;
    }

    public function handle(int $workerId, LogMessage | MessageInterface $message): void
    {
        $this->logger->log(
            $message->getLevel(),
            "M{$workerId}: {$message->getMessage()}",
            $message->getContext()
        );
    }
}
