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


if (isset($user['ID'])) {
    if (!isset($_REQUEST['action'])) {
        include('private.php');
    } else {
        switch ($_REQUEST['action']) {
            case 'poll':
                include(SERVER_ROOT.'/sections/forums/poll_vote.php');
                break;

            default:
                error(400);
        }
    }
} else {
    Http::redirect('login');
}
