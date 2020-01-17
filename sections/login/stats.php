<!-- Stats -->
<div class="box">
      <div class="head colhead_dark"><strong>Stats</strong></div>
      <ul class="stats nobullet">
<?php if (USER_LIMIT > 0) { ?>
        <li>Maximum users: <?=number_format(USER_LIMIT) ?></li>
<?php
}

if (($UserCount = $Cache->get_value('stats_user_count')) === false) {
    $DB->query("
    SELECT COUNT(ID)
    FROM users_main
    WHERE Enabled = '1'");
    list($UserCount) = $DB->next_record();
    $Cache->cache_value('stats_user_count', $UserCount, 86400);
}
$UserCount = (int)$UserCount;
?>
        <li>Enabled users: <?=number_format($UserCount)?> <a href="stats.php?action=users" class="brackets">Details</a></li>
<?php

if (($UserStats = $Cache->get_value('stats_users')) === false) {
    $DB->query("
    SELECT COUNT(ID)
    FROM users_main
    WHERE Enabled = '1'
      AND LastAccess > '".time_minus(3600 * 24)."'");
    list($UserStats['Day']) = $DB->next_record();

    $DB->query("
    SELECT COUNT(ID)
    FROM users_main
    WHERE Enabled = '1'
      AND LastAccess > '".time_minus(3600 * 24 * 7)."'");
    list($UserStats['Week']) = $DB->next_record();

    $DB->query("
    SELECT COUNT(ID)
    FROM users_main
    WHERE Enabled = '1'
      AND LastAccess > '".time_minus(3600 * 24 * 30)."'");
    list($UserStats['Month']) = $DB->next_record();

    $Cache->cache_value('stats_users', $UserStats, 0);
}
?>
        <li>Users active today: <?=number_format($UserStats['Day'])?> (<?=number_format($UserStats['Day'] / $UserCount * 100, 2)?>%)</li>
        <li>Users active this week: <?=number_format($UserStats['Week'])?> (<?=number_format($UserStats['Week'] / $UserCount * 100, 2)?>%)</li>
        <li>Users active this month: <?=number_format($UserStats['Month'])?> (<?=number_format($UserStats['Month'] / $UserCount * 100, 2)?>%)</li>
<?php

if (($TorrentCount = $Cache->get_value('stats_torrent_count')) === false) {
    $DB->query("
    SELECT COUNT(ID)
    FROM torrents");
    list($TorrentCount) = $DB->next_record();
    $Cache->cache_value('stats_torrent_count', $TorrentCount, 86400); // 1 day cache
}

if (($GroupCount = $Cache->get_value('stats_group_count')) === false) {
    $DB->query("
    SELECT COUNT(ID)
    FROM torrents_group");
    list($GroupCount) = $DB->next_record();
    $Cache->cache_value('stats_group_count', $GroupCount, 86400); // 1 day cache
}

if (($TorrentSizeTotal = $Cache->get_value('stats_torrent_size_total')) === false) {
    $DB->query("
    SELECT SUM(Size)
    FROM torrents");
    list($TorrentSizeTotal) = $DB->next_record();
    $Cache->cache_value('stats_torrent_size_total', $TorrentSizeTotal, 86400); // 1 day cache
}
?>
        <li>Total Size of Torrents: <?=Format::get_size($TorrentSizeTotal)?> </li>
<?php

if (($ArtistCount = $Cache->get_value('stats_artist_count')) === false) {
    $DB->query("
    SELECT COUNT(ArtistID)
    FROM artists_group");
    list($ArtistCount) = $DB->next_record();
    $Cache->cache_value('stats_artist_count', $ArtistCount, 86400); // 1 day cache
}

?>
        <li>Torrents: <?=number_format($TorrentCount)?></li>
        <li>Torrent Groups: <?=number_format($GroupCount)?></li>
        <li>Artists: <?=number_format($ArtistCount)?></li>
<?php
// End Torrent Stats

if (($RequestStats = $Cache->get_value('stats_requests')) === false) {
    $DB->query("
    SELECT COUNT(ID)
    FROM requests");
    list($RequestCount) = $DB->next_record();
    $DB->query("
    SELECT COUNT(ID)
    FROM requests
    WHERE FillerID > 0");
    list($FilledCount) = $DB->next_record();
    $Cache->cache_value('stats_requests', array($RequestCount, $FilledCount), 11280);
} else {
    list($RequestCount, $FilledCount) = $RequestStats;
}

// Do not divide by zero
if ($RequestCount > 0) {
    $RequestsFilledPercent = $FilledCount / $RequestCount * 100;
} else {
    $RequestsFilledPercent = 0;
}

?>
        <li>Requests: <?=number_format($RequestCount)?> (<?=number_format($RequestsFilledPercent, 2)?>% filled)</li>
<?php

if ($SnatchStats = $Cache->get_value('stats_snatches')) {
    ?>
        <li>Snatches: <?=number_format($SnatchStats)?></li>
<?php
}

if (($PeerStats = $Cache->get_value('stats_peers')) === false) {
    // Cache lock!
    $PeerStatsLocked = $Cache->get_value('stats_peers_lock');
    if (!$PeerStatsLocked) {
        $Cache->cache_value('stats_peers_lock', 1, 30);
        $DB->query("
      SELECT IF(remaining=0,'Seeding','Leeching') AS Type, COUNT(uid)
      FROM xbt_files_users
      WHERE active = 1
      GROUP BY Type");
        $PeerCount = $DB->to_array(0, MYSQLI_NUM, false);
        $SeederCount = $PeerCount['Seeding'][1] ?: 0;
        $LeecherCount = $PeerCount['Leeching'][1] ?: 0;
        $Cache->cache_value('stats_peers', array($LeecherCount, $SeederCount), 604800); // 1 week cache
        $Cache->delete_value('stats_peers_lock');
    }
} else {
    $PeerStatsLocked = false;
    list($LeecherCount, $SeederCount) = $PeerStats;
}

if (!$PeerStatsLocked) {
    $Ratio = Format::get_ratio_html($SeederCount, $LeecherCount);
    $PeerCount = number_format($SeederCount + $LeecherCount);
    $SeederCount = number_format($SeederCount);
    $LeecherCount = number_format($LeecherCount);
} else {
    $PeerCount = $SeederCount = $LeecherCount = $Ratio = 'Server busy';
}
?>
        <li>Peers: <?=$PeerCount?></li>
        <li>Seeders: <?=$SeederCount?></li>
        <li>Leechers: <?=$LeecherCount?></li>
        <li>Seeder/leecher ratio: <?=$Ratio?></li>
      </ul>
    </div>
