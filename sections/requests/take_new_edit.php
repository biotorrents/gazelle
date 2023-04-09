<?php

#declare(strict_types=1);


$app = \Gazelle\App::go();

//******************************************************************************//
//----------------- Take request -----------------------------------------------//
authorize();

if ($_POST['action'] !== 'takenew' && $_POST['action'] !== 'takeedit') {
    error(0);
}

$NewRequest = ($_POST['action'] === 'takenew');

if (!$NewRequest) {
    $ReturnEdit = true;
}

if ($NewRequest) {
    if (!check_perms('site_submit_requests') || $app->user->extra['BytesUploaded'] < 250 * 1024 * 1024) {
        error(403);
    }
} else {
    $RequestID = $_POST['requestid'];
    if (!is_numeric($RequestID)) {
        error(0);
    }

    $Request = Requests::get_request($RequestID);
    if ($Request === false) {
        error(404);
    }
    $VoteArray = Requests::get_votes_array($RequestID);
    $VoteCount = count($VoteArray['Voters']);
    $IsFilled = !empty($Request['TorrentID']);
    $CategoryName = $Categories[$Request['CategoryID'] - 1];
    $ProjectCanEdit = (check_perms('project_team') && !$IsFilled && ($Request['CategoryID'] === '0' || ($CategoryName === 'Music' && $Year === '0')));
    $CanEdit = ((!$IsFilled && $app->user->core['id'] === $Request['UserID'] && $VoteCount < 2) || $ProjectCanEdit || check_perms('site_moderate_requests'));

    if (!$CanEdit) {
        error(403);
    }
}

// Validate
if (empty($_POST['type'])) {
    error(0);
}

$CategoryName = $_POST['type'];
$CategoryID = (array_search($CategoryName, $Categories) + 1);

if (empty($CategoryID)) {
    error(0);
}

if (empty($_POST['title']) && empty($_POST['title_rj']) && empty($_POST['title_jp'])) {
    $Err = 'You must enter at least one title!';
}

if (!empty($_POST['title'])) {
    $Title = trim($_POST['title']);
}

if (!empty($_POST['title_rj'])) {
    $Title2 = trim($_POST['title_rj']);
}

if (!empty($_POST['title_jp'])) {
    $TitleJP = trim($_POST['title_jp']);
}

if (empty($_POST['tags'])) {
    $Err = 'You forgot to enter any tags!';
} else {
    $Tags = trim($_POST['tags']);
}

if ($NewRequest) {
    if (empty($_POST['amount'])) {
        $Err = 'You forgot to enter any bounty!';
    } else {
        $Bounty = trim($_POST['amount']);
        if (!is_numeric($Bounty)) {
            $Err = 'Your entered bounty is not a number';
        } elseif ($Bounty < 100 * 1024 * 1024) {
            $Err = 'Minimum bounty is 100 MB.';
        }
        $Bytes = $Bounty; // From MB to B
    }
}

if (empty($_POST['image'])) {
    $Image = '';
} else {
    if (preg_match("/{$app->env->regexImage}/i", trim($_POST['image'])) > 0) {
        $Image = trim($_POST['image']);
    } else {
        $Err = Text::esc($_POST['image']).' does not appear to be a valid link to an image.';
    }
}

if (empty($_POST['description'])) {
    $Err = 'You forgot to enter a description.';
} else {
    $Description = trim($_POST['description']);
}

if (empty($_POST['artists']) && $CategoryName !== 'Other') {
    $Err = 'You did not enter any artists.';
} else {
    $Artists = $_POST['artists'];
}

// Not required
/*
if (!empty($_POST['cataloguenumber']) && $CategoryName === 'Movies') {
    $CatalogueNumber = trim($_POST['cataloguenumber']);
} else {
    $CatalogueNumber = '';
}
*/

