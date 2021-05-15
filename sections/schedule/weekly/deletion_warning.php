<?php
#declare(strict_types=1);

// Send warnings to uploaders of torrents that will be deleted this week
$DB->query("
  SELECT
    t.ID,
    t.GroupID,
    COALESCE(NULLIF(tg.Name,''), NULLIF(tg.Title2,''), tg.NameJP) AS Name,
    t.UserID
  FROM torrents AS t
    JOIN torrents_group AS tg ON tg.ID = t.GroupID
    JOIN users_info AS u ON u.UserID = t.UserID
  WHERE t.last_action < NOW() - INTERVAL 20 DAY
    AND t.last_action != 0
    AND u.UnseededAlerts = '1'
  ORDER BY t.last_action ASC");
  
$TorrentIDs = $DB->to_array();
$TorrentAlerts = [];

foreach ($TorrentIDs as $TorrentID) {
    list($ID, $GroupID, $Name, $UserID) = $TorrentID;

    if (array_key_exists($UserID, $InactivityExceptionsMade) && (time() < $InactivityExceptionsMade[$UserID])) {
        // Don't notify exceptions
        continue;
    }

    if (!array_key_exists($UserID, $TorrentAlerts)) {
        $TorrentAlerts[$UserID] = array('Count' => 0, 'Msg' => '');
    }

    $ArtistName = Artists::display_artists(Artists::get_artist($GroupID), false, false, false);
    if ($ArtistName) {
        $Name = "$ArtistName - $Name";
    }

    $TorrentAlerts[$UserID]['Msg'] .= "\n[url=".site_url()."torrents.php?torrentid=$ID]".$Name."[/url]";
    $TorrentAlerts[$UserID]['Count']++;
}

foreach ($TorrentAlerts as $UserID => $MessageInfo) {
    Misc::send_pm($UserID, 0, 'Unseeded torrent notification', $MessageInfo['Count']." of your uploads will be deleted for inactivity soon. Unseeded torrents are deleted after 4 weeks. If you still have the files, you can seed your uploads by ensuring the torrents are in your client and that they aren't stopped. You can view the time that a torrent has been unseeded by clicking on the torrent description line and looking for the \"Last active\" time. For more information, please go [url=".site_url()."wiki.php?action=article&amp;id=663]here[/url].\n\nThe following torrent".($MessageInfo['Count'] > 1 ? 's' : '').' will be removed for inactivity:'.$MessageInfo['Msg']."\n\nIf you no longer wish to receive these notifications, please disable them in your profile settings.");
}
