<?php
declare(strict_types=1);


/**
 * Example CLI app docblock.
 * This should really be a script that runs unit tests,
 * and emails the admin on any failure.
 */

# cli bootstrap
# todo: check me!
require_once __DIR__."/../../bootstrap/cli.php";

# do stuff
echo "start script output";
echo "\n"; # clear

$variable = random_bytes(128);
!d($variable);
