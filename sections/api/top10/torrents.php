<?php
#declare(strict_types=1);

# todo: Go through line by line
if (isset($_GET['details'])) {
    if (in_array($_GET['details'], array('day','week','overall','snatched','data','seeded'))) {
        $Details = $_GET['details'];
    } else {
        echo json_encode(array('status' => 'failure'));
        error();
    }
} else {
    $Details = 'all';
}

// Defaults to 10 (duh)
$Limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$Limit = in_array($Limit, array(10, 100, 250)) ? $Limit : 10;

$WhereSum = (empty($Where)) ? '' : md5($Where);
$BaseQuery = "
SELECT
  t.`ID`,
  g.`id`,
  g.`title`,
  g.`category_id`,
  g.`picture`,
  g.`tag_list`,
  t.`Media`,
  g.`year`,
  t.`Snatched`,
  t.`Seeders`,
  t.`Leechers`,
  (
    (t.`Size` * t.`Snatched`) +(t.`Size` * 0.5 * t.`Leechers`)
  ) AS `Data`,
  t.`Size`
FROM
  `torrents` AS t
LEFT JOIN `torrents_group` AS g
ON
  g.`id` = t.`GroupID`
";

$OuterResults = [];

if ($Details == 'all' || $Details == 'day') {
    if (!$TopTorrentsActiveLastDay = $cache->get_value('top10tor_day_'.$Limit.$WhereSum)) {
        $DayAgo = time_minus(86400);
        $Query = $BaseQuery.' WHERE t.Seeders>0 AND ';
        if (!empty($Where)) {
            $Query .= $Where.' AND ';
        }
        $Query .= "
      t.Time>'$DayAgo'
      ORDER BY (t.Seeders + t.Leechers) DESC
      LIMIT $Limit;";
        $db->query($Query);
        $TopTorrentsActiveLastDay = $db->to_array(false, MYSQLI_NUM);
        $cache->cache_value('top10tor_day_'.$Limit.$WhereSum, $TopTorrentsActiveLastDay, 3600 * 2);
    }
    $OuterResults[] = generate_torrent_json('Most Active Torrents Uploaded in the Past Day', 'day', $TopTorrentsActiveLastDay, $Limit);
}
if ($Details == 'all' || $Details == 'week') {
    if (!$TopTorrentsActiveLastWeek = $cache->get_value('top10tor_week_'.$Limit.$WhereSum)) {
        $WeekAgo = time_minus(604800);
        $Query = $BaseQuery.' WHERE ';
        if (!empty($Where)) {
            $Query .= $Where.' AND ';
        }
        $Query .= "
      t.Time>'$WeekAgo'
      ORDER BY (t.Seeders + t.Leechers) DESC
      LIMIT $Limit;";
        $db->query($Query);
        $TopTorrentsActiveLastWeek = $db->to_array(false, MYSQLI_NUM);
        $cache->cache_value('top10tor_week_'.$Limit.$WhereSum, $TopTorrentsActiveLastWeek, 3600*6);
    }
    $OuterResults[] = generate_torrent_json('Most Active Torrents Uploaded in the Past Week', 'week', $TopTorrentsActiveLastWeek, $Limit);
}

if ($Details == 'all' || $Details == 'overall') {
    if (!$TopTorrentsActiveAllTime = $cache->get_value("top10tor_overall_$Limit$WhereSum")) {
        $Query = $BaseQuery;
        if (!empty($Where)) {
            $Query .= " WHERE $Where";
        }
        $Query .= "
      ORDER BY (t.Seeders + t.Leechers) DESC
      LIMIT $Limit;";
        $db->query($Query);
        $TopTorrentsActiveAllTime = $db->to_array(false, MYSQLI_NUM);
        $cache->cache_value("top10tor_overall_$Limit$WhereSum", $TopTorrentsActiveAllTime, 3600 * 6);
    }
    $OuterResults[] = generate_torrent_json('Most Active Torrents of All Time', 'overall', $TopTorrentsActiveAllTime, $Limit);
}

