<?php

$app = \Gazelle\App::go();

//******************************************************************************//
//--------------- Vote on a request --------------------------------------------//
//This page is ajax!

if (!check_perms('site_vote')) {
    error(403);
}



if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
    error(0);
}

$RequestID = $_GET['id'];

if (empty($_GET['amount']) || !is_numeric($_GET['amount']) || $_GET['amount'] < $MinimumVote) {
    $Amount = $MinimumVote;
} else {
    $Amount = $_GET['amount'];
}

$Bounty = ($Amount * (1 - $RequestTax));

$app->dbOld->query("
  SELECT TorrentID
  FROM requests
  WHERE ID = $RequestID");
list($Filled) = $app->dbOld->next_record();

if ($app->user->extra['BytesUploaded'] >= $Amount && empty($Filled)) {

    // Create vote!
    $app->dbOld->query("
    INSERT IGNORE INTO requests_votes
      (RequestID, UserID, Bounty)
    VALUES
      ($RequestID, " . $app->user->core['id'] . ", $Bounty)");

    if ($app->dbOld->affected_rows() < 1) {
        //Insert failed, probably a dupe vote, just increase their bounty.
        $app->dbOld->query("
        UPDATE requests_votes
        SET Bounty = (Bounty + $Bounty)
        WHERE UserID = " . $app->user->core['id'] . "
          AND RequestID = $RequestID");
        echo 'dupe';
    }



    $app->dbOld->query("
    UPDATE requests
    SET LastVote = NOW()
    WHERE ID = $RequestID");

    $app->cache->delete("request_$RequestID");
    $app->cache->delete("request_votes_$RequestID");

    $ArtistForm = \Gazelle\Requests::get_artists($RequestID);
    foreach ($ArtistForm as $Artist) {
        $app->cache->delete('artists_requests_' . $Artist['id']);
    }

    // Subtract amount from user
    $app->dbOld->query("
    UPDATE users_main
    SET Uploaded = (Uploaded - $Amount)
    WHERE ID = " . $app->user->core['id']);
    $app->cache->delete('user_stats_' . $app->user->core['id']);

    $app->dbOld->query("
    SELECT UserID
    FROM requests_votes
    WHERE RequestID = '$RequestID'
      AND UserID != '{$app->user->core['id']}'");
    $UserIDs = [];
    while (list($UserID) = $app->dbOld->next_record()) {
        $UserIDs[] = $UserID;
    }
} elseif ($app->user->extra['BytesUploaded'] < $Amount) {
    echo 'bankrupt';
}
