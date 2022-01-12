<?php
declare(strict_types = 1);

enforce_login();
$P = db_array($_POST);

$FriendID = (int) $_REQUEST['friendid'];
Security::int($FriendID);

if (!empty($_REQUEST['action'])) {
    switch ($_REQUEST['action']) {
    case 'add':
      require_once "$ENV->SERVER_ROOT/sections/friends/add.php";
      break;

    case 'Remove friend':
      authorize();
      require_once "$ENV->SERVER_ROOT/sections/friends/remove.php";
      break;

    case 'Update':
      authorize();
      require_once "$ENV->SERVER_ROOT/sections/friends/comment.php";
      break;

    case 'Contact':
      header("Location: inbox.php?action=compose&to=$FriendID");
      break;
      
    default:
      error(404);
  }
} else {
    require_once "$ENV->SERVER_ROOT/sections/friends/friends.php";
}
