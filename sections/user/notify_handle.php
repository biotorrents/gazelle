<?php
#declare(strict_types=1);

if (!check_perms('site_torrents_notify')) {
    error(403);
}
authorize();

$FormID = '';
$ArtistList = '';
$TagList = '';
$NotTagList = '';
$CategoryList = '';
$MediaList = '';
$Users = '';
$HasFilter = false;

if ($_POST['formid'] && is_number($_POST['formid'])) {
    $FormID = $_POST['formid'];
}

if ($_POST['artists'.$FormID]) {
    $Artists = explode(',', $_POST['artists'.$FormID]);
    $ParsedArtists = [];
    foreach ($Artists as $Artist) {
        if (trim($Artist) !== '') {
            $ParsedArtists[] = db_string(trim($Artist));
        }
    }
    if (count($ParsedArtists) > 0) {
        $ArtistList = '|'.implode('|', $ParsedArtists).'|';
        $HasFilter = true;
    }
}

if ($_POST['newgroupsonly'.$FormID]) {
    $NewGroupsOnly = '1';
    $HasFilter = true;
} else {
    $NewGroupsOnly = '0';
}

if ($_POST['tags'.$FormID]) {
    $TagList = '|';
    $Tags = explode(',', $_POST['tags'.$FormID]);
    foreach ($Tags as $Tag) {
        $TagList.=db_string(trim($Tag)).'|';
    }
    $HasFilter = true;
}

if ($_POST['nottags'.$FormID]) {
    $NotTagList = '|';
    $Tags = explode(',', $_POST['nottags'.$FormID]);
    foreach ($Tags as $Tag) {
        $NotTagList.=db_string(trim($Tag)).'|';
    }
    $HasFilter = true;
}

if ($_POST['categories'.$FormID]) {
    $CategoryList = '|';
    foreach ($_POST['categories'.$FormID] as $Category) {
        $CategoryList.=db_string(trim($Category)).'|';
    }
    $HasFilter = true;
}

if ($_POST['media'.$FormID]) {
    $MediaList = '|';
    foreach ($_POST['media'.$FormID] as $Medium) {
        $MediaList.=db_string(trim($Medium)).'|';
    }
    $HasFilter = true;
}

if ($_POST['users'.$FormID]) {
    $Usernames = explode(',', $_POST['users'.$FormID]);
    $EscapedUsernames = [];
    foreach ($Usernames as $Username) {
        $EscapedUsernames[] = db_string(trim($Username));
    }

    $db->query("
    SELECT ID, Paranoia
    FROM users_main
    WHERE Username IN ('" . implode("', '", $EscapedUsernames) . "')
      AND ID != $user[ID]");
    while (list($UserID, $Paranoia) = $db->next_record()) {
        $Paranoia = unserialize($Paranoia);
        if (!in_array('notifications', $Paranoia)) {
            $Users .= '|' . $UserID . '|';
            $HasFilter = true;
        }
    }
}

if (!$HasFilter) {
    $Err = 'You must add at least one criterion to filter by';
} elseif (!$_POST['label'.$FormID] && !$_POST['id'.$FormID]) {
    $Err = 'You must add a label for the filter set';
}

if ($Err) {
    error($Err);
    Http::redirect("user.php?action=notify");
    error();
}

$ArtistList = str_replace('||', '|', $ArtistList);
$TagList = str_replace('||', '|', $TagList);
$NotTagList = str_replace('||', '|', $NotTagList);
$Users = str_replace('||', '|', $Users);

if ($_POST['id'.$FormID] && is_number($_POST['id'.$FormID])) {
    $db->query("
    UPDATE users_notify_filters
    SET
      Artists='$ArtistList',
      NewGroupsOnly='$NewGroupsOnly',
      Tags='$TagList',
      NotTags='$NotTagList',
      Categories='$CategoryList',
      Media='$MediaList',
      Users ='$Users'
    WHERE ID='".$_POST['id'.$FormID]."'
      AND UserID='$user[ID]'");
} else {
    $db->query("
    INSERT INTO users_notify_filters
      (UserID, Label, Artists, NewGroupsOnly, Tags, NotTags, Categories, Media, Users)
    VALUES
      ('$user[ID]','".db_string($_POST['label'.$FormID])."','$ArtistList','$NewGroupsOnly','$TagList','$NotTagList','$CategoryList','$MediaList','$Users')");
}

$cache->delete_value('notify_filters_'.$user['ID']);
if (($Notify = $cache->get_value('notify_artists_'.$user['ID'])) !== false && $Notify['ID'] === $_POST['id'.$FormID]) {
    $cache->delete_value('notify_artists_'.$user['ID']);
}
Http::redirect("user.php?action=notify");
