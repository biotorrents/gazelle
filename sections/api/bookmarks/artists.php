<?php

#declare(strict_types=1);

$app = \Gazelle\App::go();

if (!empty($_GET['userid'])) {
    if ($app->user->cant(["admin" => "sensitiveUserData"])) {
        \Gazelle\Api\Base::failure(400);
    }

    $UserID = $_GET['userid'];
    $Sneaky = ($UserID !== $app->user->core['id']);

    if (!is_numeric($UserID)) {
        \Gazelle\Api\Base::failure(400);
    }

    $app->dbOld->query("
    SELECT
      `Username`
    FROM
      `users_main`
    WHERE
      `ID` = '$UserID'
    ");
    list($Username) = $app->dbOld->next_record();
} else {
    $UserID = $app->user->core['id'];
}

//$ArtistList = Bookmarks::all_bookmarks('artist', $UserID);

$app->dbOld->query("
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

$ArtistList = $app->dbOld->to_array();
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
