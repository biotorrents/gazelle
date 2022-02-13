<?php
declare(strict_types=1);

/**
 * Flight router
 * @see https://flightphp.com/learn
 */

/** api */

/** artist */

/** better */

/** blog */

/** bookmarks */

/** collages */

/** comments */

/** contest */

/** donate */

/** enable */

/** feeds */

/** forums */

/** friends */

/** image */

/** inbox */

/** index */

/** legal */

/** log */

/** login */

/** logout */

/** peerupdate */

/** pwgen */

/** register */

/** reports */

/** reportsv2 */

/** requests */

/** rules */

/** schedule */

/** snatchlist */

/** staff */

/** staffpm */

/** stats */
Flight::route('/stats/torrents', function () {
    enforce_login();
    require_once __DIR__.'/../sections/stats/torrents.php';
});

Flight::route('/stats/users', function () {
    enforce_login();
    require_once __DIR__.'/../sections/stats/users.php';
});

/** store */

/** tools */

/** top10 */

/** torrents */

/** upload */

/** user */

/** userhistory */

/** wiki */

# start the router
Flight::start();
