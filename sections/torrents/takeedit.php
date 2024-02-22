<?php

#declare(strict_types=1);

$app = Gazelle\App::go();

//******************************************************************************//
//--------------- Take edit ----------------------------------------------------//
// This pages handles the backend of the 'edit torrent' function. It checks     //
// the data, and if it all validates, it edits the values in the database       //
// that correspond to the torrent in question.                                  //
//******************************************************************************//




$Validate = new Validate();


//******************************************************************************//
//--------------- Set $Properties array ----------------------------------------//
// This is used if the form doesn't validate, and when the time comes to enter  //
// it into the database.                                                        //
//******************************************************************************//

$Properties = [];
$_POST['type'] = $_POST['type'] + 1;
$TypeID = (int) $_POST['type'];
$Type = $Categories[$TypeID - 1];
$TorrentID = (int) $_POST['torrentid'];

$Properties['BadTags'] = (isset($_POST['bad_tags'])) ? 1 : 0;
$Properties['BadFolders'] = (isset($_POST['bad_folders'])) ? 1 : 0;
$Properties['BadFiles'] = (isset($_POST['bad_files'])) ? 1 : 0;
$Properties['Format'] = $_POST['format'];
$Properties['Media'] = $_POST['media'];
$Properties['Bitrate'] = $_POST['bitrate'];
$Properties['Encoding'] = $_POST['bitrate'];
$Properties['Trumpable'] = (isset($_POST['make_trumpable'])) ? 1 : 0;
$Properties['TorrentDescription'] = $_POST['release_desc'];
$Properties['Name'] = $_POST['title'];
$Properties['Container'] = $_POST['container'];
$Properties['Codec'] = $_POST['codec'];
$Properties['Resolution'] = $_POST['resolution'];
$Properties['Version'] = $_POST['version'];
$Properties['Censored'] = (isset($_POST['censored'])) ? '1' : '0';
$Properties['Anonymous'] = (isset($_POST['anonymous'])) ? '1' : '0';
$Properties['Archive'] = (isset($_POST['archive']) && $_POST['archive'] != '---') ? $_POST['archive'] : '';

if ($_POST['album_desc']) {
    $Properties['GroupDescription'] = $_POST['album_desc'];
}

if (check_perms('torrents_freeleech')) {
    $Free = (int) $_POST['freeleech'];
    if (!in_array($Free, array(0, 1, 2))) {
        error(404);
    }
    $Properties['FreeLeech'] = $Free;

    if ($Free == 0) {
        $FreeType = 0;
    } else {
        $FreeType = (int) $_POST['freeleechtype'];
        if (!in_array($Free, array(0, 1, 2, 3))) {
            error(404);
        }
    }
    $Properties['FreeLeechType'] = $FreeType;
}


//******************************************************************************//
//--------------- Validate data in edit form -----------------------------------//

