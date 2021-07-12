<?php

/*
 * (c) 2021 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool\Messenger\Serializer;

class SerializerFactory
{
    public static function createSerializer(): SerializerInterface
    {
        if (SerializerIgbinary::supported()) {
            return new SerializerIgbinary();
        }

        return new Serializer();
    }
}
