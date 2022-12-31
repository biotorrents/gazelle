<?php
#declare(strict_types=1);

$Where = [];

if (!empty($_GET['advanced']) && check_perms('site_advanced_top10')) {
    $Details = 'all';
    $Limit = 10;

    if ($_GET['tags']) {
        $TagWhere = [];
        $Tags = explode(',', str_replace('.', '_', trim($_GET['tags'])));
        foreach ($Tags as $Tag) {
            $Tag = preg_replace('/[^a-z0-9_]/', '', $Tag);
            if ($Tag !== '') {
                $TagWhere[] = "g.TagList REGEXP '[[:<:]]".db_string($Tag)."[[:>:]]'";
            }
        }
        if (!empty($TagWhere)) {
            if ($_GET['anyall'] === 'any') {
                $Where[] = '('.implode(' OR ', $TagWhere).')';
            } else {
                $Where[] = '('.implode(' AND ', $TagWhere).')';
            }
        }
    }

    if ($_GET['category']) {
        if (in_array($_GET['category'], $Categories)) {
            $Where[] = "g.CategoryID = '".(array_search($_GET['category'], $Categories)+1)."'";
        }
    }
} else {
    // Error out on invalid requests (before caching)
    if (isset($_GET['details'])) {
        if (in_array($_GET['details'], array('day', 'week', 'overall', 'snatched', 'data', 'seeded', 'month', 'year'))) {
            $Details = $_GET['details'];
        } else {
            error(404);
        }
    } else {
        $Details = 'all';
    }

    // Defaults to 10 (duh)
    $Limit = (isset($_GET['limit']) ? intval($_GET['limit']) : 10);
    $Limit = (in_array($Limit, array(10, 100, 250)) ? $Limit : 10);
}
$Filtered = !empty($Where);
View::header("Top $Limit Torrents", 'browse');
?>

<div>
    <div class="header">
        <h2>Top <?=$Limit?> Torrents</h2>
        <?php Top10View::render_linkbox("torrents"); ?>
    </div>
    <?php

if (check_perms('site_advanced_top10')) {
    ?>
    <div class="box pad">
        <form class="search_form" name="torrents" action="" method="get">
            <input type="hidden" name="advanced" value="1" />
            <table cellpadding="6" cellspacing="1" border="0" class="layout" width="100%">
                <tr id="tagfilter">
                    <td class="label">Tags (comma-separated)</td>
                    <td class="ft_taglist">
                        <input type="text" name="tags" id="tags" size="65" value="<?php if (!empty($_GET['tags'])) {
        echo Text::esc($_GET['tags']);
    } ?>" />&nbsp;
                        <input type="radio" id="rdoAll" name="anyall" value="all" <?=((!isset($_GET['anyall'])||$_GET['anyall']!=='any') ? ' checked="checked"' : '')?>
                        /><label for="rdoAll"> All</label>&nbsp;&nbsp;
                        <input type="radio" id="rdoAny" name="anyall" value="any" <?=((!isset($_GET['anyall'])||$_GET['anyall']==='any') ? ' checked="checked"' : '')?>
                        /><label for="rdoAny"> Any</label>
                    </td>
                </tr>
                <tr>
                    <td class="label">Category</td>
                    <td>
                        <select name="category" style="width: auto;" class="ft_format">
                            <option value="">Any</option>
                            <?php foreach ($Categories as $CategoryName) { ?>
                            <option
                                value="<?=Text::esc($CategoryName)?>"
                                <?=(($CategoryName===($_GET['category']??false)) ? 'selected="selected"' : '')?>><?=Text::esc($CategoryName)?>
                            </option>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="center">
                        <input type="submit" class="button-primary" value="Search" />
                    </td>
                </tr>
            </table>
        </form>
    </div>
    <?php
}

// Default setting to have them shown
$DisableFreeTorrentTop10 = (isset($user['DisableFreeTorrentTop10']) ? $user['DisableFreeTorrentTop10'] : 0);

// Modify the Where query
if ($DisableFreeTorrentTop10) {
    $Where[] = "t.FreeTorrent='0'";
}

