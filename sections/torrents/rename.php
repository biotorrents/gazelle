<?
authorize();

$GroupID = $_POST['groupid'];
$OldGroupID = $GroupID;
$NewName = $_POST['name'];
$NewRJ = $_POST['namerj'];
$NewJP = $_POST['namejp'];

$DB->query("
  SELECT ID
  FROM torrents
  WHERE GroupID = " . db_string($GroupID) . "
    AND UserID = " . $LoggedUser['ID']);
$Contributed = $DB->has_results();

if (!$GroupID || !is_number($GroupID)) {
  error(404);
}

if (!($Contributed || check_perms('torrents_edit'))) {
  error(403);
}

if (empty($NewName) && empty($NewRJ) && empty($NewJP)) {
  error('Torrent groups must have a name');
}

$DB->query("
  UPDATE torrents_group
  SET Name = '".db_string($NewName)."',
  NameRJ = '".db_string($NewRJ)."',
  NameJP = '".db_string($NewJP)."'
  WHERE ID = '$GroupID'");
$Cache->delete_value("torrents_details_$GroupID");

Torrents::update_hash($GroupID);

$DB->query("
  SELECT Name, NameRJ, NameJP
  FROM torrents_group
  WHERE ID = $GroupID");
list($OldName, $OldRJ, $OldJP) = $DB->next_record(MYSQLI_NUM, false);

if ($OldName != $NewName) {
  Misc::write_log("Torrent Group $GroupID ($OldName)'s title was changed to \"$NewName\" from \"$OldName\" by ".$LoggedUser['Username']);
  Torrents::write_group_log($GroupID, 0, $LoggedUser['ID'], "title changed to \"$NewName\" from \"$OldName\"", 0);
}

if ($OldRJ != $NewRJ) {
  Misc::write_log("Torrent Group $GroupID ($OldRJ)'s romaji title was changed to \"$NewRJ\" from \"$OldRJ\" by ".$LoggedUser['Username']);
  Torrents::write_group_log($GroupID, 0, $LoggedUser['ID'], "romaji title changed to \"$NewRJ\" from \"$OldRJ\"", 0);
}

if ($OldJP != $NewJP) {
  Misc::write_log("Torrent Group $GroupID ($OldJP)'s japanese title was changed to \"$NewJP\" from \"$OldJP\" by ".$LoggedUser['Username']);
  Torrents::write_group_log($GroupID, 0, $LoggedUser['ID'], "japanese title changed to \"$NewJP\" from \"$OldJP\"", 0);
}

header("Location: torrents.php?id=$GroupID");
