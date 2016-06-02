<?
if (isset($_POST['donation'])) {

  $Donation = $_POST['donation'];
  if (!is_numeric($Donation) || $Donation < 1) {
    error('Invalid donation');
  }

	$UserID = $LoggedUser['ID'];

	$DB->query("
		SELECT BonusPoints
		FROM users_main
		WHERE ID = $UserID");
	if ($DB->has_results()) {
		list($Points) = $DB->next_record();

		if ($Points >= $Donation) {

      $PoolTipped = false;

			$DB->query("
				UPDATE users_main
				SET BonusPoints = BonusPoints - $Donation
				WHERE ID = $UserID");
			$DB->query("
				UPDATE site_options
				SET Value = Value + $Donation
        WHERE Name = 'FreeleechPool'");
      $Cache->delete_value('user_info_heavy_'.$UserID);

      // Check to see if we're now over the target pool size
      $DB->query("
        SELECT Value, Comment
        FROM site_options
        WHERE Name = 'FreeleechPool'");
      if ($DB->has_results()) {
        list($Pool, $Target) = $DB->next_record();

        if ($Pool > $Target) {
          $PoolTipped = true;
          $NumTorrents = rand(2, 6);
          $Torrents = array();
          for ($i = 0; $i < $NumTorrents; $i++) {
            $TorrentSize = intval($Pool * (($i==$NumTorrents-1)?1:(rand(10,80)/100)) * 100000);
            $DB->query("
              SELECT ID, Size
              FROM torrents
              WHERE Size < $TorrentSize
                AND Size > ($TorrentSize * 0.9)
                AND Seeders > 0
                AND FreeLeechType = '0'
              ORDER BY Seeders ASC, Size DESC
              LIMIT 1");
            if ($DB->has_results()) {
              list($TorrentID, $Size) = $DB->next_record();
              $DB->query("
                INSERT INTO shop_freeleeches
                (TorrentID, ExpiryTime)
                VALUES($TorrentID, NOW() + INTERVAL 2 DAY)");
              Torrents::freeleech_torrents($TorrentID, 1, 3);
              $Pool -= $TorrentSize/100000;
            } else {
              // Failed to find a torrent. Maybe try again with a new value, maybe move on
              if (rand(1,5) > 1) { $i--; }
            }
          }

          $Target = rand(10000, 100000);
          $DB->query("
            UPDATE site_options
            SET Value = 0,
                Comment = $Target
            WHERE Name = 'FreeleechPool'");
        }
      }
      $Cache->delete_value('shop_freeleech_list');
		} else {
			error("Not enough points to donate");
		}
	}

	View::show_header('Store'); ?>
	<div class="thin">
		<h2 id="general">Donation Successful</h2>
		<div class="box pad" style="padding: 10px 10px 10px 20px;">
			<p>You donated <?=number_format($Donation)?> nips to the Freeleech Pool</p>
<? if ($PoolTipped) { ?>
      <p>Your donation triggered a freeleech!</p>
<? } ?>
			<p><a href="/store.php">Back to Store</a></p>
		</div>
	</div>
	<? View::show_footer(); 

} else {

  $DB->query("
    SELECT Value
    FROM site_options
    WHERE Name = 'FreeleechPool'");
  if ($DB->has_results()) {
    list($Pool) = $DB->next_record();
  } else {
    $Pool = 0;
  }

	View::show_header('Store'); ?>
	<div class="thin">
		<div class="box pad" style="padding: 10px 10px 10px 20px; text-align: center;">
			<form action="store.php" method="POST">
				<input type="hidden" name="item" value="freeleechpool">
				<strong>
        There are currently <?=number_format($Pool)?> nips in the Freeleech Pool
				</strong>
				<br><br>
				<input type="text" name="donation" value="">
				<input type="submit" value="Donate">
			</form>
			<p><a href="/store.php">Back to Store</a></p>
		</div>
	</div>
	<? View::show_footer(); 
}
?>
