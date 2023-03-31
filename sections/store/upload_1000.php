<?php
declare(strict_types=1);

$app = \Gazelle\App::go();

$UserID = $app->userNew->core['id'];
$Purchase = "100 GiB upload";

$GiB = 1024*1024*1024;
$Cost = 15000;

$app->dbOld->prepared_query("
  SELECT BonusPoints
  FROM users_main
  WHERE ID = $UserID");

if ($app->dbOld->has_results()) {
    list($Points) = $app->dbOld->next_record();

    if ($Points >= $Cost) {
        $app->dbOld->prepared_query("
          UPDATE users_main
          SET BonusPoints = BonusPoints - $Cost,
            Uploaded = Uploaded + ($GiB * 100)
          WHERE ID = $UserID");

        $app->dbOld->prepared_query("
          UPDATE users_info
          SET AdminComment = CONCAT('".sqltime()." - $Purchase from the store\n\n', AdminComment)
          WHERE UserID = $UserID");

        $app->cacheNew->delete('user_info_heavy_'.$UserID);
        $app->cacheNew->delete('user_stats_'.$UserID);
        $Worked = true;
    } else {
        $Worked = false;
        $ErrMessage = "Not enough points";
    }
}

View::header('Store'); ?>
<div>
  <h2>Purchase
    <?echo $Worked ? "Successful" : "Failed"?>
  </h2>
  <div class="box">
    <p>
      <?echo $Worked ? ("You purchased ".$Purchase) : ("Error: ".$ErrMessage)?>
    </p>
    <p>
      <a href="/store.php">Back to Store</a>
    </p>
  </div>
</div>
<?php View::footer();
