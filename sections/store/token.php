<?php
#declare(strict_types=1);

$Cost = 1000;

$Purchase = "1 freeleech token";
$UserID = $user['ID'];

$db->prepared_query("
  SELECT BonusPoints
  FROM users_main
  WHERE ID = $UserID");

if ($db->has_results()) {
    list($Points) = $db->next_record();

    if ($Points >= $Cost) {
        $db->prepared_query("
          UPDATE users_main
          SET BonusPoints = BonusPoints - $Cost,
            FLTokens = FLTokens + 1
          WHERE ID = $UserID");

        $db->prepared_query("
          UPDATE users_info
          SET AdminComment = CONCAT('".sqltime()." - Purchased a freeleech token from the store\n\n', AdminComment)
          WHERE UserID = $UserID");

        $cache->delete_value('user_info_heavy_'.$UserID);
        $Worked = true;
    } else {
        $Worked = false;
        $ErrMessage = "Not enough ".BONUS_POINTS.".";
    }
}

View::header('Store'); ?>
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
<?php
View::footer();
