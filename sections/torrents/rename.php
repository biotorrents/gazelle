<?php

authorize();

$GroupID = $_POST['groupid'];
$NewTitle1 = $_POST['name'];
$NewTitle2 = $_POST['Title2'];
$NewTitle3 = $_POST['namejp'];

$DB->query(
    "
SELECT
  `ID`
FROM
  `torrents`
WHERE
  `GroupID` = ".db_string($GroupID)."
  AND `UserID` = ".$LoggedUser['ID']
);
$Contributed = $DB->has_results();

if (!$GroupID || !is_number($GroupID)) {
    error(404);
}

if (!($Contributed || check_perms('torrents_edit'))) {
    error(403);
}

#if (empty($NewName) && empty($NewRJ) && empty($NewJP)) {
if (empty($NewTitle1)) { # Title2, Title3 optional
    error('Torrent groups must have a name');
}

$DB->query("
UPDATE
  `torrents_group`
SET
  `Name` = '".db_string($NewTitle1)."',
  `Title2` = '".db_string($NewTitle2)."',
  `NameJP` = '".db_string($NewTitle3)."'
WHERE
  `ID` = '$GroupID'
");
$Cache->delete_value("torrents_details_$GroupID");

Torrents::update_hash($GroupID);

$DB->query("
  SELECT `Name`, `Title2`, `NameJP`
  FROM `torrents_group`
  WHERE `ID` = '$GroupID'
");
list($OldName, $OldRJ, $OldJP) = $DB->next_record(MYSQLI_NUM, false);

# Map metadata over generic database fields
# todo: Work into $ENV in classes/config.php
$Title1 = 'Title';
$Title2 = 'Organism';
$Title3 = 'Strain/Variety';

if ($OldName !== $NewTitle1) {
    Misc::write_log("Torrent Group $GroupID ($OldName)'s $Title1 was changed to '$NewTitle1' from '$OldName' by ".$LoggedUser['Username']);
    Torrents::write_group_log($GroupID, 0, $LoggedUser['ID'], "$Title1 changed to '$NewTitle1' from '$OldName'", 0);
}

if ($OldRJ !== $NewTitle2) {
    Misc::write_log("Torrent Group $GroupID ($OldRJ)'s $Title2 was changed to '$NewTitle2' from '$OldRJ' by ".$LoggedUser['Username']);
    Torrents::write_group_log($GroupID, 0, $LoggedUser['ID'], "$Title2 changed to '$NewTitle2' from '$OldRJ'", 0);
}

if ($OldJP !== $NewTitle3) {
    Misc::write_log("Torrent Group $GroupID ($OldJP)'s $Title3 was changed to '$NewTitle3' from '$OldJP' by ".$LoggedUser['Username']);
    Torrents::write_group_log($GroupID, 0, $LoggedUser['ID'], "$Title3 changed to '$NewTitle3' from '$OldJP'", 0);
}

header("Location: torrents.php?id=$GroupID");
