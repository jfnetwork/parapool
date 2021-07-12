<?php

/*
 * (c) 2021 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool\Messenger\Serializer;

use function serialize;
use function unserialize;

class Serializer implements SerializerInterface
{

    public function serialize($data): string
    {
        return serialize($data);
    }

    public function unserialize(string $data)
    {
        return unserialize($data);
    }
}
