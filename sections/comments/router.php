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
        require serverRoot . '/sections/commentsOld/take_post.php';
        break;

    case 'take_edit':
        require serverRoot . '/sections/commentsOld/take_edit.php';
        break;

    case 'take_delete':
        require serverRoot . '/sections/commentsOld/take_delete.php';
        break;

    case 'warn':
        require serverRoot . '/sections/commentsOld/warn.php';
        break;

    case 'take_warn':
        require serverRoot . '/sections/commentsOld/take_warn.php';
        break;

    case 'get':
        require serverRoot . '/sections/commentsOld/get.php';
        break;

    case 'jump':
        require serverRoot . '/sections/commentsOld/jump.php';
        break;

    case 'artist':
    case 'collages':
    case 'requests':
    case 'torrents':
    default:
        require serverRoot . '/sections/commentsOld/comments.php';
        break;
}
