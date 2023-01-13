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

if (!check_perms('site_upload')) {
    error('Please read the site wiki for information on how to become a Member and gain upload privileges.');
}

/*
if ($app->userNew->extra['DisableUpload']) {
    error('Your upload privileges have been revoked.');
}
*/

// Build the page
if (!empty($_POST['submit'])) {
    require_once 'upload_handle.php';
} else {
    require_once serverRoot.'/sections/upload/upload.php';
}
