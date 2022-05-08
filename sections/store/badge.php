<?php
#declare(strict_types=1);

$UserID = $user['ID'];
$BadgeID = $_GET['badge'];

$ShopBadgeIDs = [40, 41, 42, 43, 44, 45, 46, 47, 48];
$Prices = [
    40 => 50,
    41 => 100,
    42 => 250,
    43 => 500,
    44 => 1000,
    45 => 2500,
    46 => 5000,
    47 => 10000,
    48 => 25000
];

if (!$BadgeID) {
    $Err = 'No badge specified';
} elseif (!in_array($BadgeID, $ShopBadgeIDs)) {
    $Err = 'Invalid badge ID';
} elseif (Badges::has_badge($UserID, $BadgeID)) {
    $Err = 'You already have this badge';
} elseif ((int) $BadgeID !== $ShopBadgeIDs[0] && !Badges::has_badge($UserID, $ShopBadgeIDs[array_search($BadgeID, $ShopBadgeIDs)-1])) {
    $Err = "You haven't purchased the badges before this one!";
}

if (isset($_GET['confirm']) && $_GET['confirm'] === '1') {
    if (!isset($Err)) {
        $db->prepared_query("
          SELECT BonusPoints
          FROM users_main
          WHERE ID = $UserID");

        if ($db->has_results()) {
            list($BP) =  $db->next_record();
            $BP = (int) $BP;

            if ($BP >= $Prices[$BadgeID]) {
                if (!Badges::award_badge($UserID, $BadgeID)) {
                    $Err = 'Could not award badge, unknown error occurred.';
                } else {
                    $db->prepared_query("
                      UPDATE users_main
                      SET BonusPoints = BonusPoints - " . $Prices[$BadgeID] ."
                      WHERE ID = $UserID");

                    $db->prepared_query("
                      UPDATE users_info
                      SET AdminComment = CONCAT('".sqltime()." - Purchased badge $BadgeID from store\n\n', AdminComment)
                      WHERE UserID = $UserID");

                    $cache->delete_value("user_info_heavy_$UserID");
                }
            } else {
                $Err = 'Not enough '.BONUS_POINTS.'.';
            }
        }
    }

    View::header('Store'); ?>
<div>
    <h2 id='general'>
        Purchase <?=isset($Err)?'Failed':'Successful'?>
    </h2>
    <div class='box pad'>
        <p>
            <?=isset($Err)?'Error: '.$Err:'You have purchased a badge'?>
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
            <?=Text::float($Prices[$BadgeID])?>
            <?=BONUS_POINTS?>
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
