<?php

#declare(strict_types=1);

if (!isset($_GET['userid']) || !is_number($_GET['userid'])) {
    json_die('failure');
}

$UserID = $_GET['userid'];
$CommStats = array(
  'leeching' => false,
  'seeding' => false,
  'snatched' => false,
  'usnatched' => false,
  'downloaded' => false,
  'udownloaded' => false,
  'seedingperc' => false,
);

$User = User::user_info($UserID);

function check_paranoia_here($Setting)
{
    global $User;
    return check_paranoia($Setting, $User['Paranoia'], $User['Class'], $User['ID']);
}

if (check_paranoia_here('seeding+') || check_paranoia_here('leeching+')) {
    $db->query("
    SELECT IF(remaining = 0, 'Seeding', 'Leeching') AS Type, COUNT(x.uid)
    FROM xbt_files_users AS x
      INNER JOIN torrents AS t ON t.ID = x.fid
    WHERE x.uid = '$UserID'
      AND x.active = 1
    GROUP BY Type");
    $PeerCount = $db->to_array(0, MYSQLI_NUM, false);
    if (check_paranoia('seeding+')) {
        $Seeding = isset($PeerCount['Seeding']) ? $PeerCount['Seeding'][1] : 0;
        $CommStats['seeding'] = Text::float($Seeding);
    }
    if (check_paranoia('leeching+')) {
        $CommStats['leeching'] = isset($PeerCount['Leeching']) ? Text::float($PeerCount['Leeching'][1]) : 0;
    }
}
if (check_paranoia_here('snatched+')) {
    $db->query("
    SELECT COUNT(x.uid), COUNT(DISTINCT x.fid)
    FROM xbt_snatched AS x
      INNER JOIN torrents AS t ON t.ID = x.fid
    WHERE x.uid = '$UserID'");
    list($Snatched, $UniqueSnatched) = $db->next_record(MYSQLI_NUM, false);
    $CommStats['snatched'] = Text::float($Snatched);
    if (check_perms('site_view_torrent_snatchlist', $User['Class'])) {
        $CommStats['usnatched'] = Text::float($UniqueSnatched);
    }
    if (check_paranoia_here('seeding+') && check_paranoia_here('snatched+') && $UniqueSnatched > 0) {
        $CommStats['seedingperc'] = 100 * min(1, round($Seeding / $UniqueSnatched, 2));
    }
}
if (check_perms('site_view_torrent_snatchlist', $Class)) {
    $db->query("
    SELECT COUNT(ud.UserID), COUNT(DISTINCT ud.TorrentID)
    FROM users_downloads AS ud
      JOIN torrents AS t ON t.ID = ud.TorrentID
    WHERE ud.UserID = '$UserID'");
    list($NumDownloads, $UniqueDownloads) = $db->next_record(MYSQLI_NUM, false);
    $CommStats['downloaded'] = Text::float($NumDownloads);
    $CommStats['udownloaded'] = Text::float($UniqueDownloads);
}

json_die('success', $CommStats);
