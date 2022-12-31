<?php

switch ($_GET['action']) {
  case 'notify_clear':
    $db->query("DELETE FROM users_notify_torrents WHERE UserID = '$user[ID]' AND UnRead = '0'");
    $cache->delete_value('notifications_new_'.$user['ID']);
    Http::redirect("torrents.php?action=notify");
    break;

  case 'notify_clear_item':
  case 'notify_clearitem':
    if (!isset($_GET['torrentid']) || !is_number($_GET['torrentid'])) {
        error(0);
    }
    $db->query("DELETE FROM users_notify_torrents WHERE UserID = '$user[ID]' AND TorrentID = '$_GET[torrentid]'");
    $cache->delete_value('notifications_new_'.$user['ID']);
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
    $db->query("DELETE FROM users_notify_torrents WHERE UserID = $user[ID] AND TorrentID IN ($_GET[torrentids])");
    $cache->delete_value('notifications_new_'.$user['ID']);
    break;

  case 'notify_clear_filter':
  case 'notify_cleargroup':
    if (!isset($_GET['filterid']) || !is_number($_GET['filterid'])) {
        error(0);
    }
    $db->query("DELETE FROM users_notify_torrents WHERE UserID = '$user[ID]' AND FilterID = '$_GET[filterid]' AND UnRead = '0'");
    $cache->delete_value('notifications_new_'.$user['ID']);
    Http::redirect("torrents.php?action=notify");
    break;

  case 'notify_catchup':
    $db->query("UPDATE users_notify_torrents SET UnRead = '0' WHERE UserID=$user[ID]");
    if ($db->affected_rows()) {
        $cache->delete_value('notifications_new_'.$user['ID']);
    }
    Http::redirect("torrents.php?action=notify");
    break;

  case 'notify_catchup_filter':
    if (!isset($_GET['filterid']) || !is_number($_GET['filterid'])) {
        error(0);
    }
    $db->query("UPDATE users_notify_torrents SET UnRead='0' WHERE UserID = $user[ID] AND FilterID = $_GET[filterid]");
    if ($db->affected_rows()) {
        $cache->delete_value('notifications_new_'.$user['ID']);
    }
    Http::redirect("torrents.php?action=notify");
    break;
  default:
    error(0);
}
