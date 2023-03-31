<?php
#declare(strict_types=1);

$app = \Gazelle\App::go();

$UserID = $app->user->core['id'];
$app->dbOld->prepared_query("
  SELECT First, Second
  FROM misc
  WHERE Name='CoinBadge'");

if ($app->dbOld->has_results()) {
    list($Purchases, $Price) = $app->dbOld->next_record();
} else {
    $app->dbOld->prepared_query("
    INSERT INTO misc
      (Name, First, Second)
    VALUES ('CoinBadge', 0, 1000)");
    list($Purchases, $Price) = [0, 1000];
}

View::header('Store');
?>
<div>
  <?php
  if (isset($_GET['confirm'])
   && $_GET['confirm'] === 1
   && !Badges::hasBadge($UserID, 255)) {
      $app->dbOld->prepared_query("
      SELECT BonusPoints
      FROM users_main
      WHERE ID = $UserID");

      list($Points) = $app->dbOld->has_results() ? $app->dbOld->next_record() : [0];
      if ($Points > $Price) {
          if (!Badges::awardBadge($UserID, 255)) {
              $Err = 'Could not award badge, unknown error occurred.';
          } else {
              $app->dbOld->prepared_query("
              UPDATE users_main
              SET BonusPoints = BonusPoints - $Price
              WHERE ID = $UserID");

              $app->dbOld->prepared_query("
              UPDATE users_info
              SET AdminComment = CONCAT('".sqltime()." - Purchased badge 255 from store\n\n', AdminComment)
              WHERE UserID = $UserID");

              $app->cacheNew->delete("user_info_heavy_$UserID");
              // Calculate new badge values
              $Purchases += 1;
              $x = $Purchases;
              $Price = 1000+$x*(10000+1400*((sin($x/1.3)+cos($x/4.21))+(sin($x/2.6)+cos(2*$x/4.21))/2));

              $app->dbOld->prepared_query("
              UPDATE misc
              SET First  = $Purchases,
                Second = $Price
              WHERE Name = 'CoinBadge'");
          }
      } else {
          $Err = 'Not enough '.bonusPoints.'.';
      }

      if (isset($Err)) { ?>
  <h2>Purchase Failed</h2>
  <div class="box pad">
    <p>
      Error:
      <?=$Err?>
    </p>

    <p>
      Transaction aborted
    </p>

    <p>
      <a href='/store.php'>Back to Store</a>
    </p>
  </div>
  <?php
} else { ?>
  <h2>Purchase Successful</h2>
  <div class="box pad">
    <p>You bought the Oppaicoin badge</p>
    <p>This badge has been purchased <?=Text::float($Purchases)?>
      times and now costs <?=Text::float($Price)?> <?=bonusPoints?>.</p>
  </div>
  <?php } ?>
  <?php
  } else {
      if (Badges::hasBadge($UserID, 255)) {
          ?>
  <h2>Oppaicoin Status</h2>
  <?php
      } else {
          ?>
  <h2>Purchase Oppaicoin Badge?</h2>
  <?php
      } ?>
  <div class="box pad">
    <p><?=Text::float($Purchases)?> people have bought this badge
    </p>
    <p>Current cost: <?=Text::float($Price)?> <?=bonusPoints?>
    </p>
    <?php if (Badges::hasBadge($UserID, 255)) { ?>
    <p>You already own this badge</p>
    <?php } else { ?>

    <form action="store.php">
      <input type="hidden" name="item" value="coinbadge">
      <input type="hidden" name="confirm" value="1">
      <input type="submit" value="Purchase">
    </form>

    <?php } ?>
  </div>
  <?php
  } ?>
</div>
<?php
View::footer();
