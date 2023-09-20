<?php

#declare(strict_types=1);

$app = \Gazelle\App::go();

$ENV = ENV::go();

$Key = $_REQUEST['key'];
$Type = $_REQUEST['type'];

if (($Key !== $ENV->private('trackerSecret')) || $_SERVER['REMOTE_ADDR'] !== $ENV->private('trackerHost')) {
    send_irc(DEBUG_CHAN, 'Ocelot Auth Failure '.$_SERVER['REMOTE_ADDR']);
    error(403);
}

switch ($Type) {
    case 'expiretoken':
        if (isset($_GET['tokens'])) {
            $Tokens = explode(',', $_GET['tokens']);
            if (empty($Tokens)) {
                error(0);
            }
            $Cond = $UserIDs = [];
            foreach ($Tokens as $Key => $Token) {
                list($UserID, $TorrentID) = explode(':', $Token);
                if (!is_numeric($UserID) || !is_numeric($TorrentID)) {
                    continue;
                }
                $Cond[] = "(UserID = $UserID AND TorrentID = $TorrentID)";
                $UserIDs[] = $UserID;
            }
            if (!empty($Cond)) {
                $Query = "
          UPDATE users_freeleeches
          SET Expired = TRUE
          WHERE ".implode(" OR ", $Cond);
                $app->dbOld->query($Query);
                foreach ($UserIDs as $UserID) {
                    $app->cache->delete("users_tokens_$UserID");
                }
            }
        } else {
            $TorrentID = $_REQUEST['torrentid'];
            $UserID = $_REQUEST['userid'];
            if (!is_numeric($TorrentID) || !is_numeric($UserID)) {
                error(403);
            }
            $app->dbOld->query("
        UPDATE users_freeleeches
        SET Expired = TRUE
        WHERE UserID = $UserID
          AND TorrentID = $TorrentID");
            $app->cache->delete("users_tokens_$UserID");
        }
        break;
}