$app->dbOld->query("
  SELECT UserID, FreeTorrent
  FROM torrents
  WHERE ID = $TorrentID");

if (!$app->dbOld->has_results()) {
    error(404);
}

// list($UserID, $Remastered, $RemasterYear, $CurFreeLeech) = $app->dbOld->next_record(MYSQLI_BOTH, false);
list($UserID, $CurFreeLeech) = $app->dbOld->next_record(MYSQLI_BOTH, false);

if ($app->user->core['id'] != $UserID && !check_perms('torrents_edit')) {
    error(403);
}

if ($Properties['UnknownRelease'] && !($Remastered == '1' && !$RemasterYear) && !check_perms('edit_unknowns')) {
    // It's Unknown now, and it wasn't before
    if ($app->user->core['id'] != $UserID) {
        // Hax
        error();
    }
}

$Validate->SetFields('type', '1', 'number', 'Not a valid type.', array('maxlength' => count($Categories), 'minlength' => 1));
$Err = $Validate->ValidateForm($_POST); // Validate the form

if ($Properties['Remastered'] && !$Properties['RemasterYear']) {
    //Unknown Edit!
    if ($app->user->core['id'] == $UserID || check_perms('edit_unknowns')) {
        //Fine!
    } else {
        $Err = "You may not edit someone else's upload to unknown release.";
    }
}

// Strip out Amazon's padding
$AmazonReg = '/(http:\/\/ecx.images-amazon.com\/images\/.+)(\._.*_\.jpg)/i';
$Matches = [];
if (preg_match($RegX, $Properties['Image'], $Matches)) {
    $Properties['Image'] = $Matches[1] . '.jpg';
}

if ($Err) { // Show the upload form, with the data the user entered
    if (check_perms('site_debug')) {
        error($Err);
    }
    error($Err);
}


//******************************************************************************//
//--------------- Make variables ready for database input ----------------------//

// Shorten and escape $Properties for database input
$T = [];
foreach ($Properties as $Key => $Value) {
    $T[$Key] = "'" . db_string(trim($Value)) . "'";
    if (!$T[$Key]) {
        $T[$Key] = null;
    }
}

$T['Censored'] = $Properties['Censored'];
$T['Anonymous'] = $Properties['Anonymous'];


//******************************************************************************//
//--------------- Start database stuff -----------------------------------------//

$dbTorVals = [];
$app->dbOld->query("
  SELECT Media, Container, Codec, Resolution, Version, Description, Censored, Anonymous, Archive
  FROM torrents
  WHERE ID = $TorrentID");

$dbTorVals = $app->dbOld->to_array(false, MYSQLI_ASSOC);
$dbTorVals = $dbTorVals[0];
$LogDetails = '';

foreach ($dbTorVals as $Key => $Value) {
    $Value = "'$Value'";
    if ($Value != $T[$Key]) {
        if (!isset($T[$Key])) {
            continue;
        }

        if ((empty($Value) && empty($T[$Key])) || ($Value == "'0'" && $T[$Key] == "''")) {
            continue;
        }

        if ($LogDetails == '') {
            $LogDetails = "$Key: $Value -> " . $T[$Key];
        } else {
            $LogDetails = "$LogDetails, $Key: $Value -> " . $T[$Key];
        }
    }
}

$T['Censored'] = $Properties['Censored'];
$T['Anonymous'] = $Properties['Anonymous'];

// Update info for the torrent
$SQL = "
  UPDATE torrents
  SET
    Media = $T[Media],
    Container = $T[Container],
    Codec = $T[Codec],
    Resolution = $T[Resolution],
    Version = $T[Version],
    Archive = $T[Archive],
    Censored = $T[Censored],
    Anonymous = $T[Anonymous],";

if (check_perms('torrents_freeleech')) {
    $SQL .= "FreeTorrent = $T[FreeLeech],";
    $SQL .= "FreeLeechType = $T[FreeLeechType],";
}

if (check_perms('users_mod')) {
    $app->dbOld->query("
      SELECT TorrentID
      FROM torrents_bad_tags
      WHERE TorrentID = '$TorrentID'");
    list($btID) = $app->dbOld->next_record();

    if (!$btID && $Properties['BadTags']) {
        $app->dbOld->query("
          INSERT INTO torrents_bad_tags
          VALUES ($TorrentID, {$app->user->core['id']}, NOW())");
    }

    if ($btID && !$Properties['BadTags']) {
        $app->dbOld->query("
          DELETE FROM torrents_bad_tags
          WHERE TorrentID = '$TorrentID'");
    }

    $app->dbOld->query("
      SELECT TorrentID
      FROM torrents_bad_folders
      WHERE TorrentID = '$TorrentID'");
    list($bfID) = $app->dbOld->next_record();

    if (!$bfID && $Properties['BadFolders']) {
        $app->dbOld->query("
          INSERT INTO torrents_bad_folders
          VALUES ($TorrentID, {$app->user->core['id']}, NOW())");
    }

    if ($bfID && !$Properties['BadFolders']) {
        $app->dbOld->query("
          DELETE FROM torrents_bad_folders
          WHERE TorrentID = '$TorrentID'");
    }

    $app->dbOld->query("
      SELECT TorrentID
      FROM torrents_bad_files
      WHERE TorrentID = '$TorrentID'");
    list($bfiID) = $app->dbOld->next_record();

    if (!$bfiID && $Properties['BadFiles']) {
        $app->dbOld->query("
          INSERT INTO torrents_bad_files
          VALUES ($TorrentID, {$app->user->core['id']}, NOW())");
    }

    if ($bfiID && !$Properties['BadFiles']) {
        $app->dbOld->query("
          DELETE FROM torrents_bad_files
          WHERE TorrentID = '$TorrentID'");
    }
}

$SQL .= "
  Description = $T[TorrentDescription]
  WHERE ID = $TorrentID";
$app->dbOld->query($SQL);

if (check_perms('torrents_freeleech') && $Properties['FreeLeech'] != $CurFreeLeech) {
    Torrents::freeleech_torrents($TorrentID, $Properties['FreeLeech'], $Properties['FreeLeechType']);
}

$app->dbOld->query("
  SELECT GroupID, Time
  FROM torrents
  WHERE ID = '$TorrentID'");
list($GroupID, $Time) = $app->dbOld->next_record();

$app->dbOld->query("
  SELECT Enabled
  FROM users_main
  WHERE ID = $UserID");
list($Enabled) = $app->dbOld->next_record();

$app->dbOld->query("
  SELECT `title`
  FROM `torrents_group`
  WHERE `id` = $GroupID");
list($Name) = $app->dbOld->next_record(MYSQLI_NUM, false);

Misc::write_log("Torrent $TorrentID ($Name) in group $GroupID was edited by " . $app->user->core['username'] . " ($LogDetails)"); // TODO: this is probably broken
Torrents::write_group_log($GroupID, $TorrentID, $app->user->core['id'], $LogDetails, 0);

$app->cache->delete("torrents_details_$GroupID");
$app->cache->delete("torrent_download_$TorrentID");

Torrents::update_hash($GroupID);
// All done!

Gazelle\Http::redirect("torrents.php?id=$GroupID");
