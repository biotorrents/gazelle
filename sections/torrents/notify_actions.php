<?php

$app = App::go();

switch ($_GET['action']) {
  case 'notify_clear':
    $app->dbOld->query("DELETE FROM users_notify_torrents WHERE UserID = '$user[ID]' AND UnRead = '0'");
    $app->cacheOld->delete_value('notifications_new_'.$user['ID']);
    Http::redirect("torrents.php?action=notify");
    break;

  case 'notify_clear_item':
  case 'notify_clearitem':
    if (!isset($_GET['torrentid']) || !is_number($_GET['torrentid'])) {
        error(0);
    }
    $app->dbOld->query("DELETE FROM users_notify_torrents WHERE UserID = '$user[ID]' AND TorrentID = '$_GET[torrentid]'");
    $app->cacheOld->delete_value('notifications_new_'.$user['ID']);
    break;

  case 'notify_clear_items':
    if (!isset($_GET['torrentids'])) {
        error(0);
    }
    $TorrentIDs = explode(',', $_GET['torrentids']);
    foreach ($TorrentIDs as $TorrentID) {
        if (!is_number($TorrentID)) {
            error(0);
        }
    }
    $app->dbOld->query("DELETE FROM users_notify_torrents WHERE UserID = $user[ID] AND TorrentID IN ($_GET[torrentids])");
    $app->cacheOld->delete_value('notifications_new_'.$user['ID']);
    break;

  case 'notify_clear_filter':
  case 'notify_cleargroup':
    if (!isset($_GET['filterid']) || !is_number($_GET['filterid'])) {
        error(0);
    }
    $app->dbOld->query("DELETE FROM users_notify_torrents WHERE UserID = '$user[ID]' AND FilterID = '$_GET[filterid]' AND UnRead = '0'");
    $app->cacheOld->delete_value('notifications_new_'.$user['ID']);
    Http::redirect("torrents.php?action=notify");
    break;

  case 'notify_catchup':
    $app->dbOld->query("UPDATE users_notify_torrents SET UnRead = '0' WHERE UserID=$user[ID]");
    if ($app->dbOld->affected_rows()) {
        $app->cacheOld->delete_value('notifications_new_'.$user['ID']);
    }
    Http::redirect("torrents.php?action=notify");
    break;

  case 'notify_catchup_filter':
    if (!isset($_GET['filterid']) || !is_number($_GET['filterid'])) {
        error(0);
    }
    $app->dbOld->query("UPDATE users_notify_torrents SET UnRead='0' WHERE UserID = $user[ID] AND FilterID = $_GET[filterid]");
    if ($app->dbOld->affected_rows()) {
        $app->cacheOld->delete_value('notifications_new_'.$user['ID']);
    }
    Http::redirect("torrents.php?action=notify");
    break;
  default:
    error(0);
}