if (($Details == 'all' || $Details == 'snatched') && empty($Where)) {
    if (!$TopTorrentsSnatched = $cache->get_value("top10tor_snatched_$Limit$WhereSum")) {
        $Query = $BaseQuery;
        $Query .= "
      ORDER BY t.Snatched DESC
      LIMIT $Limit;";
        $db->query($Query);
        $TopTorrentsSnatched = $db->to_array(false, MYSQLI_NUM);
        $cache->cache_value("top10tor_snatched_$Limit$WhereSum", $TopTorrentsSnatched, 3600 * 6);
    }
    $OuterResults[] = generate_torrent_json('Most Snatched Torrents', 'snatched', $TopTorrentsSnatched, $Limit);
}

if (($Details == 'all' || $Details == 'data') && empty($Where)) {
    if (!$TopTorrentsTransferred = $cache->get_value("top10tor_data_$Limit$WhereSum")) {
        $Query = $BaseQuery;
        $Query .= "
      ORDER BY Data DESC
      LIMIT $Limit;";
        $db->query($Query);
        $TopTorrentsTransferred = $db->to_array(false, MYSQLI_NUM);
        $cache->cache_value("top10tor_data_$Limit$WhereSum", $TopTorrentsTransferred, 3600 * 6);
    }
    $OuterResults[] = generate_torrent_json('Most Data Transferred Torrents', 'data', $TopTorrentsTransferred, $Limit);
}

if (($Details == 'all' || $Details == 'seeded') && empty($Where)) {
    if (!$TopTorrentsSeeded = $cache->get_value("top10tor_seeded_$Limit$WhereSum")) {
        $Query = $BaseQuery."
      ORDER BY t.Seeders DESC
      LIMIT $Limit;";
        $db->query($Query);
        $TopTorrentsSeeded = $db->to_array(false, MYSQLI_NUM);
        $cache->cache_value("top10tor_seeded_$Limit$WhereSum", $TopTorrentsSeeded, 3600 * 6);
    }
    $OuterResults[] = generate_torrent_json('Best Seeded Torrents', 'seeded', $TopTorrentsSeeded, $Limit);
}

json_print("success", $OuterResults);

function generate_torrent_json($Caption, $Tag, $Details, $Limit)
{
    global $user, $Categories;
    $results = [];
    foreach ($Details as $Detail) {
        list($TorrentID, $GroupID, $GroupName, $GroupCategoryID, $WikiImage, $TorrentTags,
      $Media, $GroupYear,
      $Snatched, $Seeders, $Leechers, $Data, $Size) = $Detail;

        # todo: Make JSON object if multiple artists
        $Artist = Artists::display_artists(Artists::get_artist($GroupID), false, false);

        $TagList = [];

        if ($TorrentTags != '') {
            $TorrentTags = explode(' ', $TorrentTags);
            foreach ($TorrentTags as $TagKey => $TagName) {
                $TagName = str_replace('_', '.', $TagName);
                $TagList[] = $TagName;
            }
        }

        // Append to the existing array
        $results[] = array(
          'torrentId'     => (int) $TorrentID,
          'groupId'       => (int) $GroupID,
          'author'        => $Artist, # todo
          'groupName'     => $GroupName,
          'groupCategory' => (int) $GroupCategoryID,
          'groupYear'     => (int) $GroupYear,
          'platform'      => $Media,
          'tags'          => $TagList,
          'snatched'      => (int) $Snatched,
          'seeders'       => (int) $Seeders,
          'leechers'      => (int) $Leechers,
          'data'          => (int) $Data,
          'size'          => (int) $Size,
          'picture'       => $WikiImage,
        );
    }

    return array(
      'caption' => $Caption,
      'tag'     => $Tag,
      'limit'   => (int)$Limit,
      'results' => $results
    );
}
