<?php

$app = Gazelle\App::go();

switch ($_GET['action']) {
    case 'notify_clear':
        $app->dbOld->query("DELETE FROM users_notify_torrents WHERE UserID = '{$app->user->core['id']}' AND UnRead = '0'");
        $app->cache->delete('notifications_new_' . $app->user->core['id']);
        Gazelle\Http::redirect("torrents.php?action=notify");
        break;

    case 'notify_clear_item':
    case 'notify_clearitem':
        if (!isset($_GET['torrentid']) || !is_numeric($_GET['torrentid'])) {
            error(0);
        }
        $app->dbOld->query("DELETE FROM users_notify_torrents WHERE UserID = '{$app->user->core['id']}' AND TorrentID = '$_GET[torrentid]'");
        $app->cache->delete('notifications_new_' . $app->user->core['id']);
        break;

    case 'notify_clear_items':
        if (!isset($_GET['torrentids'])) {
            error(0);
        }
        $TorrentIDs = explode(',', $_GET['torrentids']);
        foreach ($TorrentIDs as $TorrentID) {
            if (!is_numeric($TorrentID)) {
                error(0);
            }
        }
        $app->dbOld->query("DELETE FROM users_notify_torrents WHERE UserID = {$app->user->core['id']} AND TorrentID IN ($_GET[torrentids])");
        $app->cache->delete('notifications_new_' . $app->user->core['id']);
        break;

    case 'notify_clear_filter':
    case 'notify_cleargroup':
        if (!isset($_GET['filterid']) || !is_numeric($_GET['filterid'])) {
            error(0);
        }
        $app->dbOld->query("DELETE FROM users_notify_torrents WHERE UserID = '{$app->user->core['id']}' AND FilterID = '$_GET[filterid]' AND UnRead = '0'");
        $app->cache->delete('notifications_new_' . $app->user->core['id']);
        Gazelle\Http::redirect("torrents.php?action=notify");
        break;

    case 'notify_catchup':
        $app->dbOld->query("UPDATE users_notify_torrents SET UnRead = '0' WHERE UserID={$app->user->core['id']}");
        if ($app->dbOld->affected_rows()) {
            $app->cache->delete('notifications_new_' . $app->user->core['id']);
        }
        Gazelle\Http::redirect("torrents.php?action=notify");
        break;

    case 'notify_catchup_filter':
        if (!isset($_GET['filterid']) || !is_numeric($_GET['filterid'])) {
            error(0);
        }
        $app->dbOld->query("UPDATE users_notify_torrents SET UnRead='0' WHERE UserID = {$app->user->core['id']} AND FilterID = $_GET[filterid]");
        if ($app->dbOld->affected_rows()) {
            $app->cache->delete('notifications_new_' . $app->user->core['id']);
        }
        Gazelle\Http::redirect("torrents.php?action=notify");
        break;
    default:
        error(0);
}
