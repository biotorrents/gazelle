<?php
#declare(strict_types=1);

if (isset($_POST['target']) && isset($_POST['amount'])) {
    $TargetID = abs(intval($_POST['target']));
    $Amount = abs(intval($_POST['amount']));
    $UserID = $LoggedUser['ID'];

    $DB->query("
      SELECT u.BonusPoints, p.Level
      FROM users_main AS u
      LEFT JOIN permissions AS p ON u.PermissionID=p.ID
      WHERE u.ID = $UserID");

    if ($DB->has_results()) {
        list($Points, $PLevel) = $DB->next_record();

        if ($Points < $Amount) {
            error('Not enough points!');
        }

        if ($UserID == $TargetID) {
            error("You can't capture yourself!");
        }

        if ($PLevel < 200) {
            error('Insufficient class');
        }

        $DB->query("SELECT COUNT(*) FROM slaves WHERE OwnerID = $UserID");
        if ($DB->next_record()[0] >= 6) {
            error('You own too many users already');
        }

        // Logic for capture success
        $DB->query("
          SELECT u.Uploaded,
            u.Downloaded,
            u.BonusPoints,
            COUNT(t.UserID)
          FROM users_main AS u
          LEFT JOIN torrents AS t ON u.ID=t.UserID
          WHERE u.ID = $TargetID");

        if (!$DB->has_results()) {
            error('User does not exist');
        }

        list($Upload, $Download, $Points, $Uploads) = $DB->next_record();
        $AdjLevel = intval(((($Uploads**0.35)*1.5)+1) * max(($Upload+($Points*1000000)-$Download)/(1024**3), 1) * 1000);

        if ($Amount <= $AdjLevel) {
            error('You need to spend more points to have any chance of catching this user!');
        }

        $Captured = (rand(0, $Amount) >= $AdjLevel);
        $DB->query("
          UPDATE users_main
          SET BonusPoints = BonusPoints - $Amount
          WHERE ID = $UserID");
        $Cache->delete_value('user_info_heavy_'.$UserID);

        if ($Captured) {
            $DB->query("
              INSERT INTO slaves
                (UserID, OwnerID)
              Values($TargetID, $UserID)");
        }
    }

    View::show_header('Store'); ?>
<div>
  <h2>Capture <?=($Captured?'Successful':'Failed')?>
  </h2>
  <div class="box">
    <p>
      <?=($Captured?'You successfully captured your target':'Your target eluded capture')?>
    </p>
    <p>
      <a href="/store.php">Back to Store</a>
      | <a href="/user.php?id=<?=$TargetID?>">Back to Profile</a>
    </p>
  </div>
</div>
<?php View::show_footer();
} else {
    View::show_header('Store'); ?>
<div>
  <div class="box text-align: center;">

    <form action="store.php" method="POST">
      <input type="hidden" name="item" value="capture_user">
      <strong>
        Enter the name of the user you want to capture and the <?=BONUS_POINTS?> you want to spend
      </strong>
      <br />
      <input type="text" name="target_name" placeholder="Username">
      <input type="text" name="amount"
        placeholder="<?=BONUS_POINTS?>">
      <input type="submit">
    </form>

    <p>
      <a href="/store.php">Back to Store</a>
    </p>
  </div>
</div>
<?php
View::show_footer();
}
