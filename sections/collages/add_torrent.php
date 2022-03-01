<?php
#declare(strict_types=1);

authorize();

require_once SERVER_ROOT.'/classes/validate.class.php';
$Val = new Validate;

function add_torrent($CollageID, $GroupID)
{
    global $cache, $user, $db;

    $db->query("
    SELECT MAX(Sort)
    FROM collages_torrents
    WHERE CollageID = '$CollageID'");
    list($Sort) = $db->next_record();
    $Sort += 10;

    $db->query("
    SELECT GroupID
    FROM collages_torrents
    WHERE CollageID = '$CollageID'
      AND GroupID = '$GroupID'");
    if (!$db->has_results()) {
        $db->query("
      INSERT IGNORE INTO collages_torrents
        (CollageID, GroupID, UserID, Sort, AddedOn)
      VALUES
        ('$CollageID', '$GroupID', '$user[ID]', '$Sort', '" . sqltime() . "')");

        $db->query("
      UPDATE collages
      SET NumTorrents = NumTorrents + 1, Updated = '" . sqltime() . "'
      WHERE ID = '$CollageID'");

        $cache->delete_value("collage_$CollageID");
        $cache->delete_value("torrents_details_$GroupID");
        $cache->delete_value("torrent_collages_$GroupID");
        $cache->delete_value("torrent_collages_personal_$GroupID");

        $db->query("
      SELECT UserID
      FROM users_collage_subs
      WHERE CollageID = $CollageID");
        while (list($cacheUserID) = $db->next_record()) {
            $cache->delete_value("collage_subs_user_new_$cacheUserID");
        }
    }
}

$CollageID = $_POST['collageid'];
if (!is_number($CollageID)) {
    error(404);
}
$db->query("
  SELECT UserID, CategoryID, Locked, NumTorrents, MaxGroups, MaxGroupsPerUser
  FROM collages
  WHERE ID = '$CollageID'");
list($UserID, $CategoryID, $Locked, $NumTorrents, $MaxGroups, $MaxGroupsPerUser) = $db->next_record();

if (!check_perms('site_collages_delete')) {
    if ($Locked) {
        $Err = 'This collage is locked';
    }
    if ($CategoryID == 0 && $UserID != $user['ID']) {
        $Err = 'You cannot edit someone else\'s personal collage.';
    }
    if ($MaxGroups > 0 && $NumTorrents >= $MaxGroups) {
        $Err = 'This collage already holds its maximum allowed number of torrents.';
    }

    if (isset($Err)) {
        error($Err);
    }
}

if ($MaxGroupsPerUser > 0) {
    $db->query("
    SELECT COUNT(*)
    FROM collages_torrents
    WHERE CollageID = '$CollageID'
      AND UserID = '$user[ID]'");
    list($GroupsForUser) = $db->next_record();
    if (!check_perms('site_collages_delete') && $GroupsForUser >= $MaxGroupsPerUser) {
        error(403);
    }
}

if ($_REQUEST['action'] == 'add_torrent') {
    $Val->SetFields('url', '1', 'regex', 'The URL must be a link to a torrent on the site.', array('regex' => '/^'.TORRENT_GROUP_REGEX.'/i'));
    $Err = $Val->ValidateForm($_POST);

    if ($Err) {
        error($Err);
    }

    $URL = $_POST['url'];

    // Get torrent ID
    preg_match('/^'.TORRENT_GROUP_REGEX.'/i', $URL, $Matches);
    $TorrentID = (int) $Matches[4];
    Security::int($TorrentID);

    $db->query("
    SELECT ID
    FROM torrents_group
    WHERE ID = '$TorrentID'");
    list($GroupID) = $db->next_record();
    if (!$GroupID) {
        error('The torrent was not found in the database.');
    }

    add_torrent($CollageID, $GroupID);
} else {
    $URLs = explode("\n", $_REQUEST['urls']);
    $GroupIDs = [];
    $Err = '';
    foreach ($URLs as $Key => &$URL) {
        $URL = trim($URL);
        if ($URL == '') {
            unset($URLs[$Key]);
        }
    }
    unset($URL);

    if (!check_perms('site_collages_delete')) {
        if ($MaxGroups > 0 && ($NumTorrents + count($URLs) > $MaxGroups)) {
            $Err = "This collage can only hold $MaxGroups torrents.";
        }
        if ($MaxGroupsPerUser > 0 && ($GroupsForUser + count($URLs) > $MaxGroupsPerUser)) {
            $Err = "You may only have $MaxGroupsPerUser torrents in this collage.";
        }
    }

    foreach ($URLs as $URL) {
        $Matches = [];
        if (preg_match('/^'.TORRENT_GROUP_REGEX.'/i', $URL, $Matches)) {
            $GroupIDs[] = $Matches[4];
            $GroupID = $Matches[4];
        } else {
            $Err = "One of the entered URLs ($URL) does not correspond to a torrent group on the site.";
            break;
        }

        $db->query("
      SELECT ID
      FROM torrents_group
      WHERE ID = '$GroupID'");
        if (!$db->has_results()) {
            $Err = "One of the entered URLs ($URL) does not correspond to a torrent group on the site.";
            break;
        }
    }

    if ($Err) {
        error($Err);
    }

    foreach ($GroupIDs as $GroupID) {
        add_torrent($CollageID, $GroupID);
    }
}
header('Location: collages.php?id='.$CollageID);
