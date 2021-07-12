<?php

/*
 * (c) 2021 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool\Messenger\Serializer;

use function function_exists;
use function igbinary_serialize;
use function igbinary_unserialize;

class SerializerIgbinary implements SerializerInterface
{
    public function serialize($data): string
    {
        return igbinary_serialize($data);
    }

    public function unserialize(string $data)
    {
        return igbinary_unserialize($data);
    }

    public static function supported(): bool
    {
        return function_exists('\igbinary_serialize') && function_exists('\igbinary_unserialize');
    }
}
