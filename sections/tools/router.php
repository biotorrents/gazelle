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


/**
 * Tools switch center
 * This page acts as a switch for the tools pages.
 */

$ENV = ENV::go();
#!d(Http::query());exit;

if (isset($argv[1])) {
    $_REQUEST['action'] = $argv[1];
} else {
    if (empty($_REQUEST['action']) || $_REQUEST['action'] !== 'ocelot') {
        // If set, do not enforce login so we can set the encryption key w/o an account
        if (!$ENV->FEATURE_SET_ENC_KEY_PUBLIC) {
            enforce_login();
        }
    }
}

# Error checking
if (!isset($_REQUEST['action'])) {
    include SERVER_ROOT.'/sections/tools/tools.php';
    #error('Need to set an "action" parameter in sections/tools/tools.php.');
}

if (substr($_REQUEST['action'], 0, 16) === 'rerender_gallery' && !isset($argv[1])) {
    if (!check_perms('site_debug')) {
        error(403);
    }
}

$Val = new Validate;
$Feed = new Feed;

# Finally
switch ($_REQUEST['action']) {
  // Services
  case 'get_host':
    include SERVER_ROOT.'/sections/tools/services/get_host.php';
    break;

  case 'get_cc':
    include SERVER_ROOT.'/sections/tools/services/get_cc.php';
    break;

  // Managers
  case 'forum':
    include SERVER_ROOT.'/sections/tools/managers/forum_list.php';
    break;

  case 'forum_alter':
    include SERVER_ROOT.'/sections/tools/managers/forum_alter.php';
    break;

  case 'whitelist':
    include SERVER_ROOT.'/sections/tools/managers/whitelist_list.php';
    break;

  case 'whitelist_alter':
    include SERVER_ROOT.'/sections/tools/managers/whitelist_alter.php';
    break;

  case 'enable_requests':
    include SERVER_ROOT.'/sections/tools/managers/enable_requests.php';
    break;

  case 'ajax_take_enable_request':
    if (FEATURE_EMAIL_REENABLE) {
        include SERVER_ROOT.'/sections/tools/managers/ajax_take_enable_request.php';
    } else {
        // Prevent post requests to the ajax page
        Http::redirect("tools.php");
        error();
    }
    break;

  case 'login_watch':
    include SERVER_ROOT.'/sections/tools/managers/login_watch.php';
    break;

  case 'email_blacklist':
    include SERVER_ROOT.'/sections/tools/managers/email_blacklist.php';
    break;

  case 'email_blacklist_alter':
    include SERVER_ROOT.'/sections/tools/managers/email_blacklist_alter.php';
    break;

  case 'email_blacklist_search':
    include SERVER_ROOT.'/sections/tools/managers/email_blacklist_search.php';
    break;

  case 'editnews':
  case 'news':
    include SERVER_ROOT.'/sections/tools/managers/news.php';
    break;

  case 'edit_tags':
    include SERVER_ROOT.'/sections/tools/misc/tags.php';
    break;

  case 'takeeditnews':
    if (!check_perms('admin_manage_news')) {
        error(403);
    }

    if (is_number($_POST['newsid'])) {
        $db->prepared_query("
          UPDATE news
          SET Title = '".db_string($_POST['title'])."',
            Body = '".db_string($_POST['body'])."'
          WHERE ID = '".db_string($_POST['newsid'])."'");

        $cache->delete_value('news');
        $cache->delete_value('feed_news');
    }
    Http::redirect("index.php");
    break;

  case 'deletenews':
    if (!check_perms('admin_manage_news')) {
        error(403);
    }

    if (is_number($_GET['id'])) {
        authorize();
        $db->prepared_query("
          DELETE FROM news
          WHERE ID = '".db_string($_GET['id'])."'");

        $cache->delete_value('news');
        $cache->delete_value('feed_news');

        // Deleting latest news
        $LatestNews = $cache->get_value('news_latest_id');
        if ($LatestNews !== false && $LatestNews === $_GET['id']) {
            $cache->delete_value('news_latest_id');
            $cache->delete_value('news_latest_title');
        }
    }
    Http::redirect("index.php");
    break;

  case 'takenewnews':
    if (!check_perms('admin_manage_news')) {
        error(403);
    }

    $db->prepared_query("
      INSERT INTO news (UserID, Title, Body, Time)
      VALUES ('$user[ID]', '".db_string($_POST['title'])."', '".db_string($_POST['body'])."', NOW())");

    $cache->delete_value('news_latest_id');
    $cache->delete_value('news_latest_title');
    $cache->delete_value('news');

    Http::redirect("index.php");
    break;

  case 'tokens':
    include SERVER_ROOT.'/sections/tools/managers/tokens.php';
    break;

  case 'multiple_freeleech':
    include SERVER_ROOT.'/sections/tools/managers/multiple_freeleech.php';
    break;

  case 'ocelot':
    include SERVER_ROOT.'/sections/tools/managers/ocelot.php';
    break;

  case 'ocelot_info':
    include SERVER_ROOT.'/sections/tools/data/ocelot_info.php';
    break;

  case 'official_tags':
    include SERVER_ROOT.'/sections/tools/managers/official_tags.php';
    break;

  case 'freeleech':
    include SERVER_ROOT.'/sections/tools/managers/sitewide_freeleech.php';
    break;

  case 'tag_aliases':
    include SERVER_ROOT.'/sections/tools/managers/tag_aliases.php';
    break;

  case 'global_notification':
    include SERVER_ROOT.'/sections/tools/managers/global_notification.php';
    break;

  case 'take_global_notification':
    include SERVER_ROOT.'/sections/tools/managers/take_global_notification.php';
    break;

  case 'permissions':
    if (!check_perms('admin_manage_permissions')) {
        error(403);
    }

    if (!empty($_REQUEST['id'])) {
        $Val->SetFields('name', true, 'string', 'You did not enter a valid name for this permission set.');
        $Val->SetFields('level', true, 'number', 'You did not enter a valid level for this permission set.');
        $Val->SetFields('maxcollages', true, 'number', 'You did not enter a valid number of personal collages.');
        //$Val->SetFields('test', true, 'number', 'You did not enter a valid level for this permission set.');

        if (is_numeric($_REQUEST['id'])) {
            $db->prepared_query("
              SELECT p.ID, p.Name, p.Abbreviation, p.Level, p.Secondary, p.PermittedForums, p.Values, p.DisplayStaff, COUNT(u.ID)
              FROM permissions AS p
                LEFT JOIN users_main AS u ON u.PermissionID = p.ID
              WHERE p.ID = '".db_string($_REQUEST['id'])."'
              GROUP BY p.ID");
            list($ID, $Name, $Abbreviation, $Level, $Secondary, $Forums, $Values, $DisplayStaff, $UserCount) = $db->next_record(MYSQLI_NUM, array(6));

            if ($Level > $user['EffectiveClass'] || (isset($_REQUEST['level']) && $_REQUEST['level'] > $user['EffectiveClass'])) {
                error(403);
            }

            $Values = unserialize($Values);
        }

        if (!empty($_POST['submit'])) {
            $Err = $Val->ValidateForm($_POST);

            if (!is_numeric($_REQUEST['id'])) {
                $db->prepared_query("
                  SELECT ID
                  FROM permissions
                  WHERE Level = '".db_string($_REQUEST['level'])."'");
                list($DupeCheck)=$db->next_record();

                if ($DupeCheck) {
                    $Err = 'There is already a permission class with that level.';
                }
            }

            $Values = [];
            foreach ($_REQUEST as $Key => $Perms) {
                if (substr($Key, 0, 5) === 'perm_') {
                    $Values[substr($Key, 5)] = (int)$Perms;
                }
            }

            $Name = $_REQUEST['name'];
            $Level = $_REQUEST['level'];
            $Abbreviation = $_REQUEST['abbreviation'];
            $Secondary = empty($_REQUEST['secondary']) ? 0 : 1;
            $Forums = $_REQUEST['forums'];
            $DisplayStaff = isset($_REQUEST['displaystaff']) ? $_REQUEST['displaystaff']: 0;
            $Values['MaxCollages'] = $_REQUEST['maxcollages'];

            if (!$Err) {
                if (!is_numeric($_REQUEST['id'])) {
                    $db->prepared_query("
                      INSERT INTO permissions (Level, Name, Abbreviation, Secondary, PermittedForums, `Values`, DisplayStaff)
                      VALUES ('".db_string($Level)."',
                        '".db_string($Name)."',
                        '".db_string($Abbreviation)."',
                        $Secondary,
                        '".db_string($Forums)."',
                        '".db_string(serialize($Values))."',
                        '".db_string($DisplayStaff)."')");
                } else {
                    $db->prepared_query("
                      UPDATE permissions
                      SET Level = '".db_string($Level)."',
                        Name = '".db_string($Name)."',
                        Abbreviation = '".db_string($Abbreviation)."',
                        Secondary = $Secondary,
                        PermittedForums = '".db_string($Forums)."',
                        `Values` = '".db_string(serialize($Values))."',
                        DisplayStaff = '".db_string($DisplayStaff)."'
                      WHERE ID = '".db_string($_REQUEST['id'])."'");

                    $cache->delete_value('perm_'.$_REQUEST['id']);
                    if ($Secondary) {
                        $db->prepared_query("
                          SELECT DISTINCT UserID
                          FROM users_levels
                          WHERE PermissionID = ".db_string($_REQUEST['id']));

                        while (list($UserID) = $db->next_record()) {
                            $cache->delete_value("user_info_heavy_$UserID");
                        }
                    }
                }
                $cache->delete_value('classes');
            } else {
                error($Err);
            }
        }

        include SERVER_ROOT.'/sections/tools/managers/permissions_alter.php';
    } else {
        if (!empty($_REQUEST['removeid'])) {
            $db->prepared_query("
              DELETE FROM permissions
              WHERE ID = '".db_string($_REQUEST['removeid'])."'");

            $db->prepared_query("
              SELECT UserID
              FROM users_levels
              WHERE PermissionID = '".db_string($_REQUEST['removeid'])."'");

            while (list($UserID) = $db->next_record()) {
                $cache->delete_value("user_info_$UserID");
                $cache->delete_value("user_info_heavy_$UserID");
            }
            $db->prepared_query("
              DELETE FROM users_levels
              WHERE PermissionID = '".db_string($_REQUEST['removeid'])."'");

            $db->prepared_query("
              SELECT ID
              FROM users_main
              WHERE PermissionID = '".db_string($_REQUEST['removeid'])."'");

            while (list($UserID) = $db->next_record()) {
                $cache->delete_value("user_info_$UserID");
                $cache->delete_value("user_info_heavy_$UserID");
            }

            $db->prepared_query("
              UPDATE users_main
              SET PermissionID = '".USER."'
              WHERE PermissionID = '".db_string($_REQUEST['removeid'])."'");

            $cache->delete_value('classes');
        }

        include SERVER_ROOT.'/sections/tools/managers/permissions_list.php';
    }
    break;

  case 'ip_ban':
    // todo: Clean up DB table ip_bans
    include SERVER_ROOT.'/sections/tools/managers/bans.php';
    break;

  case 'quick_ban':
    include SERVER_ROOT.'/sections/tools/misc/quick_ban.php';
    break;

  // Data
  case 'registration_log':
    include SERVER_ROOT.'/sections/tools/data/registration_log.php';
    break;

  case 'upscale_pool':
    include SERVER_ROOT.'/sections/tools/data/upscale_pool.php';
    break;

  case 'invite_pool':
    include SERVER_ROOT.'/sections/tools/data/invite_pool.php';
    break;

  case 'service_stats':
    include SERVER_ROOT.'/sections/tools/development/service_stats.php';
    break;

  case 'database_specifics':
    include SERVER_ROOT.'/sections/tools/data/database_specifics.php';
    break;

  case 'special_users':
    include SERVER_ROOT.'/sections/tools/data/special_users.php';
    break;
  // END Data

  // Misc
  case 'clear_cache':
    include SERVER_ROOT.'/sections/tools/development/clear_cache.php';
    break;

  case 'create_user':
    include SERVER_ROOT.'/sections/tools/misc/create_user.php';
    break;

  case 'manipulate_tree':
    include SERVER_ROOT.'/sections/tools/misc/manipulate_tree.php';
    break;

  case 'misc_values':
    include SERVER_ROOT.'/sections/tools/development/misc_values.php';
    break;

  case 'recommendations':
    include SERVER_ROOT.'/sections/tools/misc/recommendations.php';
    break;

  case 'database_key':
    include SERVER_ROOT.'/sections/tools/misc/database_key.php';
    break;

  case 'rerender_gallery':
    include SERVER_ROOT.'/sections/tools/development/rerender_gallery.php';
    break;

  case 'mass_pm':
    include SERVER_ROOT.'/sections/tools/managers/mass_pm.php';
    break;

  case 'take_mass_pm':
    include SERVER_ROOT.'/sections/tools/managers/take_mass_pm.php';
    break;
    
  default:
    include SERVER_ROOT.'/sections/tools/tools.php';
}
