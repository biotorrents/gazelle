<?php

declare(strict_types=1);


/**
 * top10 users
 */

$app = \Gazelle\App::go();

enforce_login();
if (!check_perms('site_top10')) {
    error(403);
}

$get = Http::query("get");
$limit = intval($get["limit"] ?? Top10::$defaultLimit);




View::header('Top 10 Users');
?>

<div>
  <div class="header">
    <h2>Top 10 Users</h2>
    <?php Top10::render_linkbox("users"); ?>

  </div>
  <?php

// Defaults to 10 (duh)
$Limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$Limit = in_array($Limit, array(10,100,250)) ? $Limit : 10;

$BaseQuery = "
  SELECT
    u.ID,
    ui.JoinDate,
    u.Uploaded,
    u.Downloaded,
    ABS(u.Uploaded-524288000) / (".time()." - UNIX_TIMESTAMP(ui.JoinDate)) AS UpSpeed,
    u.Downloaded / (".time()." - UNIX_TIMESTAMP(ui.JoinDate)) AS DownSpeed,
    COUNT(t.ID) AS NumUploads
  FROM users_main AS u
    JOIN users_info AS ui ON ui.UserID = u.ID
    LEFT JOIN torrents AS t ON t.UserID=u.ID
  WHERE u.Enabled='1'
    AND Uploaded>='". 500*1024*1024 ."'
    AND Downloaded>='". 0 ."'
    AND u.ID > 2
    AND (Paranoia IS NULL OR (Paranoia NOT LIKE '%\"uploaded\"%' AND Paranoia NOT LIKE '%\"downloaded\"%'))
  GROUP BY u.ID";

  $Details ??= "all";
if ($Details == 'all' || $Details == 'ul') {
    if (!$TopUserUploads = $app->cacheOld->get_value('topuser_ul_'.$Limit)) {
        $app->dbOld->prepared_query("$BaseQuery ORDER BY u.Uploaded DESC LIMIT $Limit;");
        $TopUserUploads = $app->dbOld->to_array();
        $app->cacheOld->cache_value('topuser_ul_'.$Limit, $TopUserUploads, 3600 * 12);
    }
    generate_user_table('Uploaders', 'ul', $TopUserUploads, $Limit);
}

if ($Details == 'all' || $Details == 'dl') {
    if (!$TopUserDownloads = $app->cacheOld->get_value('topuser_dl_'.$Limit)) {
        $app->dbOld->prepared_query("$BaseQuery ORDER BY u.Downloaded DESC LIMIT $Limit;");
        $TopUserDownloads = $app->dbOld->to_array();
        $app->cacheOld->cache_value('topuser_dl_'.$Limit, $TopUserDownloads, 3600 * 12);
    }
    generate_user_table('Downloaders', 'dl', $TopUserDownloads, $Limit);
}

if ($Details == 'all' || $Details == 'numul') {
    if (!$TopUserNumUploads = $app->cacheOld->get_value('topuser_numul_'.$Limit)) {
        $app->dbOld->prepared_query("$BaseQuery ORDER BY NumUploads DESC LIMIT $Limit;");
        $TopUserNumUploads = $app->dbOld->to_array();
        $app->cacheOld->cache_value('topuser_numul_'.$Limit, $TopUserNumUploads, 3600 * 12);
    }
    generate_user_table('Torrents Uploaded', 'numul', $TopUserNumUploads, $Limit);
}

if ($Details == 'all' || $Details == 'uls') {
    if (!$TopUserUploadSpeed = $app->cacheOld->get_value('topuser_ulspeed_'.$Limit)) {
        $app->dbOld->prepared_query("$BaseQuery ORDER BY UpSpeed DESC LIMIT $Limit;");
        $TopUserUploadSpeed = $app->dbOld->to_array();
        $app->cacheOld->cache_value('topuser_ulspeed_'.$Limit, $TopUserUploadSpeed, 3600 * 12);
    }
    generate_user_table('Fastest Uploaders', 'uls', $TopUserUploadSpeed, $Limit);
}

