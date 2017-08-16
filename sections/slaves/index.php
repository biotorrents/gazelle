<?
enforce_login();

if (isset($_REQUEST['action'])) {
  switch ($_REQUEST['action']) {
    case '':
      include('upload_1GB.php');
      break;
    default:
      error(404);
      break;
  }
} else {
  require(SERVER_ROOT.'/sections/slaves/slaves.php');
}
?>
