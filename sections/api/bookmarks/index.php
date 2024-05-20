<?php

#declare(strict_types=1);

// Number of users per page
define('BOOKMARKS_PER_PAGE', '20');

if (empty($_REQUEST['type'])) {
    $_REQUEST['type'] = 'torrents';
}

switch ($_REQUEST['type']) {
    case 'torrents':
        require serverRoot . '/sections/ajax/bookmarks/torrents.php';
        break;

    case 'artists':
        require serverRoot . '/sections/ajax/bookmarks/artists.php';
        break;

        /*
        case 'collages':
          $_GET['bookmarks'] = 1;
          require serverRoot.'/sections/ajax/collages/browse.php';
          break;
        */

        /*
        case 'requests':
          $_GET['type'] = 'bookmarks';
          require serverRoot.'/sections/ajax/requests/requests.php';
          break;
        */

    default:
        \Gazelle\Api\Base::failure(400);
        break;

        /*
        print
          json_encode(
              array(
              'status' => 'failure'
            )
          );
        error();
        */
}
