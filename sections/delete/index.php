<?

enforce_login();

if ($_REQUEST['action']) {
  switch($_REQUEST['action']) {
    case 'email':
      include('delete_email.php');
      break;
    case 'takeemail':
      include('take_delete_email.php');
      break;
    case 'ip':
      include('delete_ip.php');
      break;
    case 'takeip':
      include('take_delete_ip.php');
      break;
    default:
      header('Location: index.php');
  }
} else {
  header('Location: index.php');
}

?>
