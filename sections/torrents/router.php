<?php

declare(strict_types=1);

$app = App::go();


/**
 * torrents
 */

 /*
# multi ( row ( single ) )
Flight::route("/torrents(/@group(/@torrent))", function ($group, $torrent) {
    $app = App::go();

    # browse
    if (!$group) {
        require_once "{$app->env->serverRoot}/sections/torrents/browse.php";
    }

    # group
    else {
        require_once "{$app->env->serverRoot}/sections/torrents/details.php";
    }
});
*/


$ENV = ENV::go();


    #enforce_login();

    if (!empty($_GET['id'])) {
        require_once "$ENV->serverRoot/sections/torrents/details.php";
    } elseif (isset($_GET['torrentid']) && is_number($_GET['torrentid'])) {
        $app->dbOld->query("
        SELECT
          `GroupID`
        FROM
          `torrents`
        WHERE
          `ID` = '$_GET[torrentid]'
        ");
        list($GroupID) = $app->dbOld->next_record();

        if ($GroupID) {
            header("Location: torrents.php?id=$GroupID&torrentid=".$_GET['torrentid'].'#torrent'.$_GET['torrentid']);
        } else {
            Http::redirect("log.php?search=Torrent+$_GET[torrentid]");
        }
    } elseif (!empty($_GET['type'])) {
        require_once "$ENV->serverRoot/sections/torrents/user.php";
    } elseif (!empty($_GET['groupname']) && !empty($_GET['forward'])) {
        $app->dbOld->prepared_query("
        SELECT
          `id`
        FROM
          `torrents_group`
        WHERE
          `title` LIKE '$_GET[groupname]'
        ");
        list($GroupID) = $app->dbOld->next_record();

        if ($GroupID) {
            Http::redirect("torrents.php?id=$GroupID");
        } else {
            require_once "$ENV->serverRoot/sections/torrents/browse.php";
        }
    } else {
        require_once "$ENV->serverRoot/sections/torrents/browse.php";
    }



if (!empty($_REQUEST['action'])) {
    switch ($_REQUEST['action']) {
        case 'edit':
            enforce_login();
            require_once "$ENV->serverRoot/sections/torrents/edit.php";
            exit;
            break;

        case 'editgroup':
            enforce_login();
            require_once "$ENV->serverRoot/sections/torrents/editgroup.php";
            exit;
            break;

        case 'editgroupid':
            enforce_login();
            require_once "$ENV->serverRoot/sections/torrents/editgroupid.php";
            exit;
            break;

        case 'changecategory':
            enforce_login();
            require_once "$ENV->serverRoot/sections/torrents/takechangecategory.php";
            exit;
            break;

        case 'grouplog':
            enforce_login();
            require_once "$ENV->serverRoot/sections/torrents/grouplog.php";
            exit;
            break;

        case 'takeedit':
            enforce_login();
            require_once "$ENV->serverRoot/sections/torrents/takeedit.php";
            exit;
            break;

        case 'newgroup':
            enforce_login();
            require_once "$ENV->serverRoot/sections/torrents/takenewgroup.php";
            exit;
            break;

        case 'peerlist':
            enforce_login();
            require_once "$ENV->serverRoot/sections/torrents/peerlist.php";
            exit;
            break;

        case 'snatchlist':
            enforce_login();
            require_once "$ENV->serverRoot/sections/torrents/snatchlist.php";
            exit;
            break;

        case 'downloadlist':
            enforce_login();
            require_once "$ENV->serverRoot/sections/torrents/downloadlist.php";
            exit;
            break;

        case 'redownload':
            enforce_login();
            require_once "$ENV->serverRoot/sections/torrents/redownload.php";
            exit;
            break;

        case 'revert':
        case 'takegroupedit':
            enforce_login();
            require_once "$ENV->serverRoot/sections/torrents/takegroupedit.php";
            exit;
            break;

        case 'screenshotedit':
            enforce_login();
            require_once "$ENV->serverRoot/sections/torrents/screenshotedit.php";
            exit;
            break;

        case 'nonwikiedit':
            enforce_login();
            require_once "$ENV->serverRoot/sections/torrents/nonwikiedit.php";
            exit;
            break;

        case 'rename':
            enforce_login();
            require_once "$ENV->serverRoot/sections/torrents/rename.php";
            exit;
            break;

        case 'merge':
            enforce_login();
            require_once "$ENV->serverRoot/sections/torrents/merge.php";
            exit;
            break;

        case 'add_alias':
            enforce_login();
            require_once "$ENV->serverRoot/sections/torrents/add_alias.php";
            exit;
            break;

        case 'delete_alias':
            enforce_login();
            authorize();
            require_once "$ENV->serverRoot/sections/torrents/delete_alias.php";
            exit;
            break;

        case 'history':
            enforce_login();
            require_once "$ENV->serverRoot/sections/torrents/history.php";
            exit;
            break;

        case 'delete':
            enforce_login();
            require_once "$ENV->serverRoot/sections/torrents/delete.php";
            exit;
            break;

        case 'takedelete':
            enforce_login();
            require_once "$ENV->serverRoot/sections/torrents/takedelete.php";
            exit;
            break;

        case 'masspm':
            enforce_login();
            require_once "$ENV->serverRoot/sections/torrents/masspm.php";
            exit;
            break;

        case 'reseed':
            enforce_login();
            require_once "$ENV->serverRoot/sections/torrents/reseed.php";
            exit;
            break;

        case 'takemasspm':
            enforce_login();
            require_once "$ENV->serverRoot/sections/torrents/takemasspm.php";
            exit;
            break;

        case 'add_tag':
            enforce_login();
            require_once "$ENV->serverRoot/sections/torrents/add_tag.php";
            exit;
            break;

        case 'delete_tag':
            enforce_login();
            authorize();
            require_once "$ENV->serverRoot/sections/torrents/delete_tag.php";
            exit;
            break;

        case 'notify':
            enforce_login();
            require_once "$ENV->serverRoot/sections/torrents/notify.php";
            exit;
            break;

        case 'manage_artists':
            enforce_login();
            require_once "$ENV->serverRoot/sections/torrents/manage_artists.php";
            exit;
            break;

        case 'notify_clear':
        case 'notify_clear_item':
        case 'notify_clear_items':
        case 'notify_clearitem':
        case 'notify_clear_filter':
        case 'notify_cleargroup':
        case 'notify_catchup':
        case 'notify_catchup_filter':
            enforce_login();
            authorize();
            require_once "$ENV->serverRoot/sections/torrents/notify_actions.php";
            exit;
            break;

        case 'download':
            require_once "$ENV->serverRoot/sections/torrents/download.php";
            exit;
            break;

        case 'regen_filelist':
            if (check_perms('users_mod') && !empty($_GET['torrentid']) && is_number($_GET['torrentid'])) {
                Torrents::regenerate_filelist($_GET['torrentid']);
                header('Location: torrents.php?torrentid='.$_GET['torrentid']);
                error();
            } else {
                error(403);
            }
            exit;
            break;

        case 'fix_group':
            if ((check_perms('users_mod') || check_perms('torrents_fix_ghosts'))
              && !empty($_GET['groupid'])
              && is_number($_GET['groupid'])
                ) {
                authorize();

                $app->dbOld->prepared_query("
                SELECT
                  COUNT(`ID`)
                FROM
                  `torrents`
                WHERE
                  `GroupID` = '$_GET[groupid]'
                ");
                list($Count) = $app->dbOld->next_record();

                if ($Count === 0) {
                    Torrents::delete_group($_GET['groupid']);
                }

                if (!empty($_GET['artistid']) && is_number($_GET['artistid'])) {
                    header('Location: artist.php?id='.$_GET['artistid']);
                } else {
                    header('Location: torrents.php?id='.$_GET['groupid']);
                }
            } else {
                error(403);
            }
            exit;
            break;

        case 'add_cover_art':
            require_once "$ENV->serverRoot/sections/torrents/add_cover_art.php";
            exit;
            break;

        case 'remove_cover_art':
            require_once "$ENV->serverRoot/sections/torrents/remove_cover_art.php";
            exit;
            break;

        case 'autocomplete_tags':
            require_once "$ENV->serverRoot/sections/torrents/autocomplete_tags.php";
            exit;
            break;

        default:
            enforce_login();

            if (!empty($_GET['id'])) {
                require_once "$ENV->serverRoot/sections/torrents/details.php";
            } elseif (isset($_GET['torrentid']) && is_number($_GET['torrentid'])) {
                $app->dbOld->query("
                SELECT
                  `GroupID`
                FROM
                  `torrents`
                WHERE
                  `ID` = '$_GET[torrentid]'
                ");
                list($GroupID) = $app->dbOld->next_record();

                if ($GroupID) {
                    header("Location: torrents.php?id=$GroupID&torrentid=".$_GET['torrentid']);
                }
            } else {
                require_once "$ENV->serverRoot/sections/torrents/browse.php";
            }
            exit;
            break;
    } # switch
}

# If $_REQUEST['action'] is empty
else {
    #enforce_login();

    if (!empty($_GET['id'])) {
        require_once "$ENV->serverRoot/sections/torrents/details.php";
    } elseif (isset($_GET['torrentid']) && is_number($_GET['torrentid'])) {
        $app->dbOld->query("
        SELECT
          `GroupID`
        FROM
          `torrents`
        WHERE
          `ID` = '$_GET[torrentid]'
        ");
        list($GroupID) = $app->dbOld->next_record();

        if ($GroupID) {
            header("Location: torrents.php?id=$GroupID&torrentid=".$_GET['torrentid'].'#torrent'.$_GET['torrentid']);
        } else {
            Http::redirect("log.php?search=Torrent+$_GET[torrentid]");
        }
    } elseif (!empty($_GET['type'])) {
        require_once "$ENV->serverRoot/sections/torrents/user.php";
    } elseif (!empty($_GET['groupname']) && !empty($_GET['forward'])) {
        $app->dbOld->prepared_query("
        SELECT
          `id`
        FROM
          `torrents_group`
        WHERE
          `title` LIKE '$_GET[groupname]'
        ");
        list($GroupID) = $app->dbOld->next_record();

        if ($GroupID) {
            Http::redirect("torrents.php?id=$GroupID");
        } else {
            require_once "$ENV->serverRoot/sections/torrents/browse.php";
        }
    } else {
        require_once "$ENV->serverRoot/sections/torrents/browse.php";
    }
}
