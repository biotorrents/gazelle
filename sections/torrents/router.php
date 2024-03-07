<?php

declare(strict_types=1);

$app = \Gazelle\App::go();


/**
 * torrents
 */

/*
# multi ( row ( single ) )
Flight::route("/torrents(/@group(/@torrent))", function ($group, $torrent) {
   $app = \Gazelle\App::go();

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


$ENV = \Gazelle\ENV::go();

if (empty($_REQUEST['action']) && empty($_REQUEST["id"]) && empty($_REQUEST["type"])) {
    require_once "$ENV->serverRoot/sections/torrents/browse.php";
}

if (!empty($_REQUEST["id"])) {
    require_once "$ENV->serverRoot/sections/torrents/details.php";
}

if (!empty($_GET['type'])) {
    require_once "$ENV->serverRoot/sections/torrents/user.php";
}

if (!empty($_REQUEST['action'])) {
    switch ($_REQUEST['action']) {
        case 'edit':

            require_once "$ENV->serverRoot/sections/torrents/edit.php";
            break;

        case 'editgroup':

            require_once "$ENV->serverRoot/sections/torrents/editgroup.php";
            break;

        case 'editgroupid':

            require_once "$ENV->serverRoot/sections/torrents/editgroupid.php";
            break;

        case 'changecategory':

            require_once "$ENV->serverRoot/sections/torrents/takechangecategory.php";
            break;

        case 'grouplog':

            require_once "$ENV->serverRoot/sections/torrents/grouplog.php";
            break;

        case 'takeedit':

            require_once "$ENV->serverRoot/sections/torrents/takeedit.php";
            break;

        case 'newgroup':

            require_once "$ENV->serverRoot/sections/torrents/takenewgroup.php";
            break;

        case 'peerlist':

            require_once "$ENV->serverRoot/sections/torrents/peerlist.php";
            break;

        case 'snatchlist':

            require_once "$ENV->serverRoot/sections/torrents/snatchlist.php";
            break;

        case 'downloadlist':

            require_once "$ENV->serverRoot/sections/torrents/downloadlist.php";
            break;

        case 'redownload':

            require_once "$ENV->serverRoot/sections/torrents/redownload.php";
            break;

        case 'revert':
        case 'takegroupedit':

            require_once "$ENV->serverRoot/sections/torrents/takegroupedit.php";
            break;

        case 'screenshotedit':

            require_once "$ENV->serverRoot/sections/torrents/screenshotedit.php";
            break;

        case 'nonwikiedit':

            require_once "$ENV->serverRoot/sections/torrents/nonwikiedit.php";
            break;

        case 'rename':

            require_once "$ENV->serverRoot/sections/torrents/rename.php";
            break;

        case 'merge':

            require_once "$ENV->serverRoot/sections/torrents/merge.php";
            break;

        case 'add_alias':

            require_once "$ENV->serverRoot/sections/torrents/add_alias.php";
            break;

        case 'delete_alias':


            require_once "$ENV->serverRoot/sections/torrents/delete_alias.php";
            break;

        case 'history':

            require_once "$ENV->serverRoot/sections/torrents/history.php";
            break;

        case 'delete':

            require_once "$ENV->serverRoot/sections/torrents/delete.php";
            break;

        case 'takedelete':

            require_once "$ENV->serverRoot/sections/torrents/takedelete.php";
            break;

        case 'masspm':

            require_once "$ENV->serverRoot/sections/torrents/masspm.php";
            break;

        case 'reseed':

            require_once "$ENV->serverRoot/sections/torrents/reseed.php";
            break;

        case 'takemasspm':

            require_once "$ENV->serverRoot/sections/torrents/takemasspm.php";
            break;

        case 'notify':

            require_once "$ENV->serverRoot/sections/torrents/notify.php";
            break;

        case 'manage_artists':

            require_once "$ENV->serverRoot/sections/torrents/manage_artists.php";
            break;

        case 'notify_clear':
        case 'notify_clear_item':
        case 'notify_clear_items':
        case 'notify_clearitem':
        case 'notify_clear_filter':
        case 'notify_cleargroup':
        case 'notify_catchup':
        case 'notify_catchup_filter':


            require_once "$ENV->serverRoot/sections/torrents/notify_actions.php";
            break;

        case 'download':
            require_once "$ENV->serverRoot/sections/torrents/download.php";
            break;

        case 'regen_filelist':
            if ($app->user->can(["admin" => "moderateUsers"]) && !empty($_GET['torrentid']) && is_numeric($_GET['torrentid'])) {
                Torrents::regenerate_filelist($_GET['torrentid']);
                header('Location: torrents.php?torrentid='.$_GET['torrentid']);
                error();
            } else {
                error(403);
            }
            break;

        case 'fix_group':
            if (($app->user->can(["admin" => "moderateUsers"]) || check_perms('torrents_fix_ghosts'))
              && !empty($_GET['groupid'])
              && is_numeric($_GET['groupid'])
            ) {


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

                if (!empty($_GET['artistid']) && is_numeric($_GET['artistid'])) {
                    header('Location: artist.php?id='.$_GET['artistid']);
                } else {
                    header('Location: torrents.php?id='.$_GET['groupid']);
                }
            } else {
                error(403);
            }
            break;

        case 'add_cover_art':
            require_once "$ENV->serverRoot/sections/torrents/add_cover_art.php";
            break;

        case 'remove_cover_art':
            require_once "$ENV->serverRoot/sections/torrents/remove_cover_art.php";
            break;

        default:


            if (!empty($_GET['id'])) {
                require_once "$ENV->serverRoot/sections/torrents/details.php";
            } elseif (isset($_GET['torrentid']) && is_numeric($_GET['torrentid'])) {
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
            break;
    } # switch
}
