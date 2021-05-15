<?php
#declare(strict_types=1);

$Purchase = "Admin status";
$UserID = $LoggedUser['ID'];

$DB->query("
  SELECT BonusPoints
  FROM users_main
  WHERE ID = $UserID");
  
if ($DB->has_results()) {
    list($Points) = $DB->next_record();

    if ($Points >= 4294967296) {
        $DB->query("
          UPDATE users_main
          SET BonusPoints  = BonusPoints - 4294967296,
            PermissionID = 15
          WHERE ID = $UserID");
        $Worked = true;
    } else {
        $Worked = false;
        $ErrMessage = "Not enough points";
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
<?php
View::show_footer();
