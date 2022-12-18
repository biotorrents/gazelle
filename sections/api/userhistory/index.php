<?php

#declare(strict_types=1);

if ($_GET['type']) {
    switch ($_GET['type']) {
    case 'posts':
      // Load post history page
      include('post_history.php');
      break;

    default:
      echo json_encode(
          array('status' => 'failure')
      );
  }
} else {
    echo json_encode(
        array('status' => 'failure')
    );
}