// GroupID
if (!empty($_POST['groupid'])) {
    $GroupID = $_POST['groupid'];
    if (is_numeric($GroupID)) {
        $app->dbOld->query("
      SELECT CategoryID
      FROM torrents_group
      WHERE ID = '$GroupID'");
        if (!$app->dbOld->has_results()) {
            $Err = 'The torrent group, if entered, must correspond to a torrent group on the site.';
        } else {
            if ($CategoryID !== $app->dbOld->to_array()[0]['CategoryID']) {
                $Err = 'The category of the specified torrent group does not match the category of your request.';
            }
        }
    } else {
        $Err = 'The torrent group, if entered, must correspond to a torrent group on the site.';
    }
} elseif (isset($_POST['groupid']) && $_POST['groupid'] === '0') {
    $GroupID = 0;
}

// For refilling on error
$ArtistNames = [];
$ArtistForm = [];
for ($i = 0; $i < count($Artists); $i++) {
    if (trim($Artists[$i]) !== '') {
        if (!in_array($Artists[$i], $ArtistNames)) {
            $ArtistForm[] = array('name' => trim($Artists[$i]));
            $ArtistNames[] = trim($Artists[$i]);
        }
    }
}
if (!isset($ArtistNames[0])) {
    unset($ArtistForm);
}

if (!empty($Err)) {
    error($Err);
    $Div = $_POST['unit'] === 'mb' ? 1024 * 1024 : 1024 * 1024 * 1024;
    $Bounty /= $Div;
    include(serverRoot.'/sections/requests/new_edit.php');
    #error();
}

if (!isset($GroupID)) {
    $GroupID = 0;
}

// Query time!
if ($NewRequest) {
    $app->dbOld->query('
    INSERT INTO requests (
      UserID, TimeAdded, LastVote, CategoryID, Title, Title2, TitleJP, Image, Description,
      CatalogueNumber, Visible, GroupID)
    VALUES
      ('.$app->user->core['id'].", NOW(), NOW(), $CategoryID, '".db_string($Title)."', '".db_string($Title2)."', '".db_string($TitleJP)."', '".db_string($Image)."', '".db_string($Description)."',
          '".db_string($CatalogueNumber)."', '1', '$GroupID')");

    $RequestID = $app->dbOld->inserted_id();
} else {
    $app->dbOld->query("
    UPDATE requests
    SET CategoryID = $CategoryID,
      Title = '".db_string($Title)."',
      Title2 = '".db_string($Title2??"")."',
      TitleJP = '".db_string($TitleJP??"")."',
      Image = '".db_string($Image)."',
      Description = '".db_string($Description)."',
      CatalogueNumber = '".db_string($CatalogueNumber)."'
    WHERE ID = $RequestID");

    // We need to be able to delete artists/tags
    $app->dbOld->query("
    SELECT ArtistID
    FROM requests_artists
    WHERE RequestID = $RequestID");
    $RequestArtists = $app->dbOld->to_array();
    foreach ($RequestArtists as $RequestArtist) {
        $app->cache->delete("artists_requests_".$RequestArtist['ArtistID']);
    }
    $app->dbOld->query("
    DELETE FROM requests_artists
    WHERE RequestID = $RequestID");
    $app->cache->delete("request_artists_$RequestID");
}

if ($GroupID) {
    $app->cache->delete("requests_group_$GroupID");
}

/*
 * Multiple Artists!
 * For the multiple artists system, we have 3 steps:
 *   1. See if each artist given already exists and if it does, grab the ID.
 *   2. For each artist that didn't exist, create an artist.
 *   3. Create a row in the requests_artists table for each artist, based on the ID.
 */
if (isset($ArtistForm)) {
    foreach ($ArtistForm as $Num => $Artist) {
        // 1. See if each artist given already exists and if it does, grab the ID.
        $app->dbOld->query("
      SELECT
        ArtistID,
        Name
      FROM artists_group
      WHERE Name = '".db_string($Artist['name'])."'");

        list($ArtistID, $ArtistName) = $app->dbOld->next_record(MYSQLI_NUM, false);
        $ArtistForm[$Num] = array('name' => $ArtistName, 'id' => $ArtistID);

        if (!$ArtistID) {
            // 2. For each artist that didn't exist, create an artist.
            $app->dbOld->query("
        INSERT INTO artists_group (Name)
        VALUES ('".db_string($Artist['name'])."')");
            $ArtistID = $app->dbOld->inserted_id();

            $app->cache->increment('stats_artist_count');

            $ArtistForm[$Num] = array('id' => $ArtistID, 'name' => $Artist['name']);
        }
    }

    // 3. Create a row in the requests_artists table for each artist, based on the ID.
    foreach ($ArtistForm as $Num => $Artist) {
        $app->dbOld->query("
      INSERT IGNORE INTO requests_artists
        (RequestID, ArtistID)
      VALUES
        ($RequestID, ".$Artist['id'].")");
        $app->cache->delete('artists_requests_'.$Artist['id']);
    }
// End music only
} else {
    // Not a music request anymore, delete music only fields.
    if (!$NewRequest) {
        $app->dbOld->query("
      SELECT ArtistID
      FROM requests_artists
      WHERE RequestID = $RequestID");
        $OldArtists = $app->dbOld->collect('ArtistID');
        foreach ($OldArtists as $ArtistID) {
            if (empty($ArtistID)) {
                continue;
            }
            // Get a count of how many groups or requests use the artist ID
            $app->dbOld->query("
        SELECT COUNT(ag.ArtistID)
        FROM artists_group AS ag
          LEFT JOIN requests_artists AS ra ON ag.ArtistID = ra.ArtistID
        WHERE ra.ArtistID IS NOT NULL
          AND ag.ArtistID = '$ArtistID'");
            list($ReqCount) = $app->dbOld->next_record();
            $app->dbOld->query("
        SELECT COUNT(ag.ArtistID)
        FROM artists_group AS ag
          LEFT JOIN torrents_artists AS ta ON ag.ArtistID = ta.ArtistID
        WHERE ta.ArtistID IS NOT NULL
          AND ag.ArtistID = '$ArtistID'");
            list($GroupCount) = $app->dbOld->next_record();
            if (($ReqCount + $GroupCount) === 0) {
                // The only group to use this artist
                Artists::delete_artist($ArtistID);
            } else {
                // Not the only group, still need to clear cache
                $app->cache->delete("artists_requests_$ArtistID");
            }
        }
        $app->dbOld->query("
      DELETE FROM requests_artists
      WHERE RequestID = $RequestID");
        $app->cache->delete("request_artists_$RequestID");
    }
}

// Tags
if (!$NewRequest) {
    $app->dbOld->query("
    DELETE FROM requests_tags
    WHERE RequestID = $RequestID");
}

$Tags = array_unique(explode(',', $Tags));
foreach ($Tags as $Index => $Tag) {
    $Tag = Misc::sanitize_tag($Tag);
    $Tag = Misc::get_alias_tag($Tag);
    $Tags[$Index] = $Tag; // For announce
    $app->dbOld->query("
    INSERT INTO tags
      (Name, UserID)
    VALUES
      ('$Tag', ".$app->user->core['id'].")
    ON DUPLICATE KEY UPDATE
      Uses = Uses + 1");

    $TagID = $app->dbOld->inserted_id();

    $app->dbOld->query("
    INSERT IGNORE INTO requests_tags
      (TagID, RequestID)
    VALUES
      ($TagID, $RequestID)");
}

if ($NewRequest) {
    // Remove the bounty and create the vote
    $app->dbOld->query("
    INSERT INTO requests_votes
      (RequestID, UserID, Bounty)
    VALUES
      ($RequestID, ".$app->user->core['id'].', '.($Bytes * (1 - $RequestTax)).')');

    $app->dbOld->query("
    UPDATE users_main
    SET Uploaded = (Uploaded - $Bytes)
    WHERE ID = ".$app->user->core['id']);
    $app->cache->delete('user_stats_'.$app->user->core['id']);

    $AnnounceTitle = empty($Title) ? (empty($Title2) ? $TitleJP : $Title2) : $Title;

    $Announce = "\"$AnnounceTitle\"".(isset($ArtistForm) ? (' - '.Artists::display_artists($ArtistForm, false, false)) : '').' '.site_url()."requests.php?action=view&id=$RequestID - ".implode(' ', $Tags);
    send_irc(REQUEST_CHAN, $Announce);
} else {
    $app->cache->delete("request_$RequestID");
    $app->cache->delete("request_artists_$RequestID");
}

Requests::update_sphinx_requests($RequestID);

Http::redirect("requests.php?action=view&id=$RequestID");
