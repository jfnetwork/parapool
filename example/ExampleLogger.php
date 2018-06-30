<?php
/**
 * (c) 2018 jfnetwork GmbH.
 */

namespace Example;

/**
 * Class ExampleLogger
 */
class ExampleLogger extends \Psr\Log\AbstractLogger
{
    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = [])
    {
        echo "[$level] ".\strtr($message, (function (array $pairs) {
            $result = [];

            foreach ($pairs as $key => $value) {
                $result["{{$key}}"] = $value;
            }

            return $result;
        })($context))."\n";
    }
}
