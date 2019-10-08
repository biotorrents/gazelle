<?
$Purchase = "1 freeleech token";
$UserID = $LoggedUser['ID'];

$DB->query("
  SELECT BonusPoints
  FROM users_main
  WHERE ID = $UserID");
if ($DB->has_results()) {
  list($Points) = $DB->next_record();

  if ($Points >= 10000) {
    $DB->query("
      UPDATE users_main
      SET BonusPoints = BonusPoints - 10000,
          FLTokens    = FLTokens + 1
      WHERE ID = $UserID");
    $DB->query("
      UPDATE users_info
      SET AdminComment = CONCAT('".sqltime()." - Purchased a freeleech token from the store\n\n', AdminComment)
      WHERE UserID = $UserID");
    $Cache->delete_value('user_info_heavy_'.$UserID);
    $Worked = true;
  } else {
    $Worked = false;
    $ErrMessage = "Not enough ".BONUS_POINTS.".";
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
