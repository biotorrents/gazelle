<?

authorize();

if (!$_POST['groupid'] || !is_number($_POST['groupid'])) {
  error(404);
}
$GroupID = $_POST['groupid'];

if (!check_perms('torrents_edit') && !check_perms('screenshots_add') && !check_perms('screenshots_delete')) {
  $DB->query("
    SELECT UserID
    FROM torrents
    WHERE GroupID = $GroupID");
  if (!in_array($LoggedUser['ID'], $DB->collect('UserID'))) {
    error(403);
  }
}

$Screenshots = isset($_POST['screenshots']) ? $_POST['screenshots'] : array();

if (count($Screenshots) > 10) {
  error(0);
}

$ScreenshotsEscaped = array();

foreach ($Screenshots as $i => $Screenshot) {
  if (!preg_match('/^'.IMAGE_REGEX.'$/i', trim($Screenshot)))
    error(0);
  $Screenshots[$i] = db_string(trim($Screenshot));
}

$DB->query("
  SELECT UserID, Image
  FROM torrents_screenshots
  WHERE GroupID = $GroupID");

// $Old is an array of the form URL => UserID where UserID is the ID of the User who originally uploaded that image.
$Old = array();
if ($DB->has_results()) {
  while($S = $DB->next_record(MYSQLI_ASSOC)) {
    $Old[$S['Image']] = $S['UserID'];
  }
}

if (!empty($Old)) {
  $New = array_diff($Screenshots, array_keys($Old));
  $Deleted = array_diff(array_keys($Old), $Screenshots);
} else {
  $New = $Screenshots;
}

// Deletion
if (!empty($Deleted)) {
  $sql = "DELETE FROM torrents_screenshots WHERE Image IN ('";

  if (check_perms('screenshots_delete') || check_perms('torrents_edit')) {
    $DeleteList = $Deleted;
  } else {
    $DeleteList = array();
    foreach ($Deleted as $S) {
      // If the user who submitted this request uploaded the image, add the image to the list.
      if ($Old[$S] == $LoggedUser['ID']) {
        $DeleteList[] = $S;
      } else {
        error(403);
      }
    }
  }

  if (!empty($DeleteList)) {
    $sql .= implode("', '", $DeleteList) . "')";
    $DB->query($sql);
  }

}

// New screenshots
foreach ($New as $Screenshot) {
  $DB->query("
    INSERT INTO torrents_screenshots
      (GroupID, UserID, Time, Image)
    VALUES
      ($GroupID, $LoggedUser[ID], '".sqltime()."', '$Screenshot')");
}

if (!empty($New)) {
  Torrents::write_group_log($GroupID, 0, $LoggedUser['ID'], "Added screenshot(s) ".implode(' , ', $New), 0);
  Misc::write_log("Screenshots ( ".implode(' , ', $New)." ) added to Torrent Group ".$GroupID." by ".$LoggedUser['Username']);
}
if (!empty($DeleteList)) {
  Torrents::write_group_log($GroupID, 0, $LoggedUser['ID'], "Deleted screenshot(s) ".implode(' , ', $DeleteList), 0);
  Misc::write_log("Screenshots ( ".implode(' , ', $DeleteList)." ) deleted from Torrent Group ".$GroupID." by ".$LoggedUser['Username']);
}

$Cache->delete_value("torrents_details_".$GroupID);
header("Location: torrents.php?id=$GroupID");

?>
