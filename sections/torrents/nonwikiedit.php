<?php
declare(strict_types=1);

authorize();

$group_id = (int) $_REQUEST['groupid'];
Security::int($group_id);

// Usual perm checks
if (!check_perms('torrents_edit')) {
    $db->prepared_query("
    SELECT
      `UserID`
    FROM
      `torrents`
    WHERE
      `GroupID` = '$GroupID'
    ");


    if (!in_array($user['ID'], $db->collect('UserID'))) {
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
$db->prepared_query("
SELECT
  `year`
FROM
  `torrents_group`
WHERE
  `id` = '$group_id'
");

list($OldYear) = $db->next_record();

$db->prepared_query("
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

    $db->prepared_query("
    INSERT INTO `group_log`(`GroupID`, `UserID`, `Time`, `Info`)
    VALUES(
      '$group_id',
      '$user[ID]',
      NOW(),
      '$Message')
    ");

}

$db->prepared_query("
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


while ($r = $db->next_record(MYSQLI_ASSOC, true)) {
    $CurrArtists[] = $r['Name'];
}

foreach ($Artists as $Artist) {
    if (!in_array($Artist, $CurrArtists)) {
        $Artist = db_string($Artist);
        $db->prepared_query("
        SELECT
          `ArtistID`
        FROM
          `artists_group`
        WHERE
          `Name` = '$Artist'
        ");


        if ($db->has_results()) {
            list($ArtistID) = $db->next_record();
        } else {
            $db->prepared_query("
            INSERT INTO `artists_group`(`Name`)
            VALUES('$Artist')
            ");

            $ArtistID = $db->inserted_id();
        }

        $db->prepared_query("
        INSERT INTO `torrents_artists`(`GroupID`, `ArtistID`, `UserID`)
        VALUES(
          '$group_id',
          '$ArtistID',
          '$user[ID]'
        )
        ON DUPLICATE KEY
        UPDATE
          `UserID` = '$user[ID]'
        "); // Why does this even happen

        $cache->delete_value('artist_groups_'.$ArtistID);
    }
}

foreach ($CurrArtists as $CurrArtist) {
    if (!in_array($CurrArtist, $Artists)) {
        $CurrArtist = db_string($CurrArtist);

        $db->prepared_query("
        SELECT
          `ArtistID`
        FROM
          `artists_group`
        WHERE
          `Name` = '$CurrArtist'
        ");


        if ($db->has_results()) {
            list($ArtistID) = $db->next_record();

            $db->prepared_query("
            DELETE
            FROM
              `torrents_artists`
            WHERE
              `ArtistID` = '$ArtistID'
              AND `GroupID` = '$group_id'
            ");


            $db->prepared_query("
            SELECT
              `GroupID`
            FROM
              `torrents_artists`
            WHERE
              `ArtistID` = '$ArtistID'
            ");


            $cache->delete_value('artist_groups_'.$ArtistID);

            if (!$db->has_results()) {
                $db->prepared_query("
                SELECT
                  `RequestID`
                FROM
                  `requests_artists`
                WHERE
                  `ArtistID` = '$ArtistID'
                  AND `ArtistID` != 0
                ");


                if (!$db->has_results()) {
                    Artists::delete_artist($ArtistID);
                }
            }
        }
    }
}

$db->prepared_query("
SELECT
  `ID`
FROM
  `torrents`
WHERE
  `GroupID` = '$group_id'
");


while (list($TorrentID) = $db->next_record()) {
    $cache->delete_value("torrent_download_$TorrentID");
}

Torrents::update_hash($group_id);
$cache->delete_value("torrents_details_$group_id");
Http::redirect("torrents.php?id=$group_id");
