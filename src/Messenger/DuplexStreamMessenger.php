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

use const STDIN;
use const STDOUT;

class DuplexStreamMessenger
{
    private const MAX_LENGTH = 1 << 10;
    private const LENGTH_PACK = 'V';
    private const MORE_PACK = 'c';
    private const UNPACK = self::LENGTH_PACK . 'length/' . self::MORE_PACK . 'more';

    private int $headerLength;
    private ResourceStream $input;
    private ResourceStream $output;
    private ?SerializerInterface $serialize;

    public function __construct(
        ResourceStream $input,
        ResourceStream $output,
        ?SerializerInterface $serialize = null
    ) {
        $this->serialize = $serialize;
        $this->output = $output;
        $this->input = $input;
        if (null === $this->serialize) {
            $this->serialize = SerializerFactory::createSerializer();
        }
        $this->headerLength = strlen(pack(self::LENGTH_PACK, 0)) + strlen(pack(self::MORE_PACK, 0));
    }

    public static function createStandardIO(?SerializerInterface $serializer = null): self
    {
        return new self(
            new ResourceStream(STDIN),
            new ResourceStream(STDOUT),
            $serializer
        );
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
            $this->input->streamSetBlocking(true);
            ['length' => $length, 'more' => $more] = unpack(self::UNPACK, $header);
            $packet .= $this->input->read($length);
        } while ($more);
        return $this->serialize->unserialize($packet);
    }
}
