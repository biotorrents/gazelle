<?
enforce_login();
if (isset($_GET['method'])) {
  switch ($_GET['method']) {
    case 'screenshots':
      include(SERVER_ROOT.'/sections/better/screenshots.php');
      break;
    case 'encode':
      include(SERVER_ROOT.'/sections/better/encode.php');
      break;
    case 'snatch':
      include(SERVER_ROOT.'/sections/better/snatch.php');
      break;
    case 'upload':
      include(SERVER_ROOT.'/sections/better/upload.php');
      break;
    case 'tags':
      include(SERVER_ROOT.'/sections/better/tags.php');
      break;
    case 'folders':
      include(SERVER_ROOT.'/sections/better/folders.php');
      break;
    case 'files':
      include(SERVER_ROOT.'/sections/better/files.php');
      break;
    default:
      error(404);
      break;
  }
} else {
  include(SERVER_ROOT.'/sections/better/better.php');
}
?>
