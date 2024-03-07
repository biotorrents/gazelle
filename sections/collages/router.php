<?php

declare(strict_types=1);

/**
 * Flight router
 * @see https://flightphp.com/learn
 */

# endpoints go here

# start the router
#Flight::start();


/** LEGACY ROUTES */




if (empty($_REQUEST['action'])) {
    $_REQUEST['action'] = '';
}

switch ($_REQUEST['action']) {
    case 'new':
        require(serverRoot . '/sections/collages/createUpdate.php');
        break;

    case 'new_handle':
        if ($app->user->cant(["collages" => "create"])) {
            error(403);
        }
        require(serverRoot . '/sections/collages/new_handle.php');
        break;

    case 'add_torrent':
    case 'add_torrent_batch':
        if ($app->user->cant(["collages" => "updateAny"])) {
            error(403);
        }
        require(serverRoot . '/sections/collages/add_torrent.php');
        break;

    case 'manage':
        if ($app->user->cant(["collages" => "updateAny"])) {
            error(403);
        }
        require(serverRoot . '/sections/collages/manage.php');
        break;

    case 'manage_handle':
        if ($app->user->cant(["collages" => "updateAny"])) {
            error(403);
        }
        require(serverRoot . '/sections/collages/manage_handle.php');
        break;

    case 'edit':
        require(serverRoot . '/sections/collages/createUpdate.php');
        break;

    case 'edit_handle':
        if ($app->user->cant(["wiki" => "updateAny"])) {
            error(403);
        }
        require(serverRoot . '/sections/collages/edit_handle.php');
        break;

    case 'delete':

        require(serverRoot . '/sections/collages/delete.php');
        break;

    case 'take_delete':
        require(serverRoot . '/sections/collages/take_delete.php');
        break;

    case 'download':
        require(serverRoot . '/sections/collages/download.php');
        break;

    case 'recover':
        require(serverRoot . '/sections/collages/recover.php');
        break;

    case 'create_personal':
        if ($app->user->cant(["collages" => "create"])) {
            error(403);
        } else {
            Gazelle\Collages::createPersonal();
        }
        break;

    default:
        if (!empty($_GET['id'])) {
            require(serverRoot . '/sections/collages/collage.php');
        } else {
            require(serverRoot . '/sections/collages/browse.php');
        }
        break;
}
