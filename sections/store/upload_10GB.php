<?
$Purchase = "10GiB of upload";
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
          Uploaded    = Uploaded + 10737418240
      WHERE ID = $UserID");
    $DB->query("
      UPDATE users_info
      SET AdminComment = CONCAT('".sqltime()." - Purchased 10GiB upload from the store\n\n', AdminComment)
      WHERE UserID = $UserID");
    $Cache->delete_value('user_info_heavy_'.$UserID);
    $Cache->delete_value('user_stats_'.$UserID);
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
