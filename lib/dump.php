<?php
/*
 * (c) 2021 jfnetwork GmbH.
 */

namespace Jfnetwork\Parapool;

use JetBrains\PhpStorm\NoReturn;

use function fwrite;
use function ob_get_clean;
use function ob_start;
use function var_dump;

use const STDERR;

function dump(...$args)
{
    ob_start();
    var_dump(...$args);
    fwrite(STDERR, ob_get_clean());
}

#[NoReturn]
function dd(...$args)
{
    dump(...$args);
    die;
}
