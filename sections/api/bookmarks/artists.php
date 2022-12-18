<?php

#declare(strict_types=1);

if (!empty($_GET['userid'])) {
    if (!check_perms('users_override_paranoia')) {
        json_die('failure');
    }

    $UserID = $_GET['userid'];
    $Sneaky = ($UserID !== $user['ID']);

    if (!is_number($UserID)) {
        json_die('failure');
    }

    $db->query("
    SELECT
      `Username`
    FROM
      `users_main`
    WHERE
      `ID` = '$UserID'
    ");
    list($Username) = $db->next_record();
} else {
    $UserID = $user['ID'];
}

//$ArtistList = Bookmarks::all_bookmarks('artist', $UserID);

$db->query("
SELECT
  ag.`ArtistID`,
  ag.`Name`
FROM
  `bookmarks_artists` AS ba
INNER JOIN `artists_group` AS ag
ON
  ba.`ArtistID` = ag.`ArtistID`
WHERE
  ba.`UserID` = $UserID
");

$ArtistList = $db->to_array();
$JsonArtists = [];

foreach ($ArtistList as $Artist) {
    list($ArtistID, $Name) = $Artist;
    $JsonArtists[] = array(
      'artistId'   => (int) $ArtistID,
      'artistName' => $Name
  );
}

print
  json_encode(
      array(
      'status' => 'success',
      'response' => array(
        'artists' => $JsonArtists
      )
    )
  );