// The link should say the opposite of the current setting
$FreeleechToggleName = ($DisableFreeTorrentTop10 ? 'show' : 'hide');
$FreeleechToggleQuery = Format::get_url(array('freeleech', 'groups'));

if (!empty($FreeleechToggleQuery)) {
    $FreeleechToggleQuery .= '&amp;';
}

$FreeleechToggleQuery .= 'freeleech=' . $FreeleechToggleName;

$GroupByToggleName = ((isset($_GET['groups']) && $_GET['groups'] === 'show') ? 'hide' : 'show');
$GroupByToggleQuery = Format::get_url(array('freeleech', 'groups'));

if (!empty($GroupByToggleQuery)) {
    $GroupByToggleQuery .= '&amp;';
}

$GroupByToggleQuery .= 'groups=' . $GroupByToggleName;

$GroupBySum = '';
$GroupBy = '';
if (isset($_GET['groups']) && $_GET['groups'] === 'show') {
    $GroupBy = ' GROUP BY g.ID ';
    $GroupBySum = md5($GroupBy);
} ?>
    <div style="text-align: right;" class="linkbox">
        <a href="top10.php?<?=$FreeleechToggleQuery?>"
            class="brackets"><?=ucfirst($FreeleechToggleName)?>
            freeleech in Top 10</a>
        <?php if (check_perms('users_mod')) { ?>
        <a href="top10.php?<?=$GroupByToggleQuery?>"
            class="brackets"><?=ucfirst($GroupByToggleName)?>
            top groups</a>
        <?php } ?>
    </div>
    <?php

if (!empty($Where)) {
    $Where = '('.implode(' AND ', $Where).')';
    $WhereSum = md5($Where);
} else {
    $WhereSum = '';
}

$BaseQuery = "
SELECT
  t.`ID`,
  g.`id`,
  g.`title`,
  g.`subject`,
  g.`object`,
  g.`category_id`,
  g.`picture`,
  g.`tag_list`,
  t.`Media`,
  g.`year`,
  g.`workgroup`,
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

if ($Details === 'all' || $Details === 'day') {
    $TopTorrentsActiveLastDay = $cache->get_value('top10tor_day_'.$Limit.$WhereSum.$GroupBySum);
    if ($TopTorrentsActiveLastDay === false) {
        if ($cache->get_query_lock('top10')) {
            $DayAgo = time_minus(86400);
            $Query = $BaseQuery.' WHERE t.Seeders>0 AND ';

            if (!empty($Where)) {
                $Query .= $Where.' AND ';
            }

            $Query .= "
              t.Time>'$DayAgo'
              $GroupBy
              ORDER BY (t.Seeders + t.Leechers) DESC
              LIMIT $Limit;";

            $db->prepared_query($Query);
            $TopTorrentsActiveLastDay = $db->to_array(false, MYSQLI_NUM);
            $cache->cache_value('top10tor_day_'.$Limit.$WhereSum.$GroupBySum, $TopTorrentsActiveLastDay, 3600 * 2);
            $cache->clear_query_lock('top10');
        } else {
            $TopTorrentsActiveLastDay = false;
        }
    }
    generate_torrent_table('Most Active Torrents Uploaded in the Past Day', 'day', $TopTorrentsActiveLastDay, $Limit);
}
if ($Details === 'all' || $Details === 'week') {
    $TopTorrentsActiveLastWeek = $cache->get_value('top10tor_week_'.$Limit.$WhereSum.$GroupBySum);
    if ($TopTorrentsActiveLastWeek === false) {
        if ($cache->get_query_lock('top10')) {
            $WeekAgo = time_minus(604800);
            $Query = $BaseQuery.' WHERE ';

            if (!empty($Where)) {
                $Query .= $Where.' AND ';
            }

            $Query .= "
              t.Time>'$WeekAgo'
              $GroupBy
              ORDER BY (t.Seeders + t.Leechers) DESC
              LIMIT $Limit;";

            $db->prepared_query($Query);
            $TopTorrentsActiveLastWeek = $db->to_array(false, MYSQLI_NUM);
            $cache->cache_value('top10tor_week_'.$Limit.$WhereSum.$GroupBySum, $TopTorrentsActiveLastWeek, 3600 * 6);
            $cache->clear_query_lock('top10');
        } else {
            $TopTorrentsActiveLastWeek = false;
        }
    }
    generate_torrent_table('Most Active Torrents Uploaded in the Past Week', 'week', $TopTorrentsActiveLastWeek, $Limit);
}

