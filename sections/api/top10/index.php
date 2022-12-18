<?php
#declare(strict_types=1);

// Already done in /sections/ajax/index.php
//enforce_login();

if (!check_perms('site_top10')) {
    echo json_encode(array('status' => 'failure'));
    error();
}

if (empty($_GET['type']) || $_GET['type'] === 'torrents') {
    include serverRoot.'/sections/ajax/top10/torrents.php';
} else {
    switch ($_GET['type']) {
    case 'users':
      include serverRoot.'/sections/ajax/top10/users.php';
      break;

    case 'tags':
      include serverRoot.'/sections/ajax/top10/tags.php';
      break;

    case 'history':
      include serverRoot.'/sections/ajax/top10/history.php';
      break;

    default:
      echo json_encode(array('status' => 'failure'));
      break;
  }
}
