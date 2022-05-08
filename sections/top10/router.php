<?php
declare(strict_types=1);

/**
 * Flight router
 * @see https://flightphp.com/learn
 */

# endpoints go here

# start the router
Flight::start();


/** LEGACY ROUTES */


enforce_login();

if (!check_perms('site_top10')) {
    error(403);
}

include SERVER_ROOT.'/sections/torrents/functions.php'; //Has get_reports($TorrentID);
if (empty($_GET['type']) || $_GET['type'] === 'torrents') {
    include SERVER_ROOT.'/sections/top10/torrents.php';
} else {
    switch ($_GET['type']) {
    case 'users':
      include SERVER_ROOT.'/sections/top10/users.php';
      break;

    case 'tags':
      include SERVER_ROOT.'/sections/top10/tags.php';
      break;

    case 'history':
      include SERVER_ROOT.'/sections/top10/history.php';
      break;

    case 'donors':
      include SERVER_ROOT.'/sections/top10/donors.php';
      break;

    default:
      error(404);
      break;
  }
}
