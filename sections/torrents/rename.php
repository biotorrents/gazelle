<?
authorize();

$GroupID = $_POST['groupid'];
$OldGroupID = $GroupID;
$NewName = $_POST['name'];
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

if (empty($NewName) || empty($NewJP)) {
	error('Torrent groups must have a name');
}

if (!($Contributed || check_perms('torrents_edit'))) {
	error(403);
}

$DB->query("
	SELECT Name
	FROM torrents_group
	WHERE ID = $GroupID");
list($OldName) = $DB->next_record(MYSQLI_NUM, false);

$DB->query("
	UPDATE torrents_group
	SET Name = '".db_string($NewName)."',
	NameJP = '".db_string($NewJP)."'
	WHERE ID = '$GroupID'");
$Cache->delete_value("torrents_details_$GroupID");

Torrents::update_hash($GroupID);

Misc::write_log("Torrent Group $GroupID ($OldName) was renamed to \"$NewName\" from \"$OldName\" by ".$LoggedUser['Username']);
Torrents::write_group_log($GroupID, 0, $LoggedUser['ID'], "renamed to \"$NewName\" from \"$OldName\"", 0);

header("Location: torrents.php?id=$GroupID");
