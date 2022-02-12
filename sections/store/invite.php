<?php
#declare(strict_types=1);

$Cost = 10000;

$Purchase = "1 invite";
$UserID = $LoggedUser['ID'];

$DB->prepared_query("
  SELECT BonusPoints
  FROM users_main
  WHERE ID = $UserID");

if ($DB->has_results()) {
    list($Points) = $DB->next_record();

    if ($Points >= $Cost) {
        $DB->prepared_query("
          UPDATE users_main
          SET BonusPoints = BonusPoints - $Cost,
            Invites = Invites + 1
          WHERE ID = $UserID");

        $DB->prepared_query("
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
