<?
$Purchase = "1,000,000 bonus points";
$UserID = $LoggedUser['ID'];

$GiB = 1024*1024*1024;

$DB->query("
  SELECT Uploaded
  FROM users_main
  WHERE ID = $UserID");
if ($DB->has_results()) {
  list($Upload) = $DB->next_record();

  if ($Upload >= 1000*$GiB) {
    $DB->query("
      UPDATE users_main
      SET BonusPoints = BonusPoints + 1000000,
          Uploaded    = Uploaded - ".(1000*$GiB)."
      WHERE ID = $UserID");
    $DB->query("
      UPDATE users_info
      SET AdminComment = CONCAT('".sqltime()." - Purchased 1,000,000 ".BONUS_POINTS."s from the store\n\n', AdminComment)
      WHERE UserID = $UserID");
    $Cache->delete_value('user_info_heavy_'.$UserID);
    $Cache->delete_value('user_stats_'.$UserID);
    $Worked = true;
  } else {
    $Worked = false;
    $ErrMessage = "Not enough upload";
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
