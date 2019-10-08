<?
$Purchase = "1 invite";
$UserID = $LoggedUser['ID'];

$DB->query("
  SELECT BonusPoints
  FROM users_main
  WHERE ID = $UserID");
if ($DB->has_results()) {
  list($Points) = $DB->next_record();

  if ($Points >= 100000) {
    $DB->query("
      UPDATE users_main
      SET BonusPoints = BonusPoints - 100000,
          Invites     = Invites + 1
      WHERE ID = $UserID");
    $DB->query("
      UPDATE users_info
      SET AdminComment = CONCAT('".sqltime()." - Purchased an invite from the store\n\n', AdminComment)
      WHERE UserID = $UserID");
    $Cache->delete_value('user_info_heavy_'.$UserID);
    $Worked = true;
  } else {
    $Worked = false;
    $ErrMessage = "Not enough points";
  }
}

View::show_header('Store'); ?>
<div class="thin">
  <h2 id="general">Purchase <?print $Worked?"Successful":"Failed"?></h2>
  <div class="box pad" style="padding: 10px 10px 10px 20px;">
    <p><?print $Worked?("You purchased ".$Purchase):("Error: ".$ErrMessage)?></p>
    <p><a href="/store.php">Back to Store</a></p>
  </div>
</div>
<? View::show_footer(); ?>
