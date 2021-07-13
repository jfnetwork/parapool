<?php

/*
 * (c) 2021 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool\Messenger\Serializer;

interface SerializerInterface
{
    public function serialize($data): string;
    public function unserialize(string $data);
}
