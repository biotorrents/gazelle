<?php
#declare(strict_types=1);


$app = \Gazelle\App::go();

$Cost = 2000;

if (isset($_POST['torrent'])) {
    // Validation
    if (!empty($_GET['torrentid']) && is_numeric($_GET['torrentid'])) {
        $TorrentID = $_GET['torrentid'];
    } else {
        if (empty($_POST['torrent'])) {
            error('You forgot to supply a link to the torrent to freeleech');
        } else {
            $Link = $_POST['torrent'];
            if (!preg_match("/{$app->env->regexTorrent}/i", $Link, $Matches)) {
                error('Your link didn\'t seem to be a valid torrent link');
            } else {
                $TorrentID = $Matches[4];
            }
        }

        if (!$TorrentID || !is_numeric($TorrentID)) {
            error(404);
        }
    }
    $UserID = $app->user->core['id'];

    // Make sure torrent exists
    $app->dbOld->prepared_query("
      SELECT FreeTorrent, FreeLeechType
      FROM torrents
      WHERE ID = $TorrentID");

    if ($app->dbOld->has_results()) {
        list($FreeTorrent, $FreeLeechType) = $app->dbOld->next_record();
        if ($FreeTorrent === 2) {
            error('Torrent is already neutral leech.');
        } elseif ($FreeTorrent === 1 && $FreeLeechType !== 3) {
            error('Torrent is already freeleech for another reason.');
        }
    } else {
        error('Torrent does not exist');
    }

    $app->dbOld->prepared_query("
      SELECT BonusPoints
      FROM users_main
      WHERE ID = $UserID");

    if ($app->dbOld->has_results()) {
        list($Points) = $app->dbOld->next_record();

        if ($Points >= $Cost) {
            $app->dbOld->prepared_query("
              SELECT TorrentID
              FROM shop_freeleeches
              WHERE TorrentID = $TorrentID");

            if ($app->dbOld->has_results()) {
                $app->dbOld->prepared_query("
                  UPDATE shop_freeleeches
                  SET ExpiryTime = ExpiryTime + INTERVAL 1 DAY
                  WHERE TorrentID = $TorrentID");
            } else {
                $app->dbOld->prepared_query("
                  INSERT INTO shop_freeleeches
                    (TorrentID, ExpiryTime)
                  VALUES($TorrentID, NOW() + INTERVAL 1 DAY)");
                Torrents::freeleech_torrents($TorrentID, 1, 3);
            }

            $app->dbOld->prepared_query("
              UPDATE users_main
              SET BonusPoints = BonusPoints - $Cost
              WHERE ID = $UserID");

            $app->dbOld->prepared_query("
              UPDATE users_info
              SET AdminComment = CONCAT('".sqltime()." - Made TorrentID $TorrentID freeleech for 24 more hours via the store\n\n', AdminComment)
              WHERE UserID = $UserID");

            $app->cache->delete('user_info_heavy_'.$UserID);
            $app->cache->delete('shop_freeleech_list');
        } else {
            error("Not enough points");
        }
    }

    View::header('Store'); ?>
<div>
  <h2>Purchase Successful</h2>
  <div class="box">
    <p>
      You purchased 24 hours of freeleech for
      <a href="/torrents.php?torrentid=<?= $TorrentID ?>">this
        torrent</a>
    </p>
    <p>
      <a href="/store.php">Back to Store</a>
    </p>
  </div>
</div>
<?php
View::footer();
} else {
    View::header('Store'); ?>
<div>
  <div class="box text-align: center;">

    <form action="store.php" method="POST">
      <input type="hidden" name="item" value="freeleechize">
      <strong>
        Enter the URL of the torrent you wish to make freeleech for 24 hours:
      </strong>
      <br>
      <input type="text" name="torrent" value="">
      <input type="submit">
    </form>

    <p>
      <a href="/store.php">Back to Store</a>
    </p>
  </div>
</div>
<?php
View::footer();
}
