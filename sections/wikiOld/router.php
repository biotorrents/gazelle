<?php

declare(strict_types=1);

$ENV = \Gazelle\ENV::go();

if (!empty($_REQUEST['action'])) {
    switch ($_REQUEST['action']) {
        case 'add_alias':
            require_once "$ENV->serverRoot/sections/wikiOld/add_alias.php";
            break;

        case 'delete_alias':
            require_once "$ENV->serverRoot/sections/wikiOld/delete_alias.php";
            break;
    }
}
