<?php

declare(strict_types=1);


$app = App::go();

//NumTorrents is actually the number of things in the collage, the name just isn't generic.

authorize();

include(SERVER_ROOT.'/classes/validate.class.php');
$Val = new Validate();

function add_artist($CollageID, $ArtistID)
{
    $app = App::go();


    $app->dbOld->query("
    SELECT MAX(Sort)
    FROM collages_artists
    WHERE CollageID = '$CollageID'");
    list($Sort) = $app->dbOld->next_record();
    $Sort += 10;

    $app->dbOld->query("
    SELECT ArtistID
    FROM collages_artists
    WHERE CollageID = '$CollageID'
      AND ArtistID = '$ArtistID'");
    if (!$app->dbOld->has_results()) {
        $app->dbOld->query("
      INSERT IGNORE INTO collages_artists
        (CollageID, ArtistID, UserID, Sort, AddedOn)
      VALUES
        ('$CollageID', '$ArtistID', '$app->userNew->core[id]', '$Sort', '" . sqltime() . "')");

        $app->dbOld->query("
      UPDATE collages
      SET NumTorrents = NumTorrents + 1, Updated = '" . sqltime() . "'
      WHERE ID = '$CollageID'");

        $app->cacheOld->delete_value("collage_$CollageID");
        $app->cacheOld->delete_value("artists_collages_$ArtistID");
        $app->cacheOld->delete_value("artists_collages_personal_$ArtistID");

        $app->dbOld->query("
      SELECT UserID
      FROM users_collage_subs
      WHERE CollageID = $CollageID");
        while (list($app->cacheOldUserID) = $app->dbOld->next_record()) {
            $app->cacheOld->delete_value("collage_subs_user_new_$app->cacheOldUserID");
        }
    }
}

$CollageID = $_POST['collageid'];
if (!is_number($CollageID)) {
    error(404);
}
$app->dbOld->query("
  SELECT UserID, CategoryID, Locked, NumTorrents, MaxGroups, MaxGroupsPerUser
  FROM collages
  WHERE ID = '$CollageID'");
list($UserID, $CategoryID, $Locked, $NumTorrents, $MaxGroups, $MaxGroupsPerUser) = $app->dbOld->next_record();

if (!check_perms('site_collages_delete')) {
    if ($Locked) {
        $Err = 'This collage is locked';
    }
    if ($CategoryID == 0 && $UserID != $app->userNew->core['id']) {
        $Err = 'You cannot edit someone else\'s personal collage.';
    }
    if ($MaxGroups > 0 && $NumTorrents >= $MaxGroups) {
        $Err = 'This collage already holds its maximum allowed number of artists.';
    }

    if (isset($Err)) {
        error($Err);
    }
}

if ($MaxGroupsPerUser > 0) {
    $app->dbOld->query("
    SELECT COUNT(*)
    FROM collages_artists
    WHERE CollageID = '$CollageID'
      AND UserID = '$app->userNew->core[id]'");
    list($GroupsForUser) = $app->dbOld->next_record();
    if (!check_perms('site_collages_delete') && $GroupsForUser >= $MaxGroupsPerUser) {
        error(403);
    }
}

if ($_REQUEST['action'] == 'add_artist') {
    $Val->SetFields('url', '1', 'regex', 'The URL must be a link to a artist on the site.', array('regex' => $app->env->regexArtist));
    $Err = $Val->ValidateForm($_POST);

    if ($Err) {
        error($Err);
    }

    $URL = $_POST['url'];

    // Get artist ID
    preg_match($app->env->regexArtist, $URL, $Matches);
    $ArtistID = $Matches[4];
    if (!$ArtistID || (int)$ArtistID === 0) {
        error(404);
    }

    $app->dbOld->query("
    SELECT ArtistID
    FROM artists_group
    WHERE ArtistID = '$ArtistID'");
    list($ArtistID) = $app->dbOld->next_record();
    if (!$ArtistID) {
        error('The artist was not found in the database.');
    }

    add_artist($CollageID, $ArtistID);
} else {
    $URLs = explode("\n", $_REQUEST['urls']);
    $ArtistIDs = [];
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
            $Err = "This collage can only hold $MaxGroups artists.";
        }
        if ($MaxGroupsPerUser > 0 && ($GroupsForUser + count($URLs) > $MaxGroupsPerUser)) {
            $Err = "You may only have $MaxGroupsPerUser artists in this collage.";
        }
    }

    foreach ($URLs as $URL) {
        $Matches = [];
        if (preg_match($app->env->regexArtist, $URL, $Matches)) {
            $ArtistIDs[] = $Matches[4];
            $ArtistID = $Matches[4];
        } else {
            $Err = "One of the entered URLs ($URL) does not correspond to an artist on the site.";
            break;
        }

        $app->dbOld->query("
      SELECT ArtistID
      FROM artists_group
      WHERE ArtistID = '$ArtistID'");
        if (!$app->dbOld->has_results()) {
            $Err = "One of the entered URLs ($URL) does not correspond to an artist on the site.";
            break;
        }
    }

    if ($Err) {
        error($Err);
    }

    foreach ($ArtistIDs as $ArtistID) {
        add_artist($CollageID, $ArtistID);
    }
}
Http::redirect("collages.php?id=$CollageID");
