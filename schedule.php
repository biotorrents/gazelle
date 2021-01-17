<?php
declare(strict_types=1);

define('MEMORY_EXCEPTION', true);
define('TIME_EXCEPTION', true);
define('ERROR_EXCEPTION', true);

$_SERVER['SCRIPT_FILENAME'] = 'schedule.php'; // CLI fix
require_once 'classes/script_start.php';
