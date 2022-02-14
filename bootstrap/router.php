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
Flight::route('/', function () {
    if (isset($LoggedUser['ID'])) {
        #if (!isset($_REQUEST['action'])) {
        require_once __DIR__.'/../sections/index/private.php';
    #} else {
            /*
        switch ($_REQUEST['action']) {
            case 'poll':
                include(SERVER_ROOT.'/sections/forums/poll_vote.php');
                break;

            default:
                error(400);
            }
            */
        #}
    } else {
        Http::redirect('login');
    }
});

/** legal */
Flight::route('/about', function () {
    $twig = Twig::go();
    View::header('About');
    echo $twig->render('legal/about.html');
    View::footer();
});

Flight::route('/dmca', function () {
    $twig = Twig::go();
    View::header('DMCA');
    echo $twig->render('legal/dmca.html');
    View::footer();
});

Flight::route('/privacy', function () {
    $twig = Twig::go();
    View::header('Privacy');
    echo $twig->render('legal/privacy.html');
    View::footer();
});

/** log */

/** login */
Flight::route('/login', function () {
    require_once __DIR__.'/../sections/login/login.php';
});

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
