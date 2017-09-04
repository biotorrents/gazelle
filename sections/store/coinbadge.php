<?
$UserID = $LoggedUser['ID'];

$DB->query("
  SELECT First, Second
  FROM misc
  WHERE Name='CoinBadge'");
if ($DB->has_results()) {
  list($Purchases, $Price) = $DB->next_record();
} else {
  $DB->query("
    INSERT INTO misc
           (Name,        First, Second)
    VALUES ('CoinBadge', 0,     1000)");
  list($Purchases, $Price) = [0, 1000];
}

View::show_header('Store');
?>

<div class="thin">

<? if (isset($_GET['confirm']) && $_GET['confirm'] == 1 && !Badges::has_badge($UserID, 255)) {
  $DB->query("
    SELECT BonusPoints
    FROM users_main
    WHERE ID = $UserID");
  list($Points) = $DB->has_results() ? $DB->next_record() : [0];
  if ($Points > $Price) {
    if (!Badges::award_badge($UserID, 255)) {
      $Err = 'Could not award badge, unknown error occurred.';
    } else {
      $DB->query("
        UPDATE users_main
        SET BonusPoints = BonusPoints - $Price
        WHERE ID = $UserID");
      $DB->query("
        UPDATE users_info
        SET AdminComment = CONCAT('".sqltime()." - Purchased badge 255 from store\n\n', AdminComment)
        WHERE UserID = $UserID");
      $Cache->delete_value("user_info_heavy_$UserID");
      // Calculate new badge values
      $Purchases += 1;
      $x = $Purchases;
      $Price = 1000+$x*(10000+1400*((sin($x/1.3)+cos($x/4.21))+(sin($x/2.6)+cos(2*$x/4.21))/2));
      $DB->query("
        UPDATE misc
        SET First  = $Purchases,
            Second = $Price
        WHERE Name = 'CoinBadge'");
    }
  } else {
    $Err = 'Not enough '.BONUS_POINTS.'.';
  }

  if (isset($Err)) { ?>
    <h2 id="general">Purchase Failed</h2>
    <div class="box pad">
      <p>Error: <?=$Err?></p>
      <p>Transaction aborted</p>
      <p><a href='/store.php'>Back to Store</a></p>
    </div>
  <? } else { ?>
    <h2 id="general">Purchase Successful</h2>
    <div class="box pad">
      <p>You bought the Oppaicoin badge</p>
      <p>This badge has been purchased <?=number_format($Purchases)?> times and now costs <?=number_format($Price)?> <?=BONUS_POINTS?>.</p>
    </div>
<? } ?>
<?
} else {
  if (Badges::has_badge($UserID, 255)) {
?>
  <h2 id="general">Oppaicoin Status</h2>
<?
  } else {
?>
  <h2 id="general">Purchase Oppaicoin Badge?</h2>
<? } ?>
  <div class="box pad">
    <p><?=number_format($Purchases)?> people have bought this badge</p>
    <p>Current cost: <?=number_format($Price)?> <?=BONUS_POINTS?></p>
    <? if (Badges::has_badge($UserID, 255)) { ?>
    <p>You already own this badge</p>
    <? } else { ?>
    <form action="store.php">
      <input type="hidden" name="item" value="coinbadge">
      <input type="hidden" name="confirm" value="1">
      <input type="submit" value="Purchase">
    </form>
    <? } ?>
  </div>
<? } ?>

</div>
<? View::show_footer(); ?>
