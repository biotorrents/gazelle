<?php

declare(strict_types=1);

$app = \Gazelle\App::go();

$FriendID = (int) $_POST['friend'];
$Type = $_POST['type'];
$ID = (int) $_POST['id'];
$Note = $_POST['note'];

if (empty($FriendID) || empty($Type) || empty($ID)) {
    echo json_encode(array('status' => 'error', 'response' => 'Error.'));
    error();
}

// Make sure the recipient is on your friends list and not some random dude.
$app->dbOld->prepared_query("
SELECT
  f.`FriendID`,
  u.`Username`
FROM
  `friends` AS f
RIGHT JOIN `users_main` AS u
ON
  u.`ID` = f.`FriendID`
WHERE
  f.`UserID` = '{$app->user->core['id']}' AND f.`FriendID` = '$FriendID'
");


if (!$app->dbOld->has_results()) {
    echo json_encode(array('status' => 'error', 'response' => 'Not on friend list.'));
    error();
}

$Type = strtolower($Type);
$Link = '';
// "a" vs "an", english language is so confusing.
// https://en.wikipedia.org/wiki/English_articles#Distinction_between_a_and_an
$Article = 'a';
switch ($Type) {
    case 'torrent':
        $Link = "torrents.php?id=$ID";
        $app->dbOld->query("
    SELECT
      `title`
    FROM
      `torrents_group`
    WHERE
      `id` = '$ID'
    ");
        break;

    case 'artist':
        $Article = 'an';
        $Link = "artist.php?id=$ID";
        $app->dbOld->query("
    SELECT
      `Name`
    FROM
      `artists_group`
    WHERE
      `ArtistID` = '$ID'
    ");
        break;

    case 'collage':
        $Link = "collages.php?id=$ID";
        $app->dbOld->query("
    SELECT
      `Name`
    FROM
      `collages`
    WHERE
      `ID` = '$ID'
    ");
        break;

    default:
        break;
}

list($Name) = $app->dbOld->next_record();
$Subject = $app->user->core['username'] . " recommended you $Article $Type!";
$Body = $app->user->core['username'] . " recommended you the $Type [url=".site_url()."$Link]$Name".'[/url].';

if (!empty($Note)) {
    $Body = "$Body\n\n$Note";
}

Misc::send_pm($FriendID, $app->user->core['id'], $Subject, $Body);
echo json_encode(array('status' => 'success', 'response' => 'Sent!'));
die();
