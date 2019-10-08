<?
//------------- Demote users --------------------------------------------//

// Disabled in favor of store-based promotions
/*
$Query = $DB->query('
  SELECT ID
  FROM users_main
  WHERE PermissionID IN('.POWER.', '.ELITE.', '.TORRENT_MASTER.')
    AND Uploaded / Downloaded < 0.95
    OR PermissionID IN('.POWER.', '.ELITE.', '.TORRENT_MASTER.')
    AND Uploaded < 25 * 1024 * 1024 * 1024');
echo "demoted 1\n";

$DB->query('
  UPDATE users_main
  SET PermissionID = '.MEMBER.'
  WHERE PermissionID IN('.POWER.', '.ELITE.', '.TORRENT_MASTER.')
    AND Uploaded / Downloaded < 0.95
    OR PermissionID IN('.POWER.', '.ELITE.', '.TORRENT_MASTER.')
    AND Uploaded < 25 * 1024 * 1024 * 1024');
$DB->set_query_id($Query);
while (list($UserID) = $DB->next_record()) {*/
  /*$Cache->begin_transaction("user_info_$UserID");
  $Cache->update_row(false, array('PermissionID' => MEMBER));
  $Cache->commit_transaction(2592000);*/
  /*$Cache->delete_value("user_info_$UserID");
  $Cache->delete_value("user_info_heavy_$UserID");
  Misc::send_pm($UserID, 0, 'You have been demoted to '.Users::make_class_string(MEMBER), "You now only meet the requirements for the \"".Users::make_class_string(MEMBER)."\" user class.\n\nTo read more about ".SITE_NAME."'s user classes, read [url=".site_url()."wiki.php?action=article&amp;name=userclasses]this wiki article[/url].");
}
echo "demoted 2\n";

$Query = $DB->query('
  SELECT ID
  FROM users_main
  WHERE PermissionID IN('.MEMBER.', '.POWER.', '.ELITE.', '.TORRENT_MASTER.')
    AND Uploaded / Downloaded < 0.65');
echo "demoted 3\n";
$DB->query('
  UPDATE users_main
  SET PermissionID = '.USER.'
  WHERE PermissionID IN('.MEMBER.', '.POWER.', '.ELITE.', '.TORRENT_MASTER.')
    AND Uploaded / Downloaded < 0.65');
$DB->set_query_id($Query);
while (list($UserID) = $DB->next_record()) {*/
  /*$Cache->begin_transaction("user_info_$UserID");
  $Cache->update_row(false, array('PermissionID' => USER));
  $Cache->commit_transaction(2592000);*/
  /*$Cache->delete_value("user_info_$UserID");
  $Cache->delete_value("user_info_heavy_$UserID");
  Misc::send_pm($UserID, 0, 'You have been demoted to '.Users::make_class_string(USER), "You now only meet the requirements for the \"".Users::make_class_string(USER)."\" user class.\n\nTo read more about ".SITE_NAME."'s user classes, read [url=".site_url()."wiki.php?action=article&amp;name=userclasses]this wiki article[/url].");
}
echo "demoted 4\n";
*/
?>
