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


define('ARTIST_COLLAGE', 'Artists');
enforce_login();

if (empty($_REQUEST['action'])) {
    $_REQUEST['action'] = '';
}

switch ($_REQUEST['action']) {
    case 'new':
        if (!check_perms('site_collages_create')) {
            error(403);
        }
        require(serverRoot.'/sections/collages/new.php');
        break;

    case 'new_handle':
        if (!check_perms('site_collages_create')) {
            error(403);
        }
        require(serverRoot.'/sections/collages/new_handle.php');
        break;

    case 'add_torrent':
    case 'add_torrent_batch':
        if (!check_perms('site_collages_manage')) {
            error(403);
        }
        require(serverRoot.'/sections/collages/add_torrent.php');
        break;

    case 'add_artist':
    case 'add_artist_batch':
        if (!check_perms('site_collages_manage')) {
            error(403);
        }
        require(serverRoot.'/sections/collages/add_artist.php');
        break;

    case 'manage':
        if (!check_perms('site_collages_manage')) {
            error(403);
        }
        require(serverRoot.'/sections/collages/manage.php');
        break;

    case 'manage_handle':
        if (!check_perms('site_collages_manage')) {
            error(403);
        }
        require(serverRoot.'/sections/collages/manage_handle.php');
        break;

    case 'manage_artists':
        if (!check_perms('site_collages_manage')) {
            error(403);
        }
        require(serverRoot.'/sections/collages/manage_artists.php');
        break;

    case 'manage_artists_handle':
        if (!check_perms('site_collages_manage')) {
            error(403);
        }
        require(serverRoot.'/sections/collages/manage_artists_handle.php');
        break;

    case 'edit':
        if (!check_perms('site_edit_wiki')) {
            error(403);
        }
        require(serverRoot.'/sections/collages/edit.php');
        break;

    case 'edit_handle':
        if (!check_perms('site_edit_wiki')) {
            error(403);
        }
        require(serverRoot.'/sections/collages/edit_handle.php');
        break;

    case 'delete':
        authorize();
        require(serverRoot.'/sections/collages/delete.php');
        break;

    case 'take_delete':
        require(serverRoot.'/sections/collages/take_delete.php');
        break;

    case 'comments':
        require(serverRoot.'/sections/collages/all_comments.php');
        break;

    case 'download':
        require(serverRoot.'/sections/collages/download.php');
        break;

    case 'recover':
        //if (!check_perms('')) {
        //  error(403);
        //}
        require(serverRoot.'/sections/collages/recover.php');
        break;

    case 'create_personal':
        if (!check_perms('site_collages_personal')) {
            error(403);
        } else {
            Collages::createPersonal();
        }
        break;

    default:
        if (!empty($_GET['id'])) {
            require(serverRoot.'/sections/collages/collage.php');
        } else {
            require(serverRoot.'/sections/collages/browse.php');
        }
        break;
}
