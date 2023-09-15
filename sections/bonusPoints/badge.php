<?php
#declare(strict_types=1);

$app = \Gazelle\App::go();

$UserID = $app->user->core['id'];
$BadgeID = $_GET['badge'];

$ShopBadgeIDs = [40, 41, 42, 43, 44, 45, 46, 47, 48];
$Prices = [
    40 => 1000,
    41 => 2000,
    42 => 5000,
    43 => 10000,
    44 => 20000,
    45 => 50000,
    46 => 100000,
    47 => 200000,
    48 => 500000,
];

if (!$BadgeID) {
    $Err = 'No badge specified';
} elseif (!in_array($BadgeID, $ShopBadgeIDs)) {
    $Err = 'Invalid badge ID';
} elseif (Badges::hasBadge($UserID, $BadgeID)) {
    $Err = 'You already have this badge';
} elseif ((int) $BadgeID !== $ShopBadgeIDs[0] && !Badges::hasBadge($UserID, $ShopBadgeIDs[array_search($BadgeID, $ShopBadgeIDs) - 1])) {
    $Err = "You haven't purchased the badges before this one!";
}

if (isset($_GET['confirm']) && $_GET['confirm'] === '1') {
    if (!isset($Err)) {
        $app->dbOld->prepared_query("
          SELECT BonusPoints
          FROM users_main
          WHERE ID = $UserID");

        if ($app->dbOld->has_results()) {
            list($BP) =  $app->dbOld->next_record();
            $BP = (int) $BP;

            if ($BP >= $Prices[$BadgeID]) {
                if (!Badges::awardBadge($UserID, $BadgeID)) {
                    $Err = 'Could not award badge, unknown error occurred.';
                } else {
                    $app->dbOld->prepared_query("
                      UPDATE users_main
                      SET BonusPoints = BonusPoints - " . $Prices[$BadgeID] ."
                      WHERE ID = $UserID");

                    $app->dbOld->prepared_query("
                      UPDATE users_info
                      SET AdminComment = CONCAT('".sqltime()." - Purchased badge $BadgeID from store\n\n', AdminComment)
                      WHERE UserID = $UserID");

                    $app->cache->delete("user_info_heavy_$UserID");
                }
            } else {
                $Err = 'Not enough '.bonusPoints.'.';
            }
        }
    }

    View::header('Store'); ?>
<div>
    <h2 id='general'>
        Purchase <?=isset($Err) ? 'Failed' : 'Successful'?>
    </h2>
    <div class='box pad'>
        <p>
            <?=isset($Err) ? 'Error: '.$Err : 'You have purchased a badge'?>
        </p>

        <p>
            <a href='/store.php'>Back to Store</a>
        </p>
    </div>
</div>
<?php
} else {
    View::header('Store'); ?>
<div>
    <h2 id='general'>Purchase Badge?</h2>
    <div class='box pad'>
        <p>
            Badge cost:
            <?=\Gazelle\Text::float($Prices[$BadgeID])?>
            <?=bonusPoints?>
        </p>

        <?php if (isset($Err)) { ?>
        <p>Error: <?=$Err?>
        </p>

        <?php } else { ?>
        <form action="store.php">
            <input type="hidden" name="item" value="badge">
            <input type="hidden" name="badge" value="<?=$BadgeID?>">
            <input type="hidden" name="confirm" value="1">
            <input type="submit" value="Purchase">
            <?php } ?>

            <p>
                <a href='/store.php'>Back to Store</a>
            </p>
    </div>
</div>
<?php
}
View::footer();
