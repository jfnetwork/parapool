#!/usr/bin/env php
<?php

use Psr\Log\LoggerInterface;

require __DIR__.'/../vendor/autoload.php';

$testCallable = new class implements \Jfnetwork\Parapool\SlaveCallableInterface
{
    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'test';
    }

    /**
     * @inheritdoc
     */
    public function execute(LoggerInterface $logger, array $args)
    {
        $result = $args['num'] ** 2;

        $logger->info('input: {input}, output: {output}', [
            'input' => $args['num'],
            'output' => $result,
        ]);

        \usleep(\random_int(500000, 2000000));

        return $result;
    }

};

$slave = new \Jfnetwork\Parapool\Slave((int) $argv[1], $testCallable);
$slave->loop();
