<?php

/**
 * (c) 2018 jfnetwork GmbH.
 */

namespace Example;

use Psr\Log\AbstractLogger;

use function strtr;

class ExampleLogger extends AbstractLogger
{
    public function log($level, $message, array $context = []): void
    {
        echo "[$level] " . strtr(
            $message,
            (static function (array $pairs) {
                $result = [];
                foreach ($pairs as $key => $value) {
                    $result["{{$key}}"] = $value;
                }

                return $result;
            })(
                $context
            )
        ) . "\n";
    }
}
