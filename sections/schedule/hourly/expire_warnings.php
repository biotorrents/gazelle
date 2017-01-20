<?
//------------- Remove expired warnings ---------------------------------//

$DB->query("
  SELECT UserID
  FROM users_info
  WHERE Warned < '$sqltime'");
while (list($UserID) = $DB->next_record()) {
  $Cache->begin_transaction("user_info_$UserID");
  $Cache->update_row(false, array('Warned' => NULL));
  $Cache->commit_transaction(2592000);
}

$DB->query("
  UPDATE users_info
  SET Warned IS NULL
  WHERE Warned < '$sqltime'");
?>
