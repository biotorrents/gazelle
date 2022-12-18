<?php
#declare(strict_types=1);

if (empty($_GET['type']) || $_GET['type'] == 'inbox' || $_GET['type'] == 'sentbox') {
  require(serverRoot.'/sections/ajax/inbox/inbox.php');
} elseif ($_GET['type'] == 'viewconv') {
  require(serverRoot.'/sections/ajax/inbox/viewconv.php');
} else {
  echo json_encode(array('status' => 'failure'));
  error();
}
