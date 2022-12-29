<?php
declare(strict_types=1);

/**
 * Flight router
 * @see https://flightphp.com/learn
 */

# endpoints go here

# start the router
#Flight::start();


/** LEGACY ROUTES */


enforce_login();
if ($user['DisablePoints']) {
    View::header('Store'); ?>
<div>
    <h2>Denied</h2>
    <div class="box">
        <p>You are not allowed to spend <?=bonusPoints?>.</p>
    </div>
</div>
<?php
View::footer();
} else {
    if (isset($_REQUEST['item'])) {
        switch ($_REQUEST['item']) {
      case 'upload_1':
        include serverRoot.'/sections/store/upload_1.php';
        break;

      case 'upload_10':
        include serverRoot.'/sections/store/upload_10.php';
        break;

      case 'upload_100':
        include serverRoot.'/sections/store/upload_100.php';
        break;

      case 'upload_1000':
        include serverRoot.'/sections/store/upload_1000.php';
        break;

      case 'points_1':
        include serverRoot.'/sections/store/points_1.php';
        break;

      case 'points_10':
        include serverRoot.'/sections/store/points_10.php';
        break;

      case 'points_100':
        include serverRoot.'/sections/store/points_100.php';
        break;

      case 'points_1000':
        include serverRoot.'/sections/store/points_1000.php';
        break;

      case 'token':
        include serverRoot.'/sections/store/token.php';
        break;

      case 'freeleechize':
        include serverRoot.'/sections/store/freeleechize.php';
        break;

      case 'freeleechpool':
        include serverRoot.'/sections/store/freeleechpool.php';
        break;

      case 'invite':
        include serverRoot.'/sections/store/invite.php';
        break;

      case 'title':
        include serverRoot.'/sections/store/title.php';
        break;

      case 'promotion':
        include serverRoot.'/sections/store/promotion.php';
        break;

      case 'become_admin':
        include serverRoot.'/sections/store/become_admin.php';
        break;

      case 'badge':
        include serverRoot.'/sections/store/badge.php';
        break;

      case 'coinbadge':
        include serverRoot.'/sections/store/coinbadge.php';
        break;

      case 'capture_user':
        include serverRoot.'/sections/store/capture_user.php';
        break;

      default:
        error(404);
        break;
    }
    } else {
        include serverRoot.'/sections/store/store.php';
    }
}
