<?php
#declare(strict_types=1);

$app = \Gazelle\App::go();

# todo: Not working since 2020-04-24
if (isset($_POST['donation'])) {
    $Donation = $_POST['donation'];

    if (!is_numeric($Donation) || $Donation < 1) {
        error('Invalid donation');
    }

    $UserID = $app->userNew->core['id'];
    $app->dbOld->prepared_query("
      SELECT BonusPoints
      FROM users_main
      WHERE ID = $UserID");

    if ($app->dbOld->has_results()) {
        list($Points) = $app->dbOld->next_record();

        if ($Points >= $Donation) {
            $PoolTipped = false;

            $app->dbOld->prepared_query("
              UPDATE users_main
              SET BonusPoints = BonusPoints - $Donation
              WHERE ID = $UserID");

            $app->dbOld->prepared_query("
              UPDATE misc
              SET First = First + $Donation
              WHERE Name = 'FreeleechPool'");
            $app->cacheNew->delete('user_info_heavy_'.$UserID);

            // Check to see if we're now over the target pool size
            $app->dbOld->prepared_query("
              SELECT First, Second
              FROM misc
              WHERE Name = 'FreeleechPool'");

            if ($app->dbOld->has_results()) {
                list($Pool, $Target) = $app->dbOld->next_record();

                if ($Pool > $Target) {
                    $PoolTipped = true;
                    $NumTorrents = rand(2, 6);
                    $Torrents = [];

                    for ($i = 0; $i < $NumTorrents; $i++) {
                        $TorrentSize = intval($Pool * (($i===$NumTorrents-1) ? 1 : (rand(10, 80)/100)) * 100000); # todo
                        $app->dbOld->prepared_query("
                          SELECT ID, Size
                          FROM torrents
                          WHERE Size < $TorrentSize
                            AND Size > ($TorrentSize * 0.9)
                            AND Seeders > 0
                            AND FreeLeechType = '0'
                          ORDER BY Seeders ASC, Size DESC
                          LIMIT 1");

                        if ($app->dbOld->has_results()) {
                            list($TorrentID, $Size) = $app->dbOld->next_record();

                            $app->dbOld->prepared_query("
                              INSERT INTO shop_freeleeches
                                (TorrentID, ExpiryTime)
                              VALUES($TorrentID, NOW() + INTERVAL 2 DAY)");

                            Torrents::freeleech_torrents($TorrentID, 1, 3);
                            $Pool -= $TorrentSize/100000;
                        } else {
                            // Failed to find a torrent. Maybe try again with a new value, maybe move on
                            if (rand(1, 5) > 1) {
                                $i--;
                            }
                        }
                    }

                    $Target = rand(10000, 100000);
                    $app->dbOld->prepared_query("
                      UPDATE misc
                      SET First = 0,
                        Second = $Target
                      WHERE Name = 'FreeleechPool'");
                }
            }
            $app->cacheNew->delete('shop_freeleech_list');
        } else {
            error("Not enough points to donate");
        }
    }

    View::header('Store'); ?>
<div>
    <h2>Donation Successful</h2>
    <div class="box">
        <p>
            You donated
            <?=Text::float($Donation)?>
            <?=bonusPoints?>
            to the Freeleech Pool
        </p>

        <?php
        if ($PoolTipped) { ?>
        <p>
            Your donation triggered a freeleech!
        </p>
        <?php } ?>

        <p>
            <a href="/store.php">Back to Store</a>
        </p>
    </div>
</div>
<?php
View::footer();
} else {
    $app->dbOld->prepared_query("
      SELECT First
      FROM misc
      WHERE Name = 'FreeleechPool'");

    if ($app->dbOld->has_results()) {
        list($Pool) = $app->dbOld->next_record();
    } else {
        $Pool = 0;
    }

    View::header('Store'); ?>
<div>
    <div class="box text-align: center;">
        <form action="store.php" method="POST">
            <input type="hidden" name="item" value="freeleechpool">
            <strong>
                There are currently
                <?=Text::float($Pool)?>
                <?=bonusPoints?>
                in the Freeleech Pool
            </strong>
            <br /><br />
            <input type="text" name="donation" value="">
            <input type="submit" value="Donate">
        </form>
        <p><a href="/store.php">Back to Store</a></p>
    </div>
</div>
<?php View::footer();
}
