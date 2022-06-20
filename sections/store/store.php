<?php
#declare(strict_types=1);

$UserID = $user['ID'];
$PermID = $user['PermissionID'];

if (!$user['DisablePoints']) {
    $PointsRate = 0;
    $getTorrents = $db->prepared_query("
      SELECT um.BonusPoints,
        COUNT(DISTINCT x.fid) AS Torrents,
        SUM(t.Size) AS Size,
        SUM(xs.seedtime) AS Seedtime,
        SUM(t.Seeders) AS Seeders
      FROM users_main AS um
      LEFT JOIN users_info AS i on um.ID = i.UserID
      LEFT JOIN xbt_files_users AS x ON um.ID=x.uid
      LEFT JOIN torrents AS t ON t.ID=x.fid
      LEFT JOIN xbt_snatched AS xs ON x.uid=xs.uid AND x.fid=xs.fid
      WHERE
        um.ID = '$UserID'
        AND um.Enabled = '1'
        AND x.active = 1
        AND x.completed = 0
        AND x.Remaining = 0
      GROUP BY um.ID");

    # BASE BONUS POINTS RATE
    # See /wiki.php?action=article&name=bonuspoints
    if ($db->has_results()) {
        list($BonusPoints, $NumTorr, $TSize, $TTime, $TSeeds) = $db->next_record();

        $ENV = ENV::go();
        $PointsRate = ($ENV->BP_COEFF + (0.55*($NumTorr * (sqrt(($TSize/$NumTorr)/1073741824) * pow(1.5, ($TTime/$NumTorr)/(24*365))))) / (max(1, sqrt(($TSeeds/$NumTorr)+4)/3)))**0.95;
    }

    $PointsRate = intval(max(min($PointsRate, ($PointsRate * 2) - ($BonusPoints/1440)), 0));
    $PointsPerHour = Text::float($PointsRate) . " ".BONUS_POINTS."/hour";
    $PointsPerDay = Text::float($PointsRate*24) . " ".BONUS_POINTS."/day";
} else {
    $PointsPerHour = "0 ".BONUS_POINTS."/hour";
    $PointsPerDay = BONUS_POINTS." disabled";
}

// Include the header
View::header('Store');
?>
<div>
  <div class="header">
    <h2>Store</h2>
  </div>

  <div class="box">
    <h3 id="lists" class="u-pull-left">
      You have
      <?=Text::float($user['BonusPoints'])?>
      <?=BONUS_POINTS?>
      to spend
    </h3>

    <h3 id="lists" class="u-pull-right">You're making <?=$PointsPerHour?> (<?=$PointsPerDay?>)</h3>
    <table width="100%" class="store_table">
      <tr class="colhead">
        <td style="width: 100px;">Item</td>
        <td style="width: 100px;">Cost</td>
        <td style="width: 400px;">Description</td>
      </tr>

      <!-- Upload: 10^1 -->
      <tr class="row">
        <td class="nobr">
          <a href="store.php?item=upload_1">0.1 GiB Upload</a>
        </td>

        <td class="nobr">
          15 <?=BONUS_POINTS?>
        </td>

        <td class="nobr">
          Purchase 0.1 GiB of upload
        </td>
      </tr>

      <!-- Upload: 10^2 -->
      <tr class="row">
        <td class="nobr">
          <a href="store.php?item=upload_10">1 GiB Upload</a>
        </td>

        <td class="nobr">
          150 <?=BONUS_POINTS?>
        </td>

        <td class="nobr">
          Purchase 1 GiB of upload
        </td>
      </tr>

      <!-- Upload: 10^3 -->
      <tr class="row">
        <td class="nobr">
          <a href="store.php?item=upload_100">10 GiB Upload</a>
        </td>

        <td class="nobr">
          1,500 <?=BONUS_POINTS?>
        </td>

        <td class="nobr">
          Purchase 10 GiB of upload
        </td>
      </tr>

      <!-- Upload: 10^4 -->
      <tr class="row">
        <td class="nobr">
          <a href="store.php?item=upload_1000">100 GiB Upload</a>
        </td>

        <td class="nobr">
          15,000 <?=BONUS_POINTS?>
        </td>

        <td class="nobr">
          Purchase 100 GiB of upload
        </td>
      </tr>

      <!-- Bonus Points: 10^1 -->
      <tr class="row">
        <td class="nobr">
          <a href="store.php?item=points_1">10 <?=BONUS_POINTS?></a>
        </td>

        <td class="nobr">
          0.15 GiB Upload
        </td>

        <td class="nobr">
          Purchase 10 <?=BONUS_POINTS?>
        </td>
      </tr>

      <!-- Bonus Points: 10^2 -->
      <tr class="row">
        <td class="nobr">
          <a href="store.php?item=points_10">100 <?=BONUS_POINTS?></a>
        </td>

        <td class="nobr">
          1.5 GiB Upload
        </td>

        <td class="nobr">
          Purchase 100 <?=BONUS_POINTS?>
        </td>
      </tr>

      <!-- Bonus Points: 10^3 -->
      <tr class="row">
        <td class="nobr">
          <a href="store.php?item=points_100">1,000 <?=BONUS_POINTS?></a>
        </td>

        <td class="nobr">
          15 GiB Upload
        </td>

        <td class="nobr">
          Purchase 1,000 <?=BONUS_POINTS?>
        </td>
      </tr>

      <!-- Bonus Points: 10^4 -->
      <tr class="row">
        <td class="nobr">
          <a href="store.php?item=points_1000">10,000 <?=BONUS_POINTS?></a>
        </td>

        <td class="nobr">
          150 GiB Upload
        </td>

        <td class="nobr">
          Purchase 10,000 <?=BONUS_POINTS?>
        </td>
      </tr>

      <!-- Freeleech Token -->
      <tr class="row">
        <td class="nobr">
          <a href="store.php?item=token">Freeleech Token</a>
        </td>

        <td class="nobr">
          1,000 <?=BONUS_POINTS?>
        </td>

        <td class="nobr">
          Purchase a freeleech token for yourself
        </td>
      </tr>

      <!-- Freeleechize -->
      <tr class="row">
        <td class="nobr">
          <a href="store.php?item=freeleechize">Freeleechize</a>
        </td>

        <td class="nobr">
          2,000 <?=BONUS_POINTS?>
        </td>

        <td class="nobr">
          Make a torrent freeleech (for everyone) for 24 hours
        </td>
      </tr>

      <!-- Custom Title -->
      <tr class="row">
        <td class="nobr">
          <a href="store.php?item=title">Custom Title</a>
        </td>

        <td class="nobr">
          5,000 <?=BONUS_POINTS?>
        </td>

        <td class="nobr">
          Purchase a custom title
        </td>
      </tr>

      <!-- Invite -->
      <tr class="row">
        <td class="nobr">
          <a href="store.php?item=invite">Invite</a>
        </td>

        <td class="nobr">
          10,000 <?=BONUS_POINTS?>
        </td>

        <td class="nobr">
          Purchase an invite for your friend
        </td>
      </tr>

      <!-- Freeleech Pool -->
      <tr class="row">
        <td class="nobr">
          <a href="store.php?item=freeleechpool">Freeleech Pool</a>
        </td>

        <td class="nobr">
        </td>

        <td class="nobr">
          Make a donation to the Freeleech Pool
        </td>
      </tr>
    </table>
    <br />

    <h4>Badges</h4>
    <table width="100%" class="store_table">
      <tr class="colhead">
        <td style="width: 100px;">Badge</td>
        <td style="width: 100px;">Cost</td>
        <td style="width: 400px;">Description</td>
      </tr>

      <?php
$db->prepared_query("
  SELECT ID AS BadgeID, Name, Description
  FROM badges
  WHERE ID IN (40, 41, 42, 43, 44, 45, 46, 47, 48)
");

if ($db->has_results()) {
    $Badges = $db->to_array();
    foreach ($Badges as $ID => $Badge) { ?>
      <tr class="row">
        <?php
        if (($ID === 0 || Badges::has_badge($user['ID'], $Badges[$ID-1]['BadgeID']))
        && !Badges::has_badge($user['ID'], $Badge['BadgeID'])) {
            $BadgeText = '<a href="store.php?item=badge&badge='.$Badge['BadgeID'].'">'.$Badge['Name'].'</a>';
        } else {
            $BadgeText = $Badge['Name'];
        } ?>

        <td class="nobr">
          <?=Badges::display_badge($Badge['BadgeID'])?>
          <span class="badge_name" style="margin-left: 10px;"><?=$BadgeText?></span>
        </td>

        <td class="nobr">
          <?=$Badge['Description']?>
        </td>
      </tr>
      <?php
    }
} ?>
    </table>
  </div>
</div>
<?php View::footer();
