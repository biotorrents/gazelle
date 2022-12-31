<?php

//******************************************************************************//
//--------------- Vote on a request --------------------------------------------//
//This page is ajax!

if (!check_perms('site_vote')) {
    error(403);
}

authorize();

if (empty($_GET['id']) || !is_number($_GET['id'])) {
    error(0);
}

$RequestID = $_GET['id'];

if (empty($_GET['amount']) || !is_number($_GET['amount']) || $_GET['amount'] < $MinimumVote) {
    $Amount = $MinimumVote;
} else {
    $Amount = $_GET['amount'];
}

$Bounty = ($Amount * (1 - $RequestTax));

$db->query("
  SELECT TorrentID
  FROM requests
  WHERE ID = $RequestID");
list($Filled) = $db->next_record();

if ($user['BytesUploaded'] >= $Amount && empty($Filled)) {

  // Create vote!
    $db->query("
    INSERT IGNORE INTO requests_votes
      (RequestID, UserID, Bounty)
    VALUES
      ($RequestID, ".$user['ID'].", $Bounty)");

    if ($db->affected_rows() < 1) {
        //Insert failed, probably a dupe vote, just increase their bounty.
        $db->query("
        UPDATE requests_votes
        SET Bounty = (Bounty + $Bounty)
        WHERE UserID = ".$user['ID']."
          AND RequestID = $RequestID");
        echo 'dupe';
    }



    $db->query("
    UPDATE requests
    SET LastVote = NOW()
    WHERE ID = $RequestID");

    $cache->delete_value("request_$RequestID");
    $cache->delete_value("request_votes_$RequestID");

    $ArtistForm = Requests::get_artists($RequestID);
    foreach ($ArtistForm as $Artist) {
        $cache->delete_value('artists_requests_'.$Artist['id']);
    }

    // Subtract amount from user
    $db->query("
    UPDATE users_main
    SET Uploaded = (Uploaded - $Amount)
    WHERE ID = ".$user['ID']);
    $cache->delete_value('user_stats_'.$user['ID']);

    Requests::update_sphinx_requests($RequestID);
    echo 'success';
    $db->query("
    SELECT UserID
    FROM requests_votes
    WHERE RequestID = '$RequestID'
      AND UserID != '$user[ID]'");
    $UserIDs = [];
    while (list($UserID) = $db->next_record()) {
        $UserIDs[] = $UserID;
    }
} elseif ($user['BytesUploaded'] < $Amount) {
    echo 'bankrupt';
}
