<?php
declare(strict_types=1);

$ENV = ENV::go();

$UserID = $LoggedUser['ID'];
$Purchase = "10 $ENV->BONUS_POINTS";

$GiB = 1024 * 1024 * 1024;
$Cost = intval(0.15 * $GiB);

$DB->prepared_query("
  SELECT Uploaded
  FROM users_main
  WHERE ID = $UserID");
  
if ($DB->has_results()) {
    list($Upload) = $DB->next_record();

    if ($Upload >= $Cost) {
        $DB->prepared_query("
          UPDATE users_main
          SET BonusPoints = BonusPoints + 10,
            Uploaded = Uploaded - $Cost
          WHERE ID = $UserID");

        $DB->prepared_query("
          UPDATE users_info
          SET AdminComment = CONCAT('".sqltime()." - $Purchase from the store\n\n', AdminComment)
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
<div>
  <h2>Purchase
    <?= $Worked?"Successful":"Failed"?>
  </h2>
  <div class="box">
    <p>
      <?= $Worked?("You purchased ".$Purchase):("Error: ".$ErrMessage)?>
    </p>
    <p>
      <a href="/store.php">Back to Store</a>
    </p>
  </div>
</div>
<?php View::show_footer();
