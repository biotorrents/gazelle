<?php

$app = \Gazelle\App::go();

//******************************************************************************//
//--------------- Vote on a request --------------------------------------------//
//This page is ajax!

if (!check_perms('site_vote')) {
    error(403);
}

authorize();

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

if ($app->userNew->extra['BytesUploaded'] >= $Amount && empty($Filled)) {

  // Create vote!
    $app->dbOld->query("
    INSERT IGNORE INTO requests_votes
      (RequestID, UserID, Bounty)
    VALUES
      ($RequestID, ".$app->userNew->core['id'].", $Bounty)");

    if ($app->dbOld->affected_rows() < 1) {
        //Insert failed, probably a dupe vote, just increase their bounty.
        $app->dbOld->query("
        UPDATE requests_votes
        SET Bounty = (Bounty + $Bounty)
        WHERE UserID = ".$app->userNew->core['id']."
          AND RequestID = $RequestID");
        echo 'dupe';
    }



    $app->dbOld->query("
    UPDATE requests
    SET LastVote = NOW()
    WHERE ID = $RequestID");

    $app->cacheOld->delete_value("request_$RequestID");
    $app->cacheOld->delete_value("request_votes_$RequestID");

    $ArtistForm = Requests::get_artists($RequestID);
    foreach ($ArtistForm as $Artist) {
        $app->cacheOld->delete_value('artists_requests_'.$Artist['id']);
    }

    // Subtract amount from user
    $app->dbOld->query("
    UPDATE users_main
    SET Uploaded = (Uploaded - $Amount)
    WHERE ID = ".$app->userNew->core['id']);
    $app->cacheOld->delete_value('user_stats_'.$app->userNew->core['id']);

    Requests::update_sphinx_requests($RequestID);
    echo 'success';
    $app->dbOld->query("
    SELECT UserID
    FROM requests_votes
    WHERE RequestID = '$RequestID'
      AND UserID != '{$app->userNew->core['id']}'");
    $UserIDs = [];
    while (list($UserID) = $app->dbOld->next_record()) {
        $UserIDs[] = $UserID;
    }
} elseif ($app->userNew->extra['BytesUploaded'] < $Amount) {
    echo 'bankrupt';
}
