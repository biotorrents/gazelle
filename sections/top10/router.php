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


enforce_login();

if (!check_perms('site_top10')) {
    error(403);
}

if (empty($_GET['type']) || $_GET['type'] === 'torrents') {
    include serverRoot.'/sections/top10/torrents.php';
} else {
    switch ($_GET['type']) {
    case 'users':
      include serverRoot.'/sections/top10/users.php';
      break;

    case 'tags':
      include serverRoot.'/sections/top10/tags.php';
      break;

    case 'history':
      include serverRoot.'/sections/top10/history.php';
      break;

    case 'donors':
      include serverRoot.'/sections/top10/donors.php';
      break;

    default:
      error(404);
      break;
  }
}
