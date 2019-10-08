<?
//------------- Delete dead torrents ------------------------------------//

$DB->query("
  SELECT
    t.ID,
    t.GroupID,
    tg.Name,
    t.UserID,
    t.Media,
    HEX(t.info_hash) AS InfoHash
  FROM torrents AS t
    JOIN torrents_group AS tg ON tg.ID = t.GroupID
  WHERE
    (t.last_action < (NOW() - INTERVAL 28 DAY) AND t.last_action IS NOT NULL)
    OR
    (t.Time < (NOW() - INTERVAL 2 DAY) AND t.last_action IS NULL)");
$Torrents = $DB->to_array(false, MYSQLI_NUM, false);
echo 'Found '.count($Torrents)." inactive torrents to be deleted.\n";

$LogEntries = $DeleteNotes = [];

// Exceptions for inactivity deletion
$InactivityExceptionsMade = []; //UserID => expiry time of exception

$i = 0;
foreach ($Torrents as $Torrent) {
  list($ID, $GroupID, $Name, $UserID, $Media, $InfoHash) = $Torrent;
  if (array_key_exists($UserID, $InactivityExceptionsMade) && (time() < $InactivityExceptionsMade[$UserID])) {
    // don't delete the torrent!
    continue;
  }
  $ArtistName = Artists::display_artists(Artists::get_artist($GroupID), false, false, false);
  if ($ArtistName) {
    $Name = "$ArtistName - $Name";
  }
  Torrents::delete_torrent($ID, $GroupID);
  $LogEntries[] = db_string("Torrent $ID ($Name) (".strtoupper($InfoHash).") was deleted for inactivity (unseeded)");

  if (!array_key_exists($UserID, $DeleteNotes)) {
    $DeleteNotes[$UserID] = array('Count' => 0, 'Msg' => '');
  }

  $DeleteNotes[$UserID]['Msg'] .= "\n$Name";
  $DeleteNotes[$UserID]['Count']++;

  ++$i;
  if ($i % 500 == 0) {
    echo "$i inactive torrents removed.\n";
  }
}
echo "$i torrents deleted for inactivity.\n";

foreach ($DeleteNotes as $UserID => $MessageInfo) {
  $Singular = ($MessageInfo['Count'] == 1);
  Misc::send_pm($UserID, 0, $MessageInfo['Count'].' of your torrents '.($Singular ? 'has' : 'have').' been deleted for inactivity', ($Singular ? 'One' : 'Some').' of your uploads '.($Singular ? 'has' : 'have').' been deleted for being unseeded. Since '.($Singular ? 'it' : 'they').' didn\'t break any rules (we hope), please feel free to re-upload '.($Singular ? 'it' : 'them').".\n\nThe following torrent".($Singular ? ' was' : 's were').' deleted:'.$MessageInfo['Msg']);
}
unset($DeleteNotes);

if (count($LogEntries) > 0) {
  $Values = "('".implode("', '$sqltime'), ('", $LogEntries) . "', '$sqltime')";
  $DB->query("
    INSERT INTO log (Message, Time)
    VALUES $Values");
  echo "\nDeleted $i torrents for inactivity\n";
}
?>
