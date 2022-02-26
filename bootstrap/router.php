<?php
declare(strict_types=1);

/**
 * Flight router
 * @see https://flightphp.com/learn
 */

/** api */
/*
Flight::route('/api/*', function () {
    # needs its own router
    require_once __DIR__.'/../sections/api/router.php';
});
*/


/** artist */


/** better */


/** blog */


/** bookmarks */


/** collages */


/** comments */


/** donate */


/** enable */


/** feeds */


/** forums */


/** friends */


/** image */


/** inbox */


/** index */
Flight::route('/', function () {
    if (isset(G::$LoggedUser['ID'])) {
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

Flight::route('/canary', function () {
    header('Content-Type: text/plain; charset=utf-8');
    require_once __DIR__.'/../public/docs/canary.txt';
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

Flight::route('/pubkey', function () {
    header('Content-Type: text/plain; charset=utf-8');
    require_once __DIR__.'/../public/docs/pubkey.txt';
});


/** log */


/** login */
Flight::route('/login', function () {
    # 2022-02-13: currently lots of crazy logic here
    require_once __DIR__.'/../sections/login/index.php';
    #require_once __DIR__.'/../sections/login/login.php';
});


/** logout */
Flight::route('/logout', function () {
    # no more bullshit
    logout_all_sessions();
});


/** peerupdate */


/** pwgen */


/** register */


/** reports */


/** reportsv2 */


/** requests */


/** rules */
Flight::route('/rules', function () {
    require_once __DIR__.'/../sections/rules/rules.php';
});

# chat
Flight::route('/rules/chat', function () {
    $ENV = ENV::go();
    $twig = Twig::go();

    $text = Text::parse(
        file_get_contents("{$ENV->SERVER_ROOT}/templates/rules/chat.md")
    );

    View::header('Chat rules');
    echo $twig->render('rules/rules.twig', ['text' => $text]);
    View::footer();
});

Flight::route('/rules/clients', function () {
    require_once __DIR__.'/../sections/rules/clients.php';
});

# collages
Flight::route('/rules/collages', function () {
    $ENV = ENV::go();
    $twig = Twig::go();

    $text = Text::parse(
        file_get_contents("{$ENV->SERVER_ROOT}/templates/rules/collages.md"),
        false
    );

    View::header('Collection rules');
    echo $twig->render('rules/rules.twig', ['text' => $text]);
    View::footer();
});

Flight::route('/rules/ratio', function () {
    require_once __DIR__.'/../sections/rules/ratio.php';
});

Flight::route('/rules/requests', function () {
    require_once __DIR__.'/../sections/rules/requests.php';
});

Flight::route('/rules/tags', function () {
    require_once __DIR__.'/../sections/rules/tags.php';
});

Flight::route('/rules/upload', function () {
    require_once __DIR__.'/../sections/rules/upload.php';
});


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
