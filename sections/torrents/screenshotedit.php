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
    WHERE GroupID = ?", $GroupID);
  if (!in_array($LoggedUser['ID'], $DB->collect('UserID'))) {
    error(403);
  }
}

$Screenshots = $_POST['screenshots'] ?? [];
$Screenshots = array_map("trim", $Screenshots);
$Screenshots = array_filter($Screenshots, function($s) {
  return preg_match('/^'.IMAGE_REGEX.'$/i', $s);
});
$Screenshots = array_unique($Screenshots);

if (count($Screenshots) > 10) {
  error("You cannot add more than 10 screenshots to a group");
}

$DB->query("
  SELECT UserID, Image
  FROM torrents_screenshots
  WHERE GroupID = ?", $GroupID);

// $Old is an array of the form URL => UserID where UserID is the ID of the User who originally uploaded that image.
$Old = [];
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
  if (check_perms('screenshots_delete') || check_perms('torrents_edit')) {
    $DeleteList = $Deleted;
  } else {
    $DeleteList = [];
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
    $ScreenDel = '';
    $DB->prepare_query("DELETE FROM torrents_screenshots WHERE Image = ?", $ScreenDel);
    foreach ($DeleteList as $ScreenDel) {
      $DB->exec_prepared_query();
    }

    Torrents::write_group_log($GroupID, 0, $LoggedUser['ID'], "Deleted screenshot(s) ".implode(' , ', $DeleteList), 0);
    Misc::write_log("Screenshots ( ".implode(' , ', $DeleteList)." ) deleted from Torrent Group ".$GroupID." by ".$LoggedUser['Username']);
  }
}

// New screenshots
if (!empty($New)) {
  $Screenshot = '';
  $DB->prepare_query("
    INSERT INTO torrents_screenshots
      (GroupID, UserID, Time, Image)
    VALUES
      (?, ?, NOW(), ?)",
    $GroupID, $LoggedUser['ID'], $Screenshot);
  foreach ($New as $Screenshot) {
    $DB->exec_prepared_query();
  }

  Torrents::write_group_log($GroupID, 0, $LoggedUser['ID'], "Added screenshot(s) ".implode(' , ', $New), 0);
  Misc::write_log("Screenshots ( ".implode(' , ', $New)." ) added to Torrent Group ".$GroupID." by ".$LoggedUser['Username']);
}

$Cache->delete_value("torrents_details_".$GroupID);
header("Location: torrents.php?id=$GroupID");

?>
