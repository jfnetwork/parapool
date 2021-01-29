#!/usr/bin/env php8.0
<?php

require __DIR__ . '/../vendor/autoload.php';

use Example\ExampleCallable;
use Jfnetwork\Parapool\Messenger\DuplexStreamMessenger;
use Jfnetwork\Parapool\Slave;
use Jfnetwork\Parapool\SlaveLogger;

$messenger = DuplexStreamMessenger::createStandardIO();

$slaveLogger = new SlaveLogger($messenger);

$exampleCallable = new ExampleCallable($slaveLogger);

$slave = new Slave($messenger, $exampleCallable);
$slave->loop();
