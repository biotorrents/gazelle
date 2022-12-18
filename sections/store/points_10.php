<?php
declare(strict_types=1);

$ENV = ENV::go();

$UserID = $user['ID'];
$Purchase = "100 $ENV->BONUS_POINTS";

$GiB = 1024 * 1024 * 1024;
$Cost = 1.5 * $GiB;

$db->prepared_query("
  SELECT Uploaded
  FROM users_main
  WHERE ID = $UserID");

if ($db->has_results()) {
    list($Upload) = $db->next_record();

    if ($Upload >= $Cost) {
        $db->prepared_query("
          UPDATE users_main
          SET BonusPoints = BonusPoints + 100,
            Uploaded = Uploaded - $Cost
          WHERE ID = $UserID");

        $db->prepared_query("
          UPDATE users_info
          SET AdminComment = CONCAT('".sqltime()." - $Purchase from the store\n\n', AdminComment)
          WHERE UserID = $UserID");

        $cache->delete_value('user_info_heavy_'.$UserID);
        $cache->delete_value('user_stats_'.$UserID);
        $Worked = true;
    } else {
        $Worked = false;
        $ErrMessage = "Not enough upload";
    }
}

View::header('Store'); ?>
<div>
  <h2>Purchase
    <?= $Worked ? "Successful" : "Failed"?>
  </h2>
  <div class="box">
    <p>
      <?= $Worked ? ("You purchased ".$Purchase) : ("Error: ".$ErrMessage)?>
    </p>
    <p>
      <a href="/store.php">Back to Store</a>
    </p>
  </div>
</div>
<?php View::footer();
