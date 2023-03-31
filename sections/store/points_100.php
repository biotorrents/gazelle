<?php
declare(strict_types=1);

$app = \Gazelle\App::go();

$ENV = ENV::go();

$UserID = $app->userNew->core['id'];
$Purchase = "1,000 $ENV->bonusPoints";

$GiB = 1024 * 1024 * 1024;
$Cost = 15.0 * $GiB;

$app->dbOld->prepared_query("
  SELECT Uploaded
  FROM users_main
  WHERE ID = $UserID");

if ($app->dbOld->has_results()) {
    list($Upload) = $app->dbOld->next_record();

    if ($Upload >= $Cost) {
        $app->dbOld->prepared_query("
          UPDATE users_main
          SET BonusPoints = BonusPoints + 1000,
            Uploaded = Uploaded - $Cost
          WHERE ID = $UserID");

        $app->dbOld->prepared_query("
          UPDATE users_info
          SET AdminComment = CONCAT('".sqltime()." - $Purchase from the store\n\n', AdminComment)
          WHERE UserID = $UserID");

        $app->cacheOld->delete_value('user_info_heavy_'.$UserID);
        $app->cacheOld->delete_value('user_stats_'.$UserID);
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
