<?
if (isset($_POST['torrent'])) {
  //Validation
  if (!empty($_GET['torrentid']) && is_number($_GET['torrentid'])) {
    $TorrentID = $_GET['torrentid'];
  } else {
    if (empty($_POST['torrent'])) {
      error('You forgot to supply a link to the torrent to freeleech');
    } else {
      $Link = $_POST['torrent'];
      if (!preg_match('/'.TORRENT_REGEX.'/i', $Link, $Matches)) {
        error('Your link didn\'t seem to be a valid torrent link');
      } else {
        $TorrentID = $Matches[4];
      }
    }
    if (!$TorrentID || !is_number($TorrentID)) {
      error(404);
    }
  }

  $UserID = $LoggedUser['ID'];

  //Make sure torrent exists
  $DB->query("
    SELECT FreeTorrent, FreeLeechType
    FROM torrents
    WHERE ID = $TorrentID");
  if ($DB->has_results()) {
    list($FreeTorrent, $FreeLeechType) = $DB->next_record();
    if ($FreeTorrent == 2) {
      error('Torrent is already neutral leech.');
    } elseif ($FreeTorrent == 1 && $FreeLeechType != 3) {
      error('Torrent is already free leech for another reason.');
    }
  } else {
    error('Torrent does not exist');
  }

  $DB->query("
    SELECT BonusPoints
    FROM users_main
    WHERE ID = $UserID");
  if ($DB->has_results()) {
    list($Points) = $DB->next_record();

    if ($Points >= 20000) {

      $DB->query("
        SELECT TorrentID
        FROM shop_freeleeches
        WHERE TorrentID = $TorrentID");
      if ($DB->has_results()) {
        $DB->query("
          UPDATE shop_freeleeches
          SET ExpiryTime = ExpiryTime + INTERVAL 1 DAY
          WHERE TorrentID = $TorrentID");
      } else {
        $DB->query("
          INSERT INTO shop_freeleeches
          (TorrentID, ExpiryTime)
          VALUES($TorrentID, NOW() + INTERVAL 1 DAY)");

        Torrents::freeleech_torrents($TorrentID, 1, 3);
      }

      $DB->query("
        UPDATE users_main
        SET BonusPoints = BonusPoints - 20000
        WHERE ID = $UserID");
      $DB->query("
        UPDATE users_info
        SET AdminComment = CONCAT('".sqltime()." - Made TorrentID $TorrentID freeleech for 24 more hours via the store\n\n', AdminComment)
        WHERE UserID = $UserID");

      $Cache->delete_value('user_info_heavy_'.$UserID);
      $Cache->delete_value('shop_freeleech_list');
    } else {
      error("Not enough points");
    }
  }

  View::show_header('Store'); ?>
  <div class="thin">
    <h2 id="general">Purchase Successful</h2>
    <div class="box pad" style="padding: 10px 10px 10px 20px;">
      <p>
        You purchased 24 hours of freeleech for
        <a href="/torrents.php?torrentid=<? print $TorrentID ?>">this</a>
        torrent
      </p>
      <p><a href="/store.php">Back to Store</a></p>
    </div>
  </div>
  <? View::show_footer();

} else {

  View::show_header('Store'); ?>
  <div class="thin">
    <div class="box pad" style="padding: 10px 10px 10px 20px; text-align: center;">
      <form action="store.php" method="POST">
        <input type="hidden" name="item" value="freeleechize">
        <strong>
          Enter the URL of the torrent you wish to make freeleech for 24 hours:
        </strong>
        <br>
        <input type="text" name="torrent" value="">
        <input type="submit">
      </form>
      <p><a href="/store.php">Back to Store</a></p>
    </div>
  </div>
  <? View::show_footer();
}
?>
