<?php

declare(strict_types=1);

$app = \Gazelle\App::go();

authorize();

$group_id = (int) $_REQUEST['groupid'];
Security::int($group_id);

// Usual perm checks
if (!check_perms('torrents_edit')) {
    $app->dbOld->prepared_query("
    SELECT
      `UserID`
    FROM
      `torrents`
    WHERE
      `GroupID` = '$GroupID'
    ");


    if (!in_array($app->user->core['id'], $app->dbOld->collect('UserID'))) {
        error(403);
    }
}

# ?
if (check_perms('torrents_freeleech')
    && (isset($_POST['freeleech'])
        xor isset($_POST['neutralleech'])
        xor isset($_POST['unfreeleech']))) {
    if (isset($_POST['freeleech'])) {
        $Free = 1;
    } elseif (isset($_POST['neutralleech'])) {
        $Free = 2;
    } else {
        $Free = 0;
    }

    if (isset($_POST['freeleechtype']) && in_array($_POST['freeleechtype'], [0, 1, 2, 3])) {
        $FreeType = $_POST['freeleechtype'];
    } else {
        error(404);
    }

    Torrents::freeleech_groups($group_id, $Free, $FreeType);
}

$Artists = $_POST['idols'];

// Escape fields
$workgroup = db_string($_POST['studio']);
$location = db_string($_POST['series']);
$year = db_string((int)$_POST['year']);
$identifier = db_string($_POST['catalogue']);

// Get some info for the group log
$app->dbOld->prepared_query("
SELECT
  `year`
FROM
  `torrents_group`
WHERE
  `id` = '$group_id'
");

list($OldYear) = $app->dbOld->next_record();

$app->dbOld->prepared_query("
UPDATE
  `torrents_group`
SET
  `year` = '$year',
  `identifier` = '$identifier',
  `workgroup` = '$workgroup',
  `location` = '$location'
WHERE
  `id` = '$group_id'
");


if ($OldYear !== $year) {
    $Message = db_string("Year changed from $OldYear to $year");

    $app->dbOld->prepared_query("
    INSERT INTO `group_log`(`GroupID`, `UserID`, `Time`, `Info`)
    VALUES(
      '$group_id',
      '{$app->user->core['id']}',
      NOW(),
      '$Message')
    ");
}

$app->dbOld->prepared_query("
SELECT
  ag.`Name`
FROM
  `artists_group` AS ag
JOIN `torrents_artists` AS ta
ON
  ag.`ArtistID` = ta.`ArtistID`
WHERE
  ta.`GroupID` = '$group_id'
");


while ($r = $app->dbOld->next_record(MYSQLI_ASSOC, true)) {
    $CurrArtists[] = $r['Name'];
}

foreach ($Artists as $Artist) {
    if (!in_array($Artist, $CurrArtists)) {
        $Artist = db_string($Artist);
        $app->dbOld->prepared_query("
        SELECT
          `ArtistID`
        FROM
          `artists_group`
        WHERE
          `Name` = '$Artist'
        ");


        if ($app->dbOld->has_results()) {
            list($ArtistID) = $app->dbOld->next_record();
        } else {
            $app->dbOld->prepared_query("
            INSERT INTO `artists_group`(`Name`)
            VALUES('$Artist')
            ");

            $ArtistID = $app->dbOld->inserted_id();
        }

        $app->dbOld->prepared_query("
        INSERT INTO `torrents_artists`(`GroupID`, `ArtistID`, `UserID`)
        VALUES(
          '$group_id',
          '$ArtistID',
          '{$app->user->core['id']}'
        )
        ON DUPLICATE KEY
        UPDATE
          `UserID` = '{$app->user->core['id']}'
        "); // Why does this even happen

        $app->cacheNew->delete('artist_groups_'.$ArtistID);
    }
}

foreach ($CurrArtists as $CurrArtist) {
    if (!in_array($CurrArtist, $Artists)) {
        $CurrArtist = db_string($CurrArtist);

        $app->dbOld->prepared_query("
        SELECT
          `ArtistID`
        FROM
          `artists_group`
        WHERE
          `Name` = '$CurrArtist'
        ");


        if ($app->dbOld->has_results()) {
            list($ArtistID) = $app->dbOld->next_record();

            $app->dbOld->prepared_query("
            DELETE
            FROM
              `torrents_artists`
            WHERE
              `ArtistID` = '$ArtistID'
              AND `GroupID` = '$group_id'
            ");


            $app->dbOld->prepared_query("
            SELECT
              `GroupID`
            FROM
              `torrents_artists`
            WHERE
              `ArtistID` = '$ArtistID'
            ");


            $app->cacheNew->delete('artist_groups_'.$ArtistID);

            if (!$app->dbOld->has_results()) {
                $app->dbOld->prepared_query("
                SELECT
                  `RequestID`
                FROM
                  `requests_artists`
                WHERE
                  `ArtistID` = '$ArtistID'
                  AND `ArtistID` != 0
                ");


                if (!$app->dbOld->has_results()) {
                    Artists::delete_artist($ArtistID);
                }
            }
        }
    }
}

$app->dbOld->prepared_query("
SELECT
  `ID`
FROM
  `torrents`
WHERE
  `GroupID` = '$group_id'
");


while (list($TorrentID) = $app->dbOld->next_record()) {
    $app->cacheNew->delete("torrent_download_$TorrentID");
}

Torrents::update_hash($group_id);
$app->cacheNew->delete("torrents_details_$group_id");
Http::redirect("torrents.php?id=$group_id");
