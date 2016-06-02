<?

enforce_login();

if($_REQUEST['action']) {
	switch($_REQUEST['action']) {
		case 'email':
			include('delete_email.php');
			break;
		case 'takeemail':
			include('take_delete_email.php');
			break;
		case 'ip':
			break;
		default:
			header('Location: index.php');
	}
}

?>
