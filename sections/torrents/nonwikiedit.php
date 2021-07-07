<?php
declare(strict_types=1);

authorize();

$GroupID = (int) $_GET['groupid'];
Security::checkInt($GroupID);

// Usual perm checks
if (!check_perms('torrents_edit')) {
    $DB->query("
    SELECT
      `UserID`
    FROM
      `torrents`
    WHERE
      `GroupID` = '$group_id'
    ");

    if (!in_array($LoggedUser['ID'], $DB->collect('UserID'))) {
        error(403);
    }
}

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
$published = db_string((int)$_POST['year']);
$identifier = db_string($_POST['catalogue']);

// Get some info for the group log
$DB->query("
SELECT
  `published`
FROM
  `torrents_group`
WHERE
  `id` = '$group_id'
");
list($OldYear) = $DB->next_record();

$DB->query("
UPDATE
  `torrents_group`
SET
  `published` = '$published',
  `identifier` = '$identifier',
  `workgroup` = '$workgroup',
  `location` = '$location'
WHERE
  `id` = '$group_id'
");

if ($OldYear !== $published) {
    $Message = db_string("Year changed from $OldYear to $published");

    $DB->query("
    INSERT INTO `group_log`(`GroupID`, `UserID`, `Time`, `Info`)
    VALUES(
      '$group_id',
      '$LoggedUser[ID]',
      NOW(),
      '$Message')
    ");
}

$DB->query("
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

while ($r = $DB->next_record(MYSQLI_ASSOC, true)) {
    $CurrArtists[] = $r['Name'];
}

foreach ($Artists as $Artist) {
    if (!in_array($Artist, $CurrArtists)) {
        $Artist = db_string($Artist);
        $DB->query("
        SELECT
          `ArtistID`
        FROM
          `artists_group`
        WHERE
          `Name` = '$Artist'
        ");

        if ($DB->has_results()) {
            list($ArtistID) = $DB->next_record();
        } else {
            $DB->query("
            INSERT INTO `artists_group`(`Name`)
            VALUES('$Artist')
            ");
            $ArtistID = $DB->inserted_id();
        }

        $DB->query("
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
        $Cache->delete_value('artist_groups_'.$ArtistID);
    }
}

foreach ($CurrArtists as $CurrArtist) {
    if (!in_array($CurrArtist, $Artists)) {
        $CurrArtist = db_string($CurrArtist);

        $DB->query("
        SELECT
          `ArtistID`
        FROM
          `artists_group`
        WHERE
          `Name` = '$CurrArtist'
        ");

        if ($DB->has_results()) {
            list($ArtistID) = $DB->next_record();

            $DB->query("
            DELETE
            FROM
              `torrents_artists`
            WHERE
              `ArtistID` = '$ArtistID'
              AND `GroupID` = '$group_id'
            ");

            $DB->query("
            SELECT
              `GroupID`
            FROM
              `torrents_artists`
            WHERE
              `ArtistID` = '$ArtistID'
            ");

            $Cache->delete_value('artist_groups_'.$ArtistID);

            if (!$DB->has_results()) {
                $DB->query("
                SELECT
                  `RequestID`
                FROM
                  `requests_artists`
                WHERE
                  `ArtistID` = '$ArtistID'
                  AND `ArtistID` != 0
                ");

                if (!$DB->has_results()) {
                    Artists::delete_artist($ArtistID);
                }
            }
        }
    }
}

$DB->query("
SELECT
  `ID`
FROM
  `torrents`
WHERE
  `GroupID` = '$group_id'
");

while (list($TorrentID) = $DB->next_record()) {
    $Cache->delete_value("torrent_download_$TorrentID");
}

Torrents::update_hash($group_id);
$Cache->delete_value("torrents_details_$group_id");
header("Location: torrents.php?id=$group_id");
