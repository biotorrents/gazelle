<?php

declare(strict_types=1);

$app = \Gazelle\App::go();


/**
 * Flight router
 * @see https://flightphp.com/learn
 */

# endpoints go here

# start the router
#Flight::start();


/** LEGACY ROUTES */




# staff id's
$app->dbOld->query("
SELECT m.ID, m.Username
FROM users_main AS m
  JOIN permissions AS p ON p.ID=m.PermissionID
WHERE p.DisplayStaff='1'");

while (list($StaffID, $StaffName) = $app->dbOld->next_record()) {
    $StaffIDs[$StaffID] = $StaffName;
}

uasort($StaffIDs, 'strcasecmp');
$app->cache->set('staff_ids', $StaffIDs);

# request
$_REQUEST['action'] ??= null;
switch ($_REQUEST['action']) {
    case 'takecompose':
        require('takecompose.php');
        break;
    case 'takeedit':
        require('takeedit.php');
        break;
    case 'compose':
        require('compose.php');
        break;
    case 'viewconv':
        require('conversation.php');
        break;
    case 'masschange':
        require('massdelete_handle.php');
        break;
    case 'get_post':
        require('get_post.php');
        break;
    case 'forward':
        require('forward.php');
        break;
    default:
        require($app->env->serverRoot.'/sections/inbox/inbox.php');
}
