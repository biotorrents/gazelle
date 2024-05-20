<?php
#declare(strict_types=1);

$app = \Gazelle\App::go();

View::header('Top 10 Donors');
?>

<div>
  <div class="header">
    <h2>Top Donors</h2>
    <?php \Gazelle\Top10::render_linkbox("donors"); ?>
  </div>
  <?php

$Limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$Limit = in_array($Limit, array(10, 100, 250)) ? $Limit : 10;

$IsMod = $app->user->can(["admin" => "moderateUsers"]);
$app->dbOld->prepared_query("
SELECT
  `UserID`,
  `TotalRank`,
  `Rank`,
  `SpecialRank`,
  `DonationTime`,
  `Hidden`
FROM
  `users_donor_ranks`
WHERE
  `TotalRank` > 0
ORDER BY
  `TotalRank`
DESC
LIMIT
  $Limit
");

$Results = $app->dbOld->to_array();
generate_user_table('Top Donors', $Results, $Limit);
echo '</div>';
View::footer();

// Generate a table based on data from most recent query to $app->dbOld
function generate_user_table($Caption, $Results, $Limit)
{
    global $Time, $IsMod; ?>
  <h3>Top <?="$Limit $Caption"; ?>
    <small class="top10_quantity_links">
      <?php
  switch ($Limit) {
      case 100: ?>
      &ndash; <a href="top10.php?type=donors" class="brackets">Top 10</a>
      &ndash; <span class="brackets">Top 100</span>
      &ndash; <a href="top10.php?type=donors&amp;limit=250" class="brackets">Top 250</a>
      <?php break;
      case 250: ?>
      &ndash; <a href="top10.php?type=donors" class="brackets">Top 10</a>
      &ndash; <a href="top10.php?type=donors&amp;limit=100" class="brackets">Top 100</a>
      &ndash; <span class="brackets">Top 250</span>
      <?php break;
      default: ?>
      &ndash; <span class="brackets">Top 10</span>
      &ndash; <a href="top10.php?type=donors&amp;limit=100" class="brackets">Top 100</a>
      &ndash; <a href="top10.php?type=donors&amp;limit=250" class="brackets">Top 250</a>
      <?php } ?>
    </small>
  </h3>

  <table class="border top10_table">
    <tr class="colhead">
      <td class="center">Position</td>
      <td>User</td>
      <td style="text-align: left;">Total Donor Points</td>
      <td style="text-align: left;">Current Donor Rank</td>
      <td style="text-align: left;">Last Donated</td>
    </tr>

    <?php
  // In the unlikely event that query finds 0 rows...
  if (empty($Results)) {
      echo '
    <tr class="row">
      <td colspan="9" class="center">
        Found no users matching the criteria
      </td>
    </tr>
    </table><br>';
  }

    $Position = 0;
    foreach ($Results as $Result) {
        $Position++; ?>
    <tr class="row">
      <td class="center">
        <?=$Position?>
      </td>

      <td>
        <?=$Result['Hidden'] && !$IsMod ? 'Hidden' : Users::format_username($Result['UserID'], false, false, false)?>
      </td>

      <td style="text-align: left;">
        <?=$app->user->can(["admin" => "moderateUsers"]) || $Position < 51 ? $Result['TotalRank'] : 'Hidden'; ?>
      </td>

      <td style="text-align: left;">
        <?=$Result['Hidden'] && !$IsMod ? 'Hidden' : DonationsView::render_rank($Result['Rank'], $Result['SpecialRank'])?>
      </td>

      <td style="text-align: left;">
        <?=$Result['Hidden'] && !$IsMod ? 'Hidden' : time_diff($Result['DonationTime'])?>
      </td>
    </tr>
    <?php
    } ?>
  </table>
  <?php
}
