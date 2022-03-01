<?php
#declare(strict_types=1);

enforce_login();
if ($user['DisablePoints']) {
    View::header('Store'); ?>
<div>
  <h2>Denied</h2>
  <div class="box">
    <p>You are not allowed to spend <?=BONUS_POINTS?>.</p>
  </div>
</div>
<?php
View::footer();
} else {
    if (isset($_REQUEST['item'])) {
        switch ($_REQUEST['item']) {
      case 'upload_1':
        include SERVER_ROOT.'/sections/store/upload_1.php';
        break;

      case 'upload_10':
        include SERVER_ROOT.'/sections/store/upload_10.php';
        break;

      case 'upload_100':
        include SERVER_ROOT.'/sections/store/upload_100.php';
        break;

      case 'upload_1000':
        include SERVER_ROOT.'/sections/store/upload_1000.php';
        break;

      case 'points_1':
        include SERVER_ROOT.'/sections/store/points_1.php';
        break;

      case 'points_10':
        include SERVER_ROOT.'/sections/store/points_10.php';
        break;

      case 'points_100':
        include SERVER_ROOT.'/sections/store/points_100.php';
        break;

      case 'points_1000':
        include SERVER_ROOT.'/sections/store/points_1000.php';
        break;

      case 'token':
        include SERVER_ROOT.'/sections/store/token.php';
        break;

      case 'freeleechize':
        include SERVER_ROOT.'/sections/store/freeleechize.php';
        break;

      case 'freeleechpool':
        include SERVER_ROOT.'/sections/store/freeleechpool.php';
        break;

      case 'invite':
        include SERVER_ROOT.'/sections/store/invite.php';
        break;

      case 'title':
        include SERVER_ROOT.'/sections/store/title.php';
        break;

      case 'promotion':
        include SERVER_ROOT.'/sections/store/promotion.php';
        break;

      case 'become_admin':
        include SERVER_ROOT.'/sections/store/become_admin.php';
        break;

      case 'badge':
        include SERVER_ROOT.'/sections/store/badge.php';
        break;

      case 'coinbadge':
        include SERVER_ROOT.'/sections/store/coinbadge.php';
        break;

      case 'capture_user':
        include SERVER_ROOT.'/sections/store/capture_user.php';
        break;

      default:
        error(404);
        break;
    }
    } else {
        include SERVER_ROOT.'/sections/store/store.php';
    }
}
