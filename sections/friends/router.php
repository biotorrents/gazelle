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
$P = db_array($_POST);

$FriendID = (int) $_REQUEST['friendid'];
Security::int($FriendID);

if (!empty($_REQUEST['action'])) {
    switch ($_REQUEST['action']) {
    case 'add':
      require_once "$ENV->serverRoot/sections/friends/add.php";
      break;

    case 'Remove friend':
      authorize();
      require_once "$ENV->serverRoot/sections/friends/remove.php";
      break;

    case 'Update':
      authorize();
      require_once "$ENV->serverRoot/sections/friends/comment.php";
      break;

    case 'Contact':
      Http::redirect("inbox.php?action=compose&to=$FriendID");
      break;
      
    default:
      error(404);
  }
} else {
    require_once "$ENV->serverRoot/sections/friends/friends.php";
}
