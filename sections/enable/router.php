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


if (isset($user['ID']) || !isset($_GET['token']) || !FEATURE_EMAIL_REENABLE) {
    header('Location: index.php');
    error();
}

if (isset($_GET['token'])) {
    $Err = AutoEnable::handle_token($_GET['token']);
}

View::header('Enable Request');
echo $Err; // This will always be set
View::footer();