if ($Details == 'all' || $Details == 'dls') {
    if (!$TopUserDownloadSpeed = $app->cacheOld->get_value('topuser_dlspeed_'.$Limit)) {
        $app->dbOld->prepared_query("$BaseQuery ORDER BY DownSpeed DESC LIMIT $Limit;");
        $TopUserDownloadSpeed = $app->dbOld->to_array();
        $app->cacheOld->cache_value('topuser_dlspeed_'.$Limit, $TopUserDownloadSpeed, 3600 * 12);
    }
    generate_user_table('Fastest Downloaders', 'dls', $TopUserDownloadSpeed, $Limit);
}

echo '</div>';
View::footer();
exit;

// Generate a table based on data from most recent query to $db
function generate_user_table($Caption, $Tag, $Details, $Limit)
{
    ?>
  <h3>Top <?=$Limit.' '.$Caption; ?>
    <small class="top10_quantity_links">
      <?php
  switch ($Limit) {
      case 100: ?>
      &ndash; <a href="/top10?type=users&amp;details=<?=$Tag?>"
        class="brackets">Top 10</a>
      &ndash; <span class="brackets">Top 100</span>
      &ndash; <a
        href="/top10?type=users&amp;limit=250&amp;details=<?=$Tag?>"
        class="brackets">Top 250</a>
      <?php break;
      case 250: ?>
      &ndash; <a href="/top10?type=users&amp;details=<?=$Tag?>"
        class="brackets">Top 10</a>
      &ndash; <a
        href="/top10?type=users&amp;limit=100&amp;details=<?=$Tag?>"
        class="brackets">Top 100</a>
      &ndash; <span class="brackets">Top 250</span>
      <?php break;
      default: ?>
      &ndash; <span class="brackets">Top 10</span>
      &ndash; <a
        href="/top10?type=users&amp;limit=100&amp;details=<?=$Tag?>"
        class="brackets">Top 100</a>
      &ndash; <a
        href="/top10?type=users&amp;limit=250&amp;details=<?=$Tag?>"
        class="brackets">Top 250</a>
      <?php } ?>
    </small>
  </h3>
  <table class="border top10_table">
    <tr class="colhead">
      <td class="center">Rank</td>
      <td>User</td>
      <td style="text-align: right;">Uploaded</td>
      <td style="text-align: right;">UL speed</td>
      <td style="text-align: right;">Downloaded</td>
      <td style="text-align: right;">DL speed</td>
      <td style="text-align: right;">Uploads</td>
      <td style="text-align: right;">Ratio</td>
      <td style="text-align: right;">Joined</td>
    </tr>

    <?php
  // In the unlikely event that query finds 0 rows...
  if (empty($Details)) {
      echo '
    <tr class="row">
      <td colspan="9" class="center">
        Found no users matching the criteria
      </td>
    </tr>
    </table><br />';
      return;
  }
      $Rank = 0;
    foreach ($Details as $Detail) {
        $Rank++; ?>
    <tr class="row">
      <td class="center"><?=$Rank?>
      </td>
      <td><?=User::format_username($Detail['ID'], false, false, false)?>
      </td>
      <td class="number_column"><?=Format::get_size($Detail['Uploaded'])?>
      </td>
      <td class="number_column tooltip"
        title="Upload speed is reported in base 2 in bytes per second, not bits per second."><?=Format::get_size($Detail['UpSpeed'])?>/s</td>
      <td class="number_column"><?=Format::get_size($Detail['Downloaded'])?>
      </td>
      <td class="number_column tooltip"
        title="Download speed is reported in base 2 in bytes per second, not bits per second."><?=Format::get_size($Detail['DownSpeed'])?>/s
      </td>
      <td class="number_column"><?=Text::float($Detail['NumUploads'])?>
      </td>
      <td class="number_column"><?=Format::get_ratio_html(intval($Detail['Uploaded']), intval($Detail['Downloaded']))?>
      </td>
      <td class="number_column"><?=time_diff($Detail['JoinDate'])?>
      </td>
    </tr>
    <?php
    } ?>
  </table><br />
  <?php
}