if ($Details === 'all' || $Details === 'month') {
    $TopTorrentsActiveLastMonth = $cache->get_value('top10tor_month_'.$Limit.$WhereSum.$GroupBySum);
    if ($TopTorrentsActiveLastMonth === false) {
        if ($cache->get_query_lock('top10')) {
            $Query = $BaseQuery.' WHERE ';

            if (!empty($Where)) {
                $Query .= $Where.' AND ';
            }

            $Query .= "
              t.Time > NOW() - INTERVAL 1 MONTH
              $GroupBy
              ORDER BY (t.Seeders + t.Leechers) DESC
              LIMIT $Limit;";

            $db->prepared_query($Query);
            $TopTorrentsActiveLastMonth = $db->to_array(false, MYSQLI_NUM);
            $cache->cache_value('top10tor_month_'.$Limit.$WhereSum.$GroupBySum, $TopTorrentsActiveLastMonth, 3600 * 6);
            $cache->clear_query_lock('top10');
        } else {
            $TopTorrentsActiveLastMonth = false;
        }
    }
    generate_torrent_table('Most Active Torrents Uploaded in the Past Month', 'month', $TopTorrentsActiveLastMonth, $Limit);
}

if ($Details === 'all' || $Details === 'year') {
    $TopTorrentsActiveLastYear = $cache->get_value('top10tor_year_'.$Limit.$WhereSum.$GroupBySum);
    if ($TopTorrentsActiveLastYear === false) {
        if ($cache->get_query_lock('top10')) {
            // IMPORTANT NOTE - we use WHERE t.Seeders>200 in order to speed up this query. You should remove it!
            $Query = $BaseQuery.' WHERE ';
            if ($Details === 'all' && !$Filtered) {
                // $Query .= 't.Seeders>=200 AND ';
                if (!empty($Where)) {
                    $Query .= $Where.' AND ';
                }
            } elseif (!empty($Where)) {
                $Query .= $Where.' AND ';
            }

            $Query .= "
              t.Time > NOW() - INTERVAL 1 YEAR
              $GroupBy
              ORDER BY (t.Seeders + t.Leechers) DESC
              LIMIT $Limit;";

            $db->prepared_query($Query);
            $TopTorrentsActiveLastYear = $db->to_array(false, MYSQLI_NUM);
            $cache->cache_value('top10tor_year_'.$Limit.$WhereSum.$GroupBySum, $TopTorrentsActiveLastYear, 3600 * 6);
            $cache->clear_query_lock('top10');
        } else {
            $TopTorrentsActiveLastYear = false;
        }
    }
    generate_torrent_table('Most Active Torrents Uploaded in the Past Year', 'year', $TopTorrentsActiveLastYear, $Limit);
}

if ($Details === 'all' || $Details === 'overall') {
    $TopTorrentsActiveAllTime = $cache->get_value('top10tor_overall_'.$Limit.$WhereSum.$GroupBySum);
    if ($TopTorrentsActiveAllTime === false) {
        if ($cache->get_query_lock('top10')) {
            // IMPORTANT NOTE - we use WHERE t.Seeders>500 in order to speed up this query. You should remove it!
            $Query = $BaseQuery;
            if ($Details === 'all' && !$Filtered) {
                //$Query .= "t.Seeders>=500 ";
                if (!empty($Where)) {
                    $Query .= ' WHERE '.$Where;
                }
            } elseif (!empty($Where)) {
                $Query .= ' WHERE '.$Where;
            }

            $Query .= "
              $GroupBy
              ORDER BY (t.Seeders + t.Leechers) DESC
              LIMIT $Limit;";

            $db->prepared_query($Query);
            $TopTorrentsActiveAllTime = $db->to_array(false, MYSQLI_NUM);
            $cache->cache_value('top10tor_overall_'.$Limit.$WhereSum.$GroupBySum, $TopTorrentsActiveAllTime, 3600 * 6);
            $cache->clear_query_lock('top10');
        } else {
            $TopTorrentsActiveAllTime = false;
        }
    }
    generate_torrent_table('Most Active Torrents of All Time', 'overall', $TopTorrentsActiveAllTime, $Limit);
}

