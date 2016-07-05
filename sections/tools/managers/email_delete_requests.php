<?

if (!check_perms('users_mod')) {
  error(403);
}

$QueryID = $DB->query("
  SELECT SQL_CALC_FOUND_ROWS *
  FROM email_delete_requests");

$DB->query("SELECT FOUND_ROWS()");
list($NumResults) = $DB->next_record();
$DB->set_query_id($QueryID);

$Requests = $DB->to_array();

if (isset($_GET['deny']) && isset($_GET['email'])) {
  authorize();

  $Deny = ($_GET['deny'] == "true");
  $Email = db_string($_GET['email']);

  $DB->query("
    DELETE FROM email_delete_requests
    WHERE Email = '$Email'");

  if (!$Deny) {
    $DB->query("
      SELECT UserID
      FROM users_history_emails
      WHERE Email = '$Email'");

    if (!$DB->has_results()) {
      $Err = "That email doesn't exist.";
    } else {
      list($UserID) = $DB->next_record();
      if ($UserID != $_GET['userid']) {
        $Err = "The UserID is incorrect?";
      } else {
        $DB->query("
          SELECT Email
          FROM users_history_emails
          WHERE UserID = '$UserID'");
        $ToDelete = array();
        while (list($EncEmail) = $DB->next_record()) {
          if (DBCrypt::decrypt($Email) == DBCrypt::decrypt($EncEmail)) {
            $ToDelete[] = $EncEmail;
          }
        }
        forEach ($ToDelete as $DelEmail) {
          $DB->query("
            DELETE FROM users_history_emails
            WHERE UserID = $UserID
              AND Email = '$DelEmail'");
        }
        $Succ = "Email deleted.";
        Misc::send_pm($UserID, 0, "Email Deletion Request Accepted.", "Your email deletion request has been accepted. What email? I don't know! We don't have it anymore!");
      }
    }
  } else {
    $Succ = "Request denied.";
    Misc::send_pm($UserID, 0, "Email Deletion Request Denied.", "Your email deletion request has been denied.\n\nIf you wish to discuss this matter further, please create a staff PM, or join #oppaitime-help on IRC to speak with a staff member.");
  }

  $Cache->delete_value('num_email_delete_requests');
}

View::show_header("Email Deletion Requests");

?>

<div class="header">
  <h2>Email Deletion Requests</h2>
</div>

<? if (isset($Err)) { ?>
<span>Error: <?=$Err?></span>
<? } elseif (isset($Succ)) { ?>
<span>Success: <?=$Succ?></span>
<? } ?>

<div class="thin">
  <table width="100%">
    <tr class="colhead">
      <td>User</td>
      <td>Email</td>
      <td>Reason</td>
      <td>Accept</td>
      <td>Deny</td>
    </tr>
<? foreach ($Requests as $Request) { ?>
    <tr>
      <td><?=Users::format_username($Request['UserID'])?></td>
      <td><?=DBCrypt::decrypt($Request['Email'])?></td>
      <td><?=display_str($Request['Reason'])?></td>
      <td><a href="tools.php?action=delete_email&auth=<?=$LoggedUser['AuthKey']?>&email=<?=urlencode($Request['Email'])?>&userid=<?=$Request['UserID']?>&deny=false" class="brackets">Accept</a></td>
      <td><a href="tools.php?action=delete_email&auth=<?=$LoggedUser['AuthKey']?>&email=<?=urlencode($Request['Email'])?>&userid=<?=$Request['UserID']?>&deny=true" class="brackets">Deny</a></td>
    </tr>
<? } ?>
  </table>
</div>

<? View::show_footer(); ?>
