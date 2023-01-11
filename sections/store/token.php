<?php
#declare(strict_types=1);

$app = App::go();

$Cost = 1000;

$Purchase = "1 freeleech token";
$UserID = $app->userNew->core['id'];

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
            FLTokens = FLTokens + 1
          WHERE ID = $UserID");

        $app->dbOld->prepared_query("
          UPDATE users_info
          SET AdminComment = CONCAT('".sqltime()." - Purchased a freeleech token from the store\n\n', AdminComment)
          WHERE UserID = $UserID");

        $app->cacheOld->delete_value('user_info_heavy_'.$UserID);
        $Worked = true;
    } else {
        $Worked = false;
        $ErrMessage = "Not enough ".bonusPoints.".";
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
<?php
View::footer();