if (($Details === 'all' || $Details === 'snatched') && !$Filtered) {
    $TopTorrentsSnatched = $cache->get_value('top10tor_snatched_'.$Limit.$WhereSum.$GroupBySum);
    if ($TopTorrentsSnatched === false) {
        if ($cache->get_query_lock('top10')) {
            $Query = $BaseQuery;

            if (!empty($Where)) {
                $Query .= ' WHERE '.$Where;
            }

            $Query .= "
              $GroupBy
              ORDER BY t.Snatched DESC
              LIMIT $Limit;";

            $db->prepared_query($Query);
            $TopTorrentsSnatched = $db->to_array(false, MYSQLI_NUM);
            $cache->cache_value('top10tor_snatched_'.$Limit.$WhereSum.$GroupBySum, $TopTorrentsSnatched, 3600 * 6);
            $cache->clear_query_lock('top10');
        } else {
            $TopTorrentsSnatched = false;
        }
    }
    generate_torrent_table('Most Snatched Torrents', 'snatched', $TopTorrentsSnatched, $Limit);
}

if (($Details === 'all' || $Details === 'data') && !$Filtered) {
    $TopTorrentsTransferred = $cache->get_value('top10tor_data_'.$Limit.$WhereSum.$GroupBySum);
    if ($TopTorrentsTransferred === false) {
        if ($cache->get_query_lock('top10')) {
            // IMPORTANT NOTE - we use WHERE t.Snatched>100 in order to speed up this query. You should remove it!
            $Query = $BaseQuery;
            if ($Details === 'all') {
                //$Query .= " WHERE t.Snatched>=100 ";
                if (!empty($Where)) {
                    $Query .= ' WHERE '.$Where;
                }
            }

            $Query .= "
              $GroupBy
              ORDER BY Data DESC
              LIMIT $Limit;";

            $db->prepared_query($Query);
            $TopTorrentsTransferred = $db->to_array(false, MYSQLI_NUM);
            $cache->cache_value('top10tor_data_'.$Limit.$WhereSum.$GroupBySum, $TopTorrentsTransferred, 3600 * 6);
            $cache->clear_query_lock('top10');
        } else {
            $TopTorrentsTransferred = false;
        }
    }
    generate_torrent_table('Most Data Transferred Torrents', 'data', $TopTorrentsTransferred, $Limit);
}

if (($Details === 'all' || $Details === 'seeded') && !$Filtered) {
    $TopTorrentsSeeded = $cache->get_value('top10tor_seeded_'.$Limit.$WhereSum.$GroupBySum);
    if ($TopTorrentsSeeded === false) {
        if ($cache->get_query_lock('top10')) {
            $Query = $BaseQuery;
            if (!empty($Where)) {
                $Query .= ' WHERE '.$Where;
            }

            $Query .= "
              $GroupBy
              ORDER BY t.Seeders DESC
              LIMIT $Limit;";

            $db->prepared_query($Query);
            $TopTorrentsSeeded = $db->to_array(false, MYSQLI_NUM);
            $cache->cache_value('top10tor_seeded_'.$Limit.$WhereSum.$GroupBySum, $TopTorrentsSeeded, 3600 * 6);
            $cache->clear_query_lock('top10');
        } else {
            $TopTorrentsSeeded = false;
        }
    }
    generate_torrent_table('Best Seeded Torrents', 'seeded', $TopTorrentsSeeded, $Limit);
}
?>
</div>

<?php
View::footer();

