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
$RequestTax = 0.1;

// Minimum and default amount of upload to remove from the user when they vote.
// Also change in static/js/requests.js
$MinimumVote = 20 * 1024 * 1024;

if (!isset($_REQUEST['action'])) {
    include serverRoot.'/sections/requests/requests.php';
} else {
    switch ($_REQUEST['action']) {
        case 'new':
        case 'edit':
            include serverRoot.'/sections/requests/new_edit.php';
            break;

        case 'takevote':
            include serverRoot.'/sections/requests/take_vote.php';
            break;

        case 'takefill':
            include serverRoot.'/sections/requests/take_fill.php';
            break;

        case 'takenew':
        case 'takeedit':
            include serverRoot.'/sections/requests/take_new_edit.php';
            break;

        case 'delete':
        case 'unfill':
            include serverRoot.'/sections/requests/interim.php';
            break;

        case 'takeunfill':
            include serverRoot.'/sections/requests/take_unfill.php';
            break;

        case 'takedelete':
            include serverRoot.'/sections/requests/take_delete.php';
            break;

        case 'view':
        case 'viewrequest':
            include serverRoot.'/sections/requests/request.php';
            break;

            # Single search bar workaround
        case 'search':
            include serverRoot.'/sections/requests/requests.php';
            break;

        default:
            error(0);
    }
}
