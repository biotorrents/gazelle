<?php
#declare(strict_types = 1);

authorize();

$TorrentID = $_POST['torrentid'];
if (!$TorrentID || !is_number($TorrentID)) {
    error(404);
}

if ($Cache->get_value("torrent_{$TorrentID}_lock")) {
    error('Torrent cannot be deleted because the upload process is not completed yet. Please try again later.');
}

$DB->query("
  SELECT
    t.UserID,
    t.GroupID,
    t.Size,
    t.info_hash,
    tg.title,
    ag.Name,
    t.Time,
    COUNT(x.uid)
  FROM torrents AS t
    LEFT JOIN torrents_artists AS ta ON ta.GroupID = t.GroupID
    LEFT JOIN torrents_group AS tg ON tg.ID = t.GroupID
    LEFT JOIN artists_group AS ag ON ag.ArtistID = ta.ArtistID
    LEFT JOIN xbt_snatched AS x ON x.fid = t.ID
  WHERE t.ID = '$TorrentID'");
list($UploaderID, $GroupID, $Size, $InfoHash, $Name, $ArtistName, $Time, $Snatches) = $DB->next_record(MYSQLI_NUM, false);

if ($LoggedUser['ID'] != $UploaderID && !check_perms('torrents_delete')) {
    error(403);
}

if (time_ago($Time) > 3600 * 24 * 7 && !check_perms('torrents_delete')) {
    error('Torrent cannot be deleted because it is over one week old. If you think there is a problem, contact staff.');
}

if ($Snatches > 4 && !check_perms('torrents_delete')) {
    error('Torrent cannot be deleted because it has been snatched by more than 4 people. If you think there is a problem, contact staff.');
}

if ($ArtistName) {
    $Name = "$ArtistName - $Name";
}

if (isset($_SESSION['logged_user']['multi_delete'])) {
    if ($_SESSION['logged_user']['multi_delete'] >= 3 && !check_perms('torrents_delete_fast')) {
        error('You have recently deleted 3 torrents. Please contact a staff member if you need to delete more.');
    }
    $_SESSION['logged_user']['multi_delete']++;
} else {
    $_SESSION['logged_user']['multi_delete'] = 1;
}

$InfoHash = unpack('H*', $InfoHash);
Torrents::delete_torrent($TorrentID, $GroupID);
Misc::write_log("Torrent $TorrentID ($Name) (".number_format($Size / (1024 * 1024), 2).' MB) ('.strtoupper($InfoHash[1]).') was deleted by '.$LoggedUser['Username'].': ' .$_POST['reason'].' '.$_POST['extra']);
Torrents::write_group_log($GroupID, $TorrentID, $LoggedUser['ID'], 'deleted torrent ('.number_format($Size / (1024 * 1024), 2).' MB, '.strtoupper($InfoHash[1]).') for reason: '.$_POST['reason'].' '.$_POST['extra'], 0);

View::show_header('Torrent deleted');
?>
<div>
  <h3>Torrent was successfully deleted.</h3>
</div>

<?php View::show_footer();
