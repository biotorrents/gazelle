<?php

declare(strict_types=1);

$app = Gazelle\App::go();

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

$ENV = Gazelle\ENV::go();
#!d(Gazelle\Http::request());exit;

if (isset($argv[1])) {
    $_REQUEST['action'] = $argv[1];
} else {
    if (empty($_REQUEST['action']) || $_REQUEST['action'] !== 'ocelot') {
        // If set, do not enforce login so we can set the encryption key w/o an account
        if (!$ENV->enablePublicEncryptionKey) {

        }
    }
}

# Error checking
if (!isset($_REQUEST['action'])) {
    require_once serverRoot . '/sections/tools/tools.php';
    #error('Need to set an "action" parameter in sections/tools/tools.php.');
}

$Val = new Validate();
$Feed = new Feed();

# Finally
switch ($_REQUEST['action']) {
    // Managers
    case 'forum':
        require_once serverRoot . '/sections/tools/managers/forum_list.php';
        break;

    case 'forum_alter':
        require_once serverRoot . '/sections/tools/managers/forum_alter.php';
        break;

    case 'whitelist':
        require_once serverRoot . '/sections/tools/managers/whitelist_list.php';
        break;

    case 'whitelist_alter':
        require_once serverRoot . '/sections/tools/managers/whitelist_alter.php';
        break;

    case 'enable_requests':
        require_once serverRoot . '/sections/tools/managers/enable_requests.php';
        break;

    case 'ajax_take_enable_request':
        if (FEATURE_EMAIL_REENABLE) {
            require_once serverRoot . '/sections/tools/managers/ajax_take_enable_request.php';
        } else {
            // Prevent post requests to the ajax page
            Gazelle\Http::redirect("tools.php");
            error();
        }
        break;

    case 'login_watch':
        require_once serverRoot . '/sections/tools/managers/login_watch.php';
        break;

    case 'email_blacklist':
        require_once serverRoot . '/sections/tools/managers/email_blacklist.php';
        break;

    case 'email_blacklist_alter':
        require_once serverRoot . '/sections/tools/managers/email_blacklist_alter.php';
        break;

    case 'email_blacklist_search':
        require_once serverRoot . '/sections/tools/managers/email_blacklist_search.php';
        break;

    case 'news':
        require_once serverRoot . '/sections/tools/managers/news.php';
        break;

    case 'edit_tags':
        require_once serverRoot . '/sections/tools/misc/tags.php';
        break;

    case 'tokens':
        require_once serverRoot . '/sections/tools/managers/tokens.php';
        break;

    case 'multiple_freeleech':
        require_once serverRoot . '/sections/tools/managers/multiple_freeleech.php';
        break;

    case 'ocelot':
        require_once serverRoot . '/sections/tools/managers/ocelot.php';
        break;

    case 'ocelot_info':
        require_once serverRoot . '/sections/tools/data/ocelot_info.php';
        break;

    case 'official_tags':
        require_once serverRoot . '/sections/tools/managers/official_tags.php';
        break;

    case 'freeleech':
        require_once serverRoot . '/sections/tools/managers/sitewide_freeleech.php';
        break;

    case 'tag_aliases':
        require_once serverRoot . '/sections/tools/managers/tag_aliases.php';
        break;

    case 'global_notification':
        require_once serverRoot . '/sections/tools/managers/global_notification.php';
        break;

    case 'take_global_notification':
        require_once serverRoot . '/sections/tools/managers/take_global_notification.php';
        break;

    case 'newRoles':
        require_once serverRoot . '/sections/tools/rolesList.php';
        break;

    case 'permissions':
        if (!empty($_REQUEST['id'])) {
            $Val->SetFields('name', true, 'string', 'You did not enter a valid name for this permission set.');
            $Val->SetFields('level', true, 'number', 'You did not enter a valid level for this permission set.');
            $Val->SetFields('maxcollages', true, 'number', 'You did not enter a valid number of personal collages.');
            //$Val->SetFields('test', true, 'number', 'You did not enter a valid level for this permission set.');

            if (is_numeric($_REQUEST['id'])) {
                $app->dbOld->prepared_query("
              SELECT p.ID, p.Name, p.Abbreviation, p.Level, p.Secondary, p.PermittedForums, p.Values, p.DisplayStaff, COUNT(u.ID)
              FROM permissions AS p
                LEFT JOIN users_main AS u ON u.PermissionID = p.ID
              WHERE p.ID = '" . db_string($_REQUEST['id']) . "'
              GROUP BY p.ID");
                list($ID, $Name, $Abbreviation, $Level, $Secondary, $Forums, $Values, $DisplayStaff, $UserCount) = $app->dbOld->next_record(MYSQLI_NUM, array(6));

                if ($Level > $app->user->extra['EffectiveClass'] || (isset($_REQUEST['level']) && $_REQUEST['level'] > $app->user->extra['EffectiveClass'])) {
                    #error(403);
                }

                $Values = unserialize($Values);
            }

            if (!empty($_POST['submit'])) {
                $Err = $Val->ValidateForm($_POST);

                if (!is_numeric($_REQUEST['id'])) {
                    $app->dbOld->prepared_query("
                  SELECT ID
                  FROM permissions
                  WHERE Level = '" . db_string($_REQUEST['level']) . "'");
                    list($DupeCheck) = $app->dbOld->next_record();

                    if ($DupeCheck) {
                        $Err = 'There is already a permission class with that level.';
                    }
                }

                $Values = [];
                foreach ($_REQUEST as $Key => $Perms) {
                    if (substr($Key, 0, 5) === 'perm_') {
                        $Values[substr($Key, 5)] = (int) $Perms;
                    }
                }

                $Name = $_REQUEST['name'];
                $Level = $_REQUEST['level'];
                $Abbreviation = $_REQUEST['abbreviation'];
                $Secondary = empty($_REQUEST['secondary']) ? 0 : 1;
                $Forums = $_REQUEST['forums'];
                $DisplayStaff = isset($_REQUEST['displaystaff']) ? $_REQUEST['displaystaff'] : 0;
                $Values['MaxCollages'] = $_REQUEST['maxcollages'];

                if (!$Err) {
                    if (!is_numeric($_REQUEST['id'])) {
                        $app->dbOld->prepared_query("
                      INSERT INTO permissions (Level, Name, Abbreviation, Secondary, PermittedForums, `Values`, DisplayStaff)
                      VALUES ('" . db_string($Level) . "',
                        '" . db_string($Name) . "',
                        '" . db_string($Abbreviation) . "',
                        $Secondary,
                        '" . db_string($Forums) . "',
                        '" . db_string(serialize($Values)) . "',
                        '" . db_string($DisplayStaff) . "')");
                    } else {
                        $app->dbOld->prepared_query("
                      UPDATE permissions
                      SET Level = '" . db_string($Level) . "',
                        Name = '" . db_string($Name) . "',
                        Abbreviation = '" . db_string($Abbreviation) . "',
                        Secondary = $Secondary,
                        PermittedForums = '" . db_string($Forums) . "',
                        `Values` = '" . db_string(serialize($Values)) . "',
                        DisplayStaff = '" . db_string($DisplayStaff) . "'
                      WHERE ID = '" . db_string($_REQUEST['id']) . "'");

                        $app->cache->delete('perm_' . $_REQUEST['id']);
                        if ($Secondary) {
                            $app->dbOld->prepared_query("
                          SELECT DISTINCT UserID
                          FROM users_levels
                          WHERE PermissionID = " . db_string($_REQUEST['id']));

                            while (list($UserID) = $app->dbOld->next_record()) {
                                $app->cache->delete("user_info_heavy_$UserID");
                            }
                        }
                    }
                    $app->cache->delete('classes');
                } else {
                    error($Err);
                }
            }

            require_once serverRoot . '/sections/tools/managers/permissions_alter.php';
        } else {
            if (!empty($_REQUEST['removeid'])) {
                $app->dbOld->prepared_query("
              DELETE FROM permissions
              WHERE ID = '" . db_string($_REQUEST['removeid']) . "'");

                $app->dbOld->prepared_query("
              SELECT UserID
              FROM users_levels
              WHERE PermissionID = '" . db_string($_REQUEST['removeid']) . "'");

                while (list($UserID) = $app->dbOld->next_record()) {
                    $app->cache->delete("user_info_$UserID");
                    $app->cache->delete("user_info_heavy_$UserID");
                }
                $app->dbOld->prepared_query("
              DELETE FROM users_levels
              WHERE PermissionID = '" . db_string($_REQUEST['removeid']) . "'");

                $app->dbOld->prepared_query("
              SELECT ID
              FROM users_main
              WHERE PermissionID = '" . db_string($_REQUEST['removeid']) . "'");

                while (list($UserID) = $app->dbOld->next_record()) {
                    $app->cache->delete("user_info_$UserID");
                    $app->cache->delete("user_info_heavy_$UserID");
                }

                $app->dbOld->prepared_query("
              UPDATE users_main
              SET PermissionID = '" . USER . "'
              WHERE PermissionID = '" . db_string($_REQUEST['removeid']) . "'");

                $app->cache->delete('classes');
            }

            require_once serverRoot . '/sections/tools/managers/permissions_list.php';
        }
        break;

    case 'ip_ban':
        // todo: Clean up DB table ip_bans
        require_once serverRoot . '/sections/tools/managers/bans.php';
        break;

    case 'quick_ban':
        require_once serverRoot . '/sections/tools/misc/quick_ban.php';
        break;

        // Data
    case 'registration_log':
        require_once serverRoot . '/sections/tools/data/registration_log.php';
        break;

    case 'upscale_pool':
        require_once serverRoot . '/sections/tools/data/upscale_pool.php';
        break;

    case 'invite_pool':
        require_once serverRoot . '/sections/tools/data/invite_pool.php';
        break;

    case 'service_stats':
        require_once serverRoot . '/sections/tools/development/service_stats.php';
        break;
        // END Data

        // Misc
    case 'manipulate_tree':
        require_once serverRoot . '/sections/tools/misc/manipulate_tree.php';
        break;

    case 'misc_values':
        require_once serverRoot . '/sections/tools/development/misc_values.php';
        break;

    case 'database_key':
        require_once serverRoot . '/sections/tools/misc/database_key.php';
        break;

    case 'mass_pm':
        require_once serverRoot . '/sections/tools/managers/mass_pm.php';
        break;

    case 'take_mass_pm':
        require_once serverRoot . '/sections/tools/managers/take_mass_pm.php';
        break;

    default:
        require_once serverRoot . '/sections/tools/tools.php';
}
