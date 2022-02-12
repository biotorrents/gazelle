<?php
declare(strict_types=1);

enforce_login();
$ENV = ENV::go();

if (empty($_REQUEST['action'])) {
    error(404);
} else {
    switch ($_REQUEST['action']) {
        case 'users':
            require_once "$ENV->SERVER_ROOT/sections/stats/users.php";
            break;

        case 'torrents':
            require_once "$ENV->SERVER_ROOT/sections/stats/torrents.php";
            break;

        default:
            break;
    }
}
