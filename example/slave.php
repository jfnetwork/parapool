#!/usr/bin/env php
<?php

require __DIR__.'/../vendor/autoload.php';

$exampleCallable = new \Example\ExampleCallable();

$slave = new \Jfnetwork\Parapool\Slave((int) $argv[1], $exampleCallable);
$slave->loop();
