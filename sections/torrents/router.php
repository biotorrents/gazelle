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


$ENV = ENV::go();

// Function used for pagination of peer/snatch/download lists on details.php
function js_pages($Action, $TorrentID, $NumResults, $CurrentPage)
{
    $NumPages = ceil($NumResults / 100);
    $PageLinks = [];

    for ($i = 1; $i <= $NumPages; $i++) {
        if ($i === $CurrentPage) {
            $PageLinks[] = $i;
        } else {
            $PageLinks[] = "<a href='#' onclick='$Action($TorrentID, $i)'>$i</a>";
        }
    }
  
    return implode(' | ', $PageLinks);
}


/**
 * Main switchboard
 */
if (!empty($_REQUEST['action'])) {
    switch ($_REQUEST['action']) {
        case 'edit':
            enforce_login();
            require_once "$ENV->SERVER_ROOT/sections/torrents/edit.php";
            break;
        
        case 'editgroup':
            enforce_login();
            require_once "$ENV->SERVER_ROOT/sections/torrents/editgroup.php";
            break;
            
        case 'editgroupid':
            enforce_login();
            require_once "$ENV->SERVER_ROOT/sections/torrents/editgroupid.php";
            break;
            
        case 'changecategory':
            enforce_login();
            require_once "$ENV->SERVER_ROOT/sections/torrents/takechangecategory.php";
            break;
            
        case 'grouplog':
            enforce_login();
            require_once "$ENV->SERVER_ROOT/sections/torrents/grouplog.php";
            break;
            
        case 'takeedit':
            enforce_login();
            require_once "$ENV->SERVER_ROOT/sections/torrents/takeedit.php";
            break;
            
        case 'newgroup':
            enforce_login();
            require_once "$ENV->SERVER_ROOT/sections/torrents/takenewgroup.php";
            break;
            
        case 'peerlist':
            enforce_login();
            require_once "$ENV->SERVER_ROOT/sections/torrents/peerlist.php";
            break;
            
        case 'snatchlist':
            enforce_login();
            require_once "$ENV->SERVER_ROOT/sections/torrents/snatchlist.php";
            break;
            
        case 'downloadlist':
            enforce_login();
            require_once "$ENV->SERVER_ROOT/sections/torrents/downloadlist.php";
            break;
            
        case 'redownload':
            enforce_login();
            require_once "$ENV->SERVER_ROOT/sections/torrents/redownload.php";
            break;
            
        case 'revert':
        case 'takegroupedit':
            enforce_login();
            require_once "$ENV->SERVER_ROOT/sections/torrents/takegroupedit.php";
            break;
            
        case 'screenshotedit':
            enforce_login();
            require_once "$ENV->SERVER_ROOT/sections/torrents/screenshotedit.php";
            break;
            
        case 'nonwikiedit':
            enforce_login();
            require_once "$ENV->SERVER_ROOT/sections/torrents/nonwikiedit.php";
            break;
            
        case 'rename':
            enforce_login();
            require_once "$ENV->SERVER_ROOT/sections/torrents/rename.php";
            break;
            
        case 'merge':
            enforce_login();
            require_once "$ENV->SERVER_ROOT/sections/torrents/merge.php";
            break;
            
        case 'add_alias':
            enforce_login();
            require_once "$ENV->SERVER_ROOT/sections/torrents/add_alias.php";
            break;
            
        case 'delete_alias':
            enforce_login();
            authorize();
            require_once "$ENV->SERVER_ROOT/sections/torrents/delete_alias.php";
            break;
            
        case 'history':
            enforce_login();
            require_once "$ENV->SERVER_ROOT/sections/torrents/history.php";
            break;
            
        case 'delete':
            enforce_login();
            require_once "$ENV->SERVER_ROOT/sections/torrents/delete.php";
            break;
            
        case 'takedelete':
            enforce_login();
            require_once "$ENV->SERVER_ROOT/sections/torrents/takedelete.php";
            break;
            
        case 'masspm':
            enforce_login();
            require_once "$ENV->SERVER_ROOT/sections/torrents/masspm.php";
            break;
            
        case 'reseed':
            enforce_login();
            require_once "$ENV->SERVER_ROOT/sections/torrents/reseed.php";
            break;
            
        case 'takemasspm':
            enforce_login();
            require_once "$ENV->SERVER_ROOT/sections/torrents/takemasspm.php";
            break;
            
        case 'add_tag':
            enforce_login();
            require_once "$ENV->SERVER_ROOT/sections/torrents/add_tag.php";
            break;
            
        case 'delete_tag':
            enforce_login();
            authorize();
            require_once "$ENV->SERVER_ROOT/sections/torrents/delete_tag.php";
            break;
            
        case 'notify':
            enforce_login();
            require_once "$ENV->SERVER_ROOT/sections/torrents/notify.php";
            break;
            
        case 'manage_artists':
            enforce_login();
            require_once "$ENV->SERVER_ROOT/sections/torrents/manage_artists.php";
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
            require_once "$ENV->SERVER_ROOT/sections/torrents/notify_actions.php";
            break;
            
        case 'download':
            require_once "$ENV->SERVER_ROOT/sections/torrents/download.php";
            break;
            
        case 'regen_filelist':
            if (check_perms('users_mod') && !empty($_GET['torrentid']) && is_number($_GET['torrentid'])) {
                Torrents::regenerate_filelist($_GET['torrentid']);
                header('Location: torrents.php?torrentid='.$_GET['torrentid']);
                error();
            } else {
                error(403);
            }
            break;
            
        case 'fix_group':
            if ((check_perms('users_mod') || check_perms('torrents_fix_ghosts'))
              && !empty($_GET['groupid'])
              && is_number($_GET['groupid'])
                ) {
                authorize();

                $db->prepared_query("
                SELECT
                  COUNT(`ID`)
                FROM
                  `torrents`
                WHERE
                  `GroupID` = '$_GET[groupid]'
                ");
                list($Count) = $db->next_record();
                
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
            break;
            
        case 'add_cover_art':
            require_once "$ENV->SERVER_ROOT/sections/torrents/add_cover_art.php";
            break;
            
        case 'remove_cover_art':
            require_once "$ENV->SERVER_ROOT/sections/torrents/remove_cover_art.php";
            break;
            
        case 'autocomplete_tags':
            require_once "$ENV->SERVER_ROOT/sections/torrents/autocomplete_tags.php";
            break;
            
        default:
            enforce_login();
            
            if (!empty($_GET['id'])) {
                require_once "$ENV->SERVER_ROOT/sections/torrents/details.php";
            } elseif (isset($_GET['torrentid']) && is_number($_GET['torrentid'])) {
                $db->query("
                SELECT
                  `GroupID`
                FROM
                  `torrents`
                WHERE
                  `ID` = '$_GET[torrentid]'
                ");
                list($GroupID) = $db->next_record();
                
                if ($GroupID) {
                    header("Location: torrents.php?id=$GroupID&torrentid=".$_GET['torrentid']);
                }
            } else {
                require_once "$ENV->SERVER_ROOT/sections/torrents/browse.php";
            }
            break;
    } # switch
}

# If $_REQUEST['action'] is empty
else {
    enforce_login();

    if (!empty($_GET['id'])) {
        require_once "$ENV->SERVER_ROOT/sections/torrents/details.php";
    } elseif (isset($_GET['torrentid']) && is_number($_GET['torrentid'])) {
        $db->query("
        SELECT
          `GroupID`
        FROM
          `torrents`
        WHERE
          `ID` = '$_GET[torrentid]'
        ");
        list($GroupID) = $db->next_record();

        if ($GroupID) {
            header("Location: torrents.php?id=$GroupID&torrentid=".$_GET['torrentid'].'#torrent'.$_GET['torrentid']);
        } else {
            header("Location: log.php?search=Torrent+$_GET[torrentid]");
        }
    } elseif (!empty($_GET['type'])) {
        require_once "$ENV->SERVER_ROOT/sections/torrents/user.php";
    } elseif (!empty($_GET['groupname']) && !empty($_GET['forward'])) {
        $db->prepared_query("
        SELECT
          `id`
        FROM
          `torrents_group`
        WHERE
          `title` LIKE '$_GET[groupname]'
        ");
        list($GroupID) = $db->next_record();

        if ($GroupID) {
            header("Location: torrents.php?id=$GroupID");
        } else {
            require_once "$ENV->SERVER_ROOT/sections/torrents/browse.php";
        }
    } else {
        require_once "$ENV->SERVER_ROOT/sections/torrents/browse.php";
    }
}