<?php

/*
 * (c) 2021 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool\Messenger;

use InvalidArgumentException;
use RuntimeException;

use function error_get_last;
use function fclose;
use function fread;
use function fwrite;
use function is_resource;
use function stream_set_blocking;

class ResourceStream
{
    public function __construct(private $resource)
    {
        if (!is_resource($resource)) {
            throw new InvalidArgumentException('It will be only resource accepted');
        }
    }

    public function write(string $data): int
    {
        $result = fwrite(stream: $this->resource, data: $data);
        if (false === $result) {
            throw new RuntimeException(error_get_last()['message']);
        }
        return $result;
    }

    public function read(int $length): string
    {
        $result = fread(stream: $this->resource, length: $length);
        if (false === $result) {
            throw new RuntimeException(error_get_last()['message']);
        }
        return $result;
    }

    public function close(): bool
    {
        return fclose($this->resource);
    }

    public function streamSetBlocking(bool $blocking): bool
    {
        return stream_set_blocking($this->resource, $blocking ? 1 : 0);
    }
}