// Generate a table based on data from most recent query to $db
function generate_torrent_table($Caption, $Tag, $Details, $Limit)
{
    global $user, $Categories, $ReleaseTypes, $GroupBy; ?>
<h3>Top <?="$Limit $Caption"?>
    <?php if (empty($_GET['advanced'])) { ?>
    <small class="top10_quantity_links">
        <?php
    switch ($Limit) {
      case 100: ?>
        &ndash; <a href="top10.php?details=<?=$Tag?>"
            class="brackets">Top
            10</a>
        &ndash; <span class="brackets">Top 100</span>
        &ndash; <a
            href="top10.php?type=torrents&amp;limit=250&amp;details=<?=$Tag?>"
            class="brackets">Top 250</a>
        <?php break;

      case 250: ?>
        &ndash; <a href="top10.php?details=<?=$Tag?>"
            class="brackets">Top
            10</a>
        &ndash; <a
            href="top10.php?type=torrents&amp;limit=100&amp;details=<?=$Tag?>"
            class="brackets">Top 100</a>
        &ndash; <span class="brackets">Top 250</span>
        <?php break;

      default: ?>
        &ndash; <span class="brackets">Top 10</span>
        &ndash; <a
            href="top10.php?type=torrents&amp;limit=100&amp;details=<?=$Tag?>"
            class="brackets">Top 100</a>
        &ndash; <a
            href="top10.php?type=torrents&amp;limit=250&amp;details=<?=$Tag?>"
            class="brackets">Top 250</a>
        <?php } ?>
    </small>
    <?php } ?>
</h3>

<table class="torrent_table cats numbering border">
    <tr class="colhead">
        <td class="center" style="width: 15px;"></td>
        <td class="cats_col"></td>
        <td>Name</td>
        <td style="text-align: right;">Size</td>
        <td style="text-align: right;">Data</td>
        <td style="text-align: right;" class="sign snatches">
            ↻
        </td>
        <td style="text-align: right;" class="sign seeders">
            &uarr;
        </td>
        <td style="text-align: right;" class="sign leechers">
            &darr;
        </td>
        <td style="text-align: right;">Peers</td>
    </tr>

    <?php
  // Server is already processing a top10 query. Starting another one will make things slow
  if ($Details === false) {
      ?>
    <tr class="row">
        <td colspan="9" class="center">
            Server is busy processing another top list request. Please try again in a minute
        </td>
    </tr>
</table>
<br />
<?php
    return;
  }
    // In the unlikely event that query finds 0 rows...
    if (empty($Details)) { ?>
<tr class="row">
    <td colspan="9" class="center">
        Found no torrents matching the criteria
    </td>
</tr>
</table>
<br />
<?php
    return;
    }

    $Rank = 0;
    foreach ($Details as $Detail) {
        $GroupIDs[] = $Detail[1];
    }

    $Artists = Artists::get_artists($GroupIDs);

    foreach ($Details as $Detail) {
        list($TorrentID, $GroupID, $GroupName, $GroupTitle2, $GroupNameJP, $GroupCategoryID, $WikiImage, $TagsList,
      $Media, $Year, $Studio, $Snatched, $Seeders, $Leechers, $Data, $Size) = $Detail;

        /*
        list($TorrentID, $GroupID, $GroupName, $GroupTitle2, $GroupNameJP, $GroupCategoryID, $WikiImage, $TagsList,
      $Media, $Year, $Snatched, $Seeders, $Leechers, $Data, $Size) = $Detail;
      */

        $IsBookmarked = Bookmarks::has_bookmarked('torrent', $GroupID);
        $IsSnatched = Torrents::has_snatched($TorrentID);

        $Rank++;

        // Generate torrent's title
        $DisplayName = '';

        $DisplayName .= "<a class='torrent_title' href='torrents.php?id=$GroupID&amp;torrentid=$TorrentID' ";
        if (!isset($user['CoverArt']) || $user['CoverArt']) {
            $DisplayName .= 'data-cover="'.ImageTools::process($WikiImage, 'thumb').'" ';
        }


        $Name = empty($GroupName) ? (empty($GroupTitle2) ? $GroupNameJP : $GroupTitle2) : $GroupName;
        $DisplayName .= "dir='ltr'>$Name</a>";

        // Append extra info to torrent title
        $ExtraInfo = '';
        $AddExtra = '&thinsp;|&thinsp;'; # breaking

        if (empty($GroupBy)) {
            # Year
            if ($Year) {
                $Label = '<br />📅&nbsp;';
                $DisplayName .= $Label."<a href='torrents.php?action=search&year=$Year'>$Year</a>";
            }

            # Studio
            if ($Studio) {
                $DisplayName .= "&ensp;🏫&nbsp;&nbsp;<a href='torrents.php?action=search&location=$Studio'>$Studio</a>";
            }

            # Authors
            if ($Artists) {
                # Emoji in classes/astists.class.php
                $Label = '&ensp;'; # breaking
                $DisplayName .= $Label.Artists::display_artists($Artists[$GroupID], true, true);
            }

            # Catalogue Number
            if ($CatalogueNumber) {
                $Label = '&ensp;🔑&nbsp;';
                $DisplayName .= $Label."<a href='torrents.php?action=search&numbers=$CatalogueNumber'>$CatalogueNumber</a>";
            }

            /*
            if ($Year > 0) {
                $ExtraInfo .= $Year;
            }
            */

            /*
            if ($Media) {
                $ExtraInfo .= " / $Media";
            }
            */

            /*
            if ($IsSnatched) {
                $ExtraInfo .= ' / ';
                $ExtraInfo .= Format::torrent_label('Snatched!', 'bold');
            }
            */

            /*
            if ($ExtraInfo !== '') {
                $ExtraInfo = "<br />$ExtraInfo";
            }
            */
        }

        $TorrentTags = new Tags($TagsList);

        // Get report info, use the cache if available. If not, add to it
        $Reported = false;
        $Reports = Torrents::get_reports($TorrentID);
        if (count($Reports) > 0) {
            $Reported = true;
        }

        // Print row?>
<tr
    class="torrent row<?=($IsBookmarked ? ' bookmarked' : '') . ($IsSnatched ? ' snatched_torrent' : '')?>">
    <td style="padding: 8px; text-align: center;"><strong><?=$Rank?></strong></td>
    <td class="center cats_col">
        <div title="<?=Format::pretty_category($GroupCategoryID)?>"
            class="tooltip <?=Format::css_category($GroupCategoryID)?>">
        </div>
    </td>
    <td class="big_info">
        <div class="group_info clear">

            <span class="u-pull-right">
                <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$user['AuthKey']?>&amp;torrent_pass=<?=$user['torrent_pass']?>"
                    title="Download" class="brackets tooltip">DL</a>
                </span>

            <?=$DisplayName?> <?=$ExtraInfo?><?php if ($Reported) { ?> - <strong
                class="torrent_label tl_reported">Reported</strong><?php } ?>
            <?php
    if ($IsBookmarked) {
        ?>
            <span class="remove_bookmark u-pull-right">
                <a href="#" id="bookmarklink_torrent_<?=$GroupID?>"
                    class="bookmarklink_torrent_<?=$GroupID?> brackets"
                    onclick="Unbookmark('torrent', <?=$GroupID?>, 'Bookmark'); return false;">Remove
                    bookmark</a>
            </span>
            <?php
    } else { ?>
            <span class="add_bookmark u-pull-right">
                <a href="#" id="bookmarklink_torrent_<?=$GroupID?>"
                    class="bookmarklink_torrent_<?=$GroupID?> brackets"
                    onclick="Bookmark('torrent', <?=$GroupID?>, 'Remove bookmark'); return false;">Bookmark</a>
            </span>
            <?php } ?>
            <div class="tags"><?=$TorrentTags->format()?>
            </div>
        </div>
    </td>
    <td class="number_column nobr"><?=Format::get_size($Size)?>
    </td>
    <td class="number_column nobr"><?=Format::get_size($Data)?>
    </td>
    <td class="number_column"><?=Text::float((float)$Snatched)?>
    </td>
    <td class="number_column"><?=Text::float((float)$Seeders)?>
    </td>
    <td class="number_column"><?=Text::float((float)$Leechers)?>
    </td>
    <td class="number_column"><?=Text::float($Seeders + $Leechers)?>
    </td>
</tr>
<?php
    } // foreach ($Details as $Detail)
?>
</table>
<br />
<?php
}
