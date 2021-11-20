<?php
declare(strict_types=1);

authorize();

$group_id = (int) $_REQUEST['groupid'];
Security::CheckInt($group_id);

// Usual perm checks
if (!check_perms('torrents_edit')) {
    $DB->prepare_query("
    SELECT
      `UserID`
    FROM
      `torrents`
    WHERE
      `GroupID` = '$GroupID'
    ");
    $DB->exec_prepared_query();

    if (!in_array($LoggedUser['ID'], $DB->collect('UserID'))) {
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
$DB->prepare_query("
SELECT
  `year`
FROM
  `torrents_group`
WHERE
  `id` = '$group_id'
");
$DB->exec_prepared_query();
list($OldYear) = $DB->next_record();

$DB->prepare_query("
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
$DB->exec_prepared_query();

if ($OldYear !== $year) {
    $Message = db_string("Year changed from $OldYear to $year");

    $DB->prepare_query("
    INSERT INTO `group_log`(`GroupID`, `UserID`, `Time`, `Info`)
    VALUES(
      '$group_id',
      '$LoggedUser[ID]',
      NOW(),
      '$Message')
    ");
    $DB->exec_prepared_query();
}

$DB->prepare_query("
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
$DB->exec_prepared_query();

while ($r = $DB->next_record(MYSQLI_ASSOC, true)) {
    $CurrArtists[] = $r['Name'];
}

foreach ($Artists as $Artist) {
    if (!in_array($Artist, $CurrArtists)) {
        $Artist = db_string($Artist);
        $DB->prepare_query("
        SELECT
          `ArtistID`
        FROM
          `artists_group`
        WHERE
          `Name` = '$Artist'
        ");
        $DB->exec_prepared_query();

        if ($DB->has_results()) {
            list($ArtistID) = $DB->next_record();
        } else {
            $DB->prepare_query("
            INSERT INTO `artists_group`(`Name`)
            VALUES('$Artist')
            ");
            $DB->exec_prepared_query();
            $ArtistID = $DB->inserted_id();
        }

        $DB->prepare_query("
        INSERT INTO `torrents_artists`(`GroupID`, `ArtistID`, `UserID`)
        VALUES(
          '$group_id',
          '$ArtistID',
          '$LoggedUser[ID]'
        )
        ON DUPLICATE KEY
        UPDATE
          `UserID` = '$LoggedUser[ID]'
        "); // Why does this even happen
        $DB->exec_prepared_query();
        $Cache->delete_value('artist_groups_'.$ArtistID);
    }
}

foreach ($CurrArtists as $CurrArtist) {
    if (!in_array($CurrArtist, $Artists)) {
        $CurrArtist = db_string($CurrArtist);

        $DB->prepare_query("
        SELECT
          `ArtistID`
        FROM
          `artists_group`
        WHERE
          `Name` = '$CurrArtist'
        ");
        $DB->exec_prepared_query();

        if ($DB->has_results()) {
            list($ArtistID) = $DB->next_record();

            $DB->prepare_query("
            DELETE
            FROM
              `torrents_artists`
            WHERE
              `ArtistID` = '$ArtistID'
              AND `GroupID` = '$group_id'
            ");
            $DB->exec_prepared_query();

            $DB->prepare_query("
            SELECT
              `GroupID`
            FROM
              `torrents_artists`
            WHERE
              `ArtistID` = '$ArtistID'
            ");
            $DB->exec_prepared_query();

            $Cache->delete_value('artist_groups_'.$ArtistID);

            if (!$DB->has_results()) {
                $DB->prepare_query("
                SELECT
                  `RequestID`
                FROM
                  `requests_artists`
                WHERE
                  `ArtistID` = '$ArtistID'
                  AND `ArtistID` != 0
                ");
                $DB->exec_prepared_query();

                if (!$DB->has_results()) {
                    Artists::delete_artist($ArtistID);
                }
            }
        }
    }
}

$DB->prepare_query("
SELECT
  `ID`
FROM
  `torrents`
WHERE
  `GroupID` = '$group_id'
");
$DB->exec_prepared_query();

while (list($TorrentID) = $DB->next_record()) {
    $Cache->delete_value("torrent_download_$TorrentID");
}

Torrents::update_hash($group_id);
$Cache->delete_value("torrents_details_$group_id");
header("Location: torrents.php?id=$group_id");
