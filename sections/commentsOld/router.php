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

// fix old links
if ($_REQUEST['action'] === 'artists') {
    $_REQUEST['action'] = 'artist';
} elseif ($_REQUEST['action'] === 'my_torrents') {
    $_REQUEST['action'] = 'torrents';
    $_REQUEST['type'] = 'uploaded';
}

$Action = '';
if (!empty($_REQUEST['action'])) {
    $Action = $_REQUEST['action'];
}

switch ($Action) {
  case 'take_post':
    require serverRoot . '/sections/comments/take_post.php';
    break;

  case 'take_edit':
    require serverRoot . '/sections/comments/take_edit.php';
    break;

  case 'take_delete':
    require serverRoot . '/sections/comments/take_delete.php';
    break;

  case 'warn':
    require serverRoot . '/sections/comments/warn.php';
    break;

  case 'take_warn':
    require serverRoot . '/sections/comments/take_warn.php';
    break;

  case 'get':
    require serverRoot . '/sections/comments/get.php';
    break;

  case 'jump':
    require serverRoot . '/sections/comments/jump.php';
    break;

  case 'artist':
  case 'collages':
  case 'requests':
  case 'torrents':
  default:
    require serverRoot . '/sections/comments/comments.php';
    break;
}
