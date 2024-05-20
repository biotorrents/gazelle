<?php

declare(strict_types=1);

$app = Gazelle\App::go();

/**
 * Flight router
 * @see https://flightphp.com/learn
 */

# endpoints go here

# start the router
#Flight::start();


/** LEGACY ROUTES */




// Number of users per page
define('BOOKMARKS_PER_PAGE', '20');

if (empty($_REQUEST['action'])) {
    $_REQUEST['action'] = 'view';
}

switch ($_REQUEST['action']) {
    case 'add':
        require serverRoot . '/sections/bookmarks/add.php';
        break;

    case 'remove':
        require serverRoot . '/sections/bookmarks/remove.php';
        break;

    case 'remove_snatched':

        $app->dbOld->query("
      CREATE TEMPORARY TABLE snatched_groups_temp
        (GroupID int PRIMARY KEY)");

        $app->dbOld->query("
      INSERT INTO snatched_groups_temp
      SELECT DISTINCT GroupID
      FROM torrents AS t
        JOIN xbt_snatched AS s ON s.fid = t.ID
      WHERE s.uid = '{$app->user->core['id']}'");

        $app->dbOld->query("
      DELETE b
      FROM bookmarks_torrents AS b
        JOIN snatched_groups_temp AS s
      USING(GroupID)
      WHERE b.UserID = '{$app->user->core['id']}'");

        $app->cache->delete("bookmarks_group_ids_$UserID");
        Gazelle\Http::redirect("bookmarks.php");
        error();
        break;

    case 'view':
        if (empty($_REQUEST['type'])) {
            $_REQUEST['type'] = 'torrents';
        }

        switch ($_REQUEST['type']) {
            case 'torrents':
                require serverRoot . '/sections/bookmarks/torrents.php';
                break;

            case 'artists':
                require serverRoot . '/sections/bookmarks/artists.php';
                break;

            case 'collages':
                $_GET['bookmarks'] = '1';
                require serverRoot . '/sections/collages/browse.php';
                break;

            case 'requests':
                $_GET['type'] = 'bookmarks';
                require serverRoot . '/sections/requests/requests.php';
                break;

            default:
                error(404);
        }
        break;

    default:
        error(404);
}
