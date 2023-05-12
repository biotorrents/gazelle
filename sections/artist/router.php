<?php

declare(strict_types=1);

$app = \Gazelle\App::go();

/**
 * Flight router
 * @see https://flightphp.com/learn
 */

# endpoints go here

# start the router
#Flight::start();


/** LEGACY ROUTES */


/**************************************************************************
Artists Switch Center

This page acts as a switch that includes the real artist pages (to keep
the root less cluttered).

enforce_login() is run here - the entire artist pages are off limits for
non members.
 ****************************************************************************/

// Width and height of similar artist map
define('WIDTH', 585);
define('HEIGHT', 400);

enforce_login();
if (!empty($_POST['action'])) {
    switch ($_POST['action']) {
        case 'edit':
            require(serverRoot . '/sections/artist/takeedit.php');
            break;
        case 'download':
            require(serverRoot . '/sections/artist/download.php');
            break;
        case 'rename':
            require(serverRoot . '/sections/artist/rename.php');
            break;
        case 'add_similar':
            require(serverRoot . '/sections/artist/add_similar.php');
            break;
        case 'change_artistid':
            require(serverRoot . '/sections/artist/change_artistid.php');
            break;
        case 'concert_thread':
            include(serverRoot . '/sections/artist/concert_thread.php');
            break;
        case 'take_concert_thread':
            include(serverRoot . '/sections/artist/take_concert_thread.php');
            break;
        default:
            error(0);
    }
} elseif (!empty($_GET['action'])) {
    switch ($_GET['action']) {
        case 'autocomplete':
            require('sections/artist/autocomplete.php');
            break;

        case 'edit':
            require(serverRoot . '/sections/artist/edit.php');
            break;
        case 'delete':
            require(serverRoot . '/sections/artist/delete.php');
            break;
        case 'revert':
            require(serverRoot . '/sections/artist/takeedit.php');
            break;
        case 'history':
            require(serverRoot . '/sections/artist/history.php');
            break;
        case 'vote_similar':
            require(serverRoot . '/sections/artist/vote_similar.php');
            break;
        case 'delete_similar':
            require(serverRoot . '/sections/artist/delete_similar.php');
            break;
        case 'similar':
            require(serverRoot . '/sections/artist/similar.php');
            break;
        case 'similar_bg':
            require(serverRoot . '/sections/artist/similar_bg.php');
            break;
        case 'notify':
            require(serverRoot . '/sections/artist/notify.php');
            break;
        case 'notifyremove':
            require(serverRoot . '/sections/artist/notifyremove.php');
            break;
        case 'change_artistid':
            require(serverRoot . '/sections/artist/change_artistid.php');
            break;
        default:
            error(0);
            break;
    }
} else {
    if (!empty($_GET['id'])) {
        include(serverRoot . '/sections/artist/artist.php');
    } elseif (!empty($_GET['artistname'])) {
        $NameSearch = str_replace('\\', '\\\\', trim($_GET['artistname']));
        $app->dbOld->query("
      SELECT ArtistID, Name
      FROM artists_group
      WHERE Name LIKE '" . db_string($NameSearch) . "'");
        if (!$app->dbOld->has_results()) {
            if (isset($app->user->extra['SearchType']) && $app->user->extra['SearchType']) {
                header('Location: torrents.php?action=advanced&artistname=' . urlencode($_GET['artistname']));
            } else {
                header('Location: torrents.php?search=' . urlencode($_GET['artistname']));
            }
            error();
        }
        list($FirstID, $Name) = $app->dbOld->next_record(MYSQLI_NUM, false);
        if ($app->dbOld->record_count() === 1 || !strcasecmp($Name, $NameSearch)) {
            Http::redirect("artist.php?id=$FirstID");
            error();
        }
        while (list($ID, $Name) = $app->dbOld->next_record(MYSQLI_NUM, false)) {
            if (!strcasecmp($Name, $NameSearch)) {
                Http::redirect("artist.php?id=$ID");
                error();
            }
        }
        Http::redirect("artist.php?id=$FirstID");
        error();
    } else {
        Http::redirect("torrents.php");
    }
}
