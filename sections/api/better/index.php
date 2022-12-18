<?php
//Include all the basic stuff...

enforce_login();
if (isset($_GET['method'])) {
  switch ($_GET['method']) {
    case 'transcode':
      include(serverRoot.'/sections/ajax/better/transcode.php');
      break;
    case 'single':
      include(serverRoot.'/sections/ajax/better/single.php');
      break;
    case 'snatch':
      include(serverRoot.'/sections/ajax/better/snatch.php');
      break;
    case 'artistless':
      include(serverRoot.'/sections/ajax/better/artistless.php');
      break;
    case 'tags':
      include(serverRoot.'/sections/ajax/better/tags.php');
      break;
    case 'folders':
      include(serverRoot.'/sections/ajax/better/folders.php');
      break;
    case 'files':
      include(serverRoot.'/sections/ajax/better/files.php');
      break;
    case 'upload':
      include(serverRoot.'/sections/ajax/better/upload.php');
      break;
    default:
      echo json_encode(array('status' => 'failure'));
      break;
  }
} else {
  echo json_encode(array('status' => 'failure'));
}
?>
