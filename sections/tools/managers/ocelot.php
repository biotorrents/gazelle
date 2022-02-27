<?php
#declare(strict_types=1);

$ENV = ENV::go();

$Key = $_REQUEST['key'];
$Type = $_REQUEST['type'];

if (($Key !== $ENV->getPriv('TRACKER_SECRET')) || $_SERVER['REMOTE_ADDR'] !== $ENV->getPriv('TRACKER_HOST')) {
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
            if (!is_number($UserID) || !is_number($TorrentID)) {
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
            $db->query($Query);
            foreach ($UserIDs as $UserID) {
                $cache->delete_value("users_tokens_$UserID");
            }
        }
    } else {
        $TorrentID = $_REQUEST['torrentid'];
        $UserID = $_REQUEST['userid'];
        if (!is_number($TorrentID) || !is_number($UserID)) {
            error(403);
        }
        $db->query("
        UPDATE users_freeleeches
        SET Expired = TRUE
        WHERE UserID = $UserID
          AND TorrentID = $TorrentID");
        $cache->delete_value("users_tokens_$UserID");
    }
    break;
}
