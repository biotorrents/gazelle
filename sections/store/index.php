<?
enforce_login();

if ($LoggedUser['DisableNips']) {
	View::show_header('Store'); ?>
	<div class='thin'>
		<h2 id='general'>Denied</h2>
		<div class='box pad' style='padding: 10px 10px 10px 20px;'>
			<p>You are not allowed to spend nips.</p>
		</div>
	</div>
	<? View::show_footer();
} else {
	if (isset($_REQUEST['item'])) {
		switch ($_REQUEST['item']) {
			case 'upload_1GB':
				include(SERVER_ROOT.'/sections/store/upload_1GB.php');
				break;
			case 'upload_10GB':
				include(SERVER_ROOT.'/sections/store/upload_10GB.php');
				break;
			case 'upload_100GB':
				include(SERVER_ROOT.'/sections/store/upload_100GB.php');
				break;
			case 'upload_1000GB':
				include(SERVER_ROOT.'/sections/store/upload_1000GB.php');
				break;
			case '1k_points':
				include(SERVER_ROOT.'/sections/store/1k_points.php');
				break;
			case '10k_points':
				include(SERVER_ROOT.'/sections/store/10k_points.php');
				break;
			case '100k_points':
				include(SERVER_ROOT.'/sections/store/100k_points.php');
				break;
			case '1m_points':
				include(SERVER_ROOT.'/sections/store/1m_points.php');
				break;
			case 'token':
				include(SERVER_ROOT.'/sections/store/token.php');
				break;
			case 'freeleechize':
				include(SERVER_ROOT.'/sections/store/freeleechize.php');
				break;
			case 'freeleechpool':
				include(SERVER_ROOT.'/sections/store/freeleechpool.php');
				break;
			case 'invite':
				include(SERVER_ROOT.'/sections/store/invite.php');
				break;
			case 'title':
				include(SERVER_ROOT.'/sections/store/title.php');
				break;
			case 'promotion':
				include(SERVER_ROOT.'/sections/store/promotion.php');
				break;
			case 'become_admin':
				include(SERVER_ROOT.'/sections/store/become_admin.php');
				break;
			case 'badge':
				include(SERVER_ROOT.'/sections/store/badge.php');
				break;
			case 'capture_user':
				include(SERVER_ROOT.'/sections/store/capture_user.php');
				break;
			default:
				error(404);
				break;
		}
	} else {
		include(SERVER_ROOT.'/sections/store/store.php');
	}
}
?>
