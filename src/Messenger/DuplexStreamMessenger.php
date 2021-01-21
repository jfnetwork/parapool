<?php

/*
 * (c) 2021 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool\Messenger;

use Jfnetwork\Parapool\Messenger\Message\MessageInterface;
use Jfnetwork\Parapool\Messenger\Serializer\SerializerFactory;
use Jfnetwork\Parapool\Messenger\Serializer\SerializerInterface;

use function array_shift;
use function count;
use function pack;
use function str_split;
use function strlen;
use function unpack;

class DuplexStreamMessenger
{
    private const MAX_LENGTH = 1 << 10;
    private const LENGTH_PACK = 'V';

    private int $headerLength;

    public function __construct(
        private ResourceStream $input,
        private ResourceStream $output,
        private ?SerializerInterface $serialize = null,
    ) {
        if (null === $this->serialize) {
            $this->serialize = SerializerFactory::createSerializer();
        }
        $this->headerLength = strlen(pack(self::LENGTH_PACK, 0)) + 1;
    }

    public function write(MessageInterface $data): void
    {
        $packet = $this->serialize->serialize($data);
        $parts = str_split($packet, self::MAX_LENGTH);
        while ($parts) {
            $part = array_shift($parts);
            $this->output->write(pack(self::LENGTH_PACK . 'ca*', strlen($part), count($parts) > 0, $part));
        }
    }

    public function readBlocking(): ?MessageInterface
    {
        $this->input->streamSetBlocking(true);
        return $this->read();
    }

    public function readUnblocking(): ?MessageInterface
    {
        $this->input->streamSetBlocking(false);
        return $this->read();
    }

    private function read(): ?MessageInterface
    {
        $packet = '';
        do {
            $header = $this->input->read($this->headerLength);
            if (!$header) {
                return null;
            }
            ['length' => $length, 'more' => $more] = unpack(self::LENGTH_PACK . 'length/cmore', $header);
            $packet .= $this->input->read($length);
        } while ($more);
        return $this->serialize->unserialize($packet);
    }
}
