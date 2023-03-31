<?php

declare(strict_types=1);


$app = \Gazelle\App::go();

/**
 * Flight router
 * @see https://flightphp.com/learn
 */

# endpoints go here

# start the router
#Flight::start();


/** LEGACY ROUTES */


/*****************************************************************
Finish removing the take[action] pages and utilize the index correctly
Should the advanced search really only show if they match 3 perms?
Make sure all constants are defined in config.php and not in random files
*****************************************************************/

enforce_login();

#require_once serverRoot."/classes/validate.class.php" ;
$Val = new Validate();

if (empty($_REQUEST['action'])) {
    $_REQUEST['action'] = '';
}

switch ($_REQUEST['action']) {
  case 'notify':
    require_once 'notify_edit.php';
    break;

  case 'notify_handle':
    require_once 'notify_handle.php';
    break;

  case 'notify_delete':
    authorize();
    if ($_GET['id'] && is_numeric($_GET['id'])) {
        $app->dbOld->query("DELETE FROM users_notify_filters WHERE ID='".db_string($_GET['id'])."' AND UserID='{$app->userNew->core['id']}'");
        $ArtistNotifications = $app->cacheOld->get_value('notify_artists_'.$app->userNew->core['id']);

        if (is_array($ArtistNotifications) && $ArtistNotifications['ID'] == $_GET['id']) {
            $app->cacheOld->delete_value('notify_artists_'.$app->userNew->core['id']);
        }
    }

    $app->cacheOld->delete_value('notify_filters_'.$app->userNew->core['id']);
    Http::redirect("user.php?action=notify");
    break;

  case 'search':// User search
    if (check_perms('admin_advanced_user_search') && check_perms('users_view_ips') && check_perms('users_view_email')) {
        require_once 'advancedsearch.php';
    } else {
        require_once 'search.php';
    }
    break;

  case 'edit':
    require_once 'edit.php';
    break;

  case 'take_edit':
    require_once 'take_edit.php';
    break;

  case '2fa':
    require_once '2fa.php';
    break;

  case 'invitetree':
    require_once 'invitetree.php';
    break;

  case 'invite':
    require_once 'invite.php';
    break;

  case 'take_invite':
    require_once 'take_invite.php';
    break;

  case 'delete_invite':
    require_once 'delete_invite.php';
    break;

  case 'dupes':
    require_once 'manage_linked.php';
    break;

  case 'sessions':
    require_once 'sessions.php';
    break;

  case 'permissions':
    require_once 'permissions.php';
    break;

  case 'similar':
    require_once 'similar.php';
    break;

  case 'moderate':
    require_once 'takemoderate.php';
    break;

  case 'hnr':
    require_once 'hnr.php';
    break;

  case 'take_donate':
    break;

  case 'take_update_rank':
    break;

  case 'points':
    require_once serverRoot.'/sections/user/points.php';
    break;

  case 'token':
    require_once __DIR__ . '/token.php';
    break;

  default:
    if (isset($_REQUEST['id'])) {
        require_once serverRoot.'/sections/user/user.php';
    } else {
        #Http::redirect("index.php");
    }
}
