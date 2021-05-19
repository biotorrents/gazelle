<?php
#declare(strict_types=1);

if (empty($_GET['type']) || $_GET['type'] == 'inbox' || $_GET['type'] == 'sentbox') {
  require(SERVER_ROOT.'/sections/ajax/inbox/inbox.php');
} elseif ($_GET['type'] == 'viewconv') {
  require(SERVER_ROOT.'/sections/ajax/inbox/viewconv.php');
} else {
  echo json_encode(array('status' => 'failure'));
  error();
}

?>
