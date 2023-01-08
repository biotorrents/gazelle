<?php
declare(strict_types=1);

if (!check_perms('site_torrents_notify')) {
    error(403);
}

define('NOTIFICATIONS_PER_PAGE', 50);
define('NOTIFICATIONS_MAX_SLOWSORT', 10000);

$OrderBys = array(
    'time'     => array('unt' => 'unt.TorrentID'),
    'size'     => array('t'   => 't.Size'),
    'snatches' => array('t'   => 't.Snatched'),
    'seeders'  => array('t'   => 't.Seeders'),
    'leechers' => array('t'   => 't.Leechers'),
    'year'     => array('tg'  => 'tnt.Year'));

if (empty($_GET['order_by']) || !isset($OrderBys[$_GET['order_by']])) {
    $_GET['order_by'] = 'time';
}
$OrderTbl = key($OrderBys[$_GET['order_by']]);
$OrderCol = current($OrderBys[$_GET['order_by']]);

if (!empty($_GET['order_way']) && $_GET['order_way'] === 'asc') {
    $OrderWay = 'ASC';
} else {
    $OrderWay = 'DESC';
}

if (!empty($_GET['filterid']) && is_number($_GET['filterid'])) {
    $FilterID = $_GET['filterid'];
} else {
    $FilterID = false;
}

list($Page, $Limit) = Format::page_limit(NOTIFICATIONS_PER_PAGE);

// The "order by x" links on columns headers
function header_link($SortKey, $DefaultWay = 'desc')
{
    global $OrderWay;
    if ($SortKey === $_GET['order_by']) {
        if ($OrderWay === 'DESC') {
            $NewWay = 'asc';
        } else {
            $NewWay = 'desc';
        }
    } else {
        $NewWay = $DefaultWay;
    }
    return "?action=notify&amp;order_way=$NewWay&amp;order_by=$SortKey&amp;".Format::get_url(array('page', 'order_way', 'order_by'));
}
//Perhaps this should be a feature at some point
if (check_perms('users_mod') && !empty($_GET['userid']) && is_number($_GET['userid']) && $_GET['userid'] != $user['ID']) {
    $UserID = $_GET['userid'];
    $Sneaky = true;
} else {
    $Sneaky = false;
    $UserID = $user['ID'];
}

// Sorting by release year requires joining torrents_group, which is slow. Using a temporary table
// makes it speedy enough as long as there aren't too many records to create
if ($OrderTbl === 'tg') {
    $db->query("
    SELECT COUNT(*)
    FROM users_notify_torrents AS unt
      JOIN torrents AS t ON t.ID=unt.TorrentID
    WHERE unt.UserID=$UserID".
    ($FilterID
      ? " AND FilterID=$FilterID"
      : ''));
    list($TorrentCount) = $db->next_record();
    if ($TorrentCount > NOTIFICATIONS_MAX_SLOWSORT) {
        error('Due to performance issues, torrent lists with more than '.Text::float(NOTIFICATIONS_MAX_SLOWSORT).' items cannot be ordered by release year.');
    }

    $db->query("
    CREATE TEMPORARY TABLE temp_notify_torrents
      (TorrentID int, GroupID int, UnRead tinyint, FilterID int, Year smallint, PRIMARY KEY(GroupID, TorrentID), KEY(Year))
    ENGINE=MyISAM");
    $db->query("
    INSERT IGNORE INTO temp_notify_torrents (TorrentID, GroupID, UnRead, FilterID)
    SELECT t.ID, t.GroupID, unt.UnRead, unt.FilterID
    FROM users_notify_torrents AS unt
      JOIN torrents AS t ON t.ID=unt.TorrentID
    WHERE unt.UserID=$UserID".
    ($FilterID
      ? " AND unt.FilterID=$FilterID"
      : ''));
    $db->query("
    UPDATE temp_notify_torrents AS tnt
      JOIN torrents_group AS tg ON tnt.GroupID = tg.ID
    SET tnt.Year = tg.Year");

    $db->query("
    SELECT TorrentID, GroupID, UnRead, FilterID
    FROM temp_notify_torrents AS tnt
    ORDER BY $OrderCol $OrderWay, GroupID $OrderWay
    LIMIT $Limit");
    $Results = $db->to_array(false, MYSQLI_ASSOC, false);
} else {
    $db->query("
    SELECT
      SQL_CALC_FOUND_ROWS
      unt.TorrentID,
      unt.UnRead,
      unt.FilterID,
      t.GroupID
    FROM users_notify_torrents AS unt
      JOIN torrents AS t ON t.ID = unt.TorrentID
    WHERE unt.UserID = $UserID".
    ($FilterID
      ? " AND unt.FilterID = $FilterID"
      : '')."
    ORDER BY $OrderCol $OrderWay
    LIMIT $Limit");
    $Results = $db->to_array(false, MYSQLI_ASSOC, false);
    $db->query('SELECT FOUND_ROWS()');
    list($TorrentCount) = $db->next_record();
}

$GroupIDs = $FilterIDs = $UnReadIDs = [];
foreach ($Results as $Torrent) {
    $GroupIDs[$Torrent['GroupID']] = 1;
    $FilterIDs[$Torrent['FilterID']] = 1;
    if ($Torrent['UnRead']) {
        $UnReadIDs[] = $Torrent['TorrentID'];
    }
}
$Pages = Format::get_pages($Page, $TorrentCount, NOTIFICATIONS_PER_PAGE, 9);

if (!empty($GroupIDs)) {
    $GroupIDs = array_keys($GroupIDs);
    $FilterIDs = array_keys($FilterIDs);
    $TorrentGroups = Torrents::get_groups($GroupIDs);

    // Get the relevant filter labels
    $db->query('
    SELECT ID, Label, Artists
    FROM users_notify_filters
    WHERE ID IN ('.implode(',', $FilterIDs).')');
    $Filters = $db->to_array('ID', MYSQLI_ASSOC, array('Artists'));
    foreach ($Filters as &$Filter) {
        $Filter['Artists'] = explode('|', trim($Filter['Artists'], '|'));
        foreach ($Filter['Artists'] as &$FilterArtist) {
            $FilterArtist = mb_strtolower($FilterArtist, 'UTF-8');
        }
        $Filter['Artists'] = array_flip($Filter['Artists']);
    }
    unset($Filter);

    if (!empty($UnReadIDs)) {
        //Clear before header but after query so as to not have the alert bar on this page load
        $db->query("
      UPDATE users_notify_torrents
      SET UnRead = '0'
      WHERE UserID = ".$user['ID'].'
        AND TorrentID IN ('.implode(',', $UnReadIDs).')');
        $cache->delete_value('notifications_new_'.$user['ID']);
    }
}
if ($Sneaky) {
    $UserInfo = User::user_info($UserID);
    View::header($UserInfo['Username'].'\'s notifications', 'notifications');
} else {
    View::header('My notifications', 'notifications');
}
?>
<div>
  <div class="header">
    <h2>Latest notifications</h2>
  </div>
  <div class="linkbox">
    <?php if ($FilterID) { ?>
    <a href="torrents.php?action=notify<?=($Sneaky ? "&amp;userid=$UserID" : '')?>"
      class="brackets">View all</a>&nbsp;&nbsp;&nbsp;
    <?php } elseif (!$Sneaky) { ?>
    <a href="torrents.php?action=notify_clear&amp;auth=<?=$user['AuthKey']?>"
      class="brackets">Clear all old</a>&nbsp;&nbsp;&nbsp;
    <a href="#" onclick="clearSelected(); return false;" class="brackets">Clear selected</a>&nbsp;&nbsp;&nbsp;
    <a href="torrents.php?action=notify_catchup&amp;auth=<?=$user['AuthKey']?>"
      class="brackets">Catch up</a>&nbsp;&nbsp;&nbsp;
    <?php } ?>
    <a href="user.php?action=notify" class="brackets">Edit filters</a>&nbsp;&nbsp;&nbsp;
  </div>
  <?php if ($TorrentCount > NOTIFICATIONS_PER_PAGE) { ?>
  <div class="linkbox">
    <?=$Pages?>
  </div>
  <?php
}
if (empty($Results)) {
    ?>
  <table class="layout border slight_margin">
    <tr class="row">
      <td colspan="8" class="center">
        No new notifications found! <a href="user.php?action=notify" class="brackets">Edit notification filters</a>
      </td>
    </tr>
  </table>
  <?php
} else {
        $FilterGroups = [];
        foreach ($Results as $Result) {
            if (!isset($FilterGroups[$Result['FilterID']])) {
                $FilterGroups[$Result['FilterID']] = [];
                $FilterGroups[$Result['FilterID']]['FilterLabel'] = isset($Filters[$Result['FilterID']])
        ? $Filters[$Result['FilterID']]['Label']
        : false;
            }
            $FilterGroups[$Result['FilterID']][] = $Result;
        }

        foreach ($FilterGroups as $FilterID => $FilterResults) {
            ?>
  <div class="header">
    <h3>
      <?php if ($FilterResults['FilterLabel'] !== false) { ?>
      Matches for <a
        href="torrents.php?action=notify&amp;filterid=<?=$FilterID.($Sneaky ? "&amp;userid=$UserID" : '')?>"><?=$FilterResults['FilterLabel']?></a>
      <?php } else { ?>
      Matches for unknown filter[<?=$FilterID?>]
      <?php } ?>
    </h3>
  </div>
  <div class="linkbox notify_filter_links">
    <?php if (!$Sneaky) { ?>
    <a href="#"
      onclick="clearSelected(<?=$FilterID?>); return false;"
      class="brackets">Clear selected in filter</a>
    <a href="torrents.php?action=notify_clear_filter&amp;filterid=<?=$FilterID?>&amp;auth=<?=$user['AuthKey']?>"
      class="brackets">Clear all old in filter</a>
    <a href="torrents.php?action=notify_catchup_filter&amp;filterid=<?=$FilterID?>&amp;auth=<?=$user['AuthKey']?>"
      class="brackets">Mark all in filter as read</a>
    <?php } ?>
  </div>
  <form class="manage_form" name="torrents"
    id="notificationform_<?=$FilterID?>" action="">
    <table class="torrent_table cats checkboxes border slight_margin">
      <tr class="colhead">
        <td style="text-align: center;"><input type="checkbox" name="toggle"
            onclick="toggleChecks('notificationform_<?=$FilterID?>', this, '.notify_box')" />
        </td>
        <td class="small cats_col"></td>
        <td style="width: 100%;">Name<?=$TorrentCount <= NOTIFICATIONS_MAX_SLOWSORT ? ' / <a href="'.header_link('year').'">Year</a>' : ''?>
        </td>
        <td>Files</td>
        <td><a
            href="<?=header_link('time')?>">Time</a>
        </td>
        <td><a
            href="<?=header_link('size')?>">Size</a>
        </td>
        <td class="sign snatches"><a
            href="<?=header_link('snatches')?>">↻</a>
        </td>
        <td class="sign seeders"><a
            href="<?=header_link('seeders')?>">&uarr;</a>
        </td>
        <td class="sign leechers"><a
            href="<?=header_link('leechers')?>">&darr;</a>
        </td>
      </tr>
      <?php
    unset($FilterResults['FilterLabel']);
            foreach ($FilterResults as $Result) {
                $TorrentID = $Result['TorrentID'];
                $GroupID = $Result['GroupID'];
                $GroupInfo = $TorrentGroups[$Result['GroupID']];
                if (!isset($GroupInfo['Torrents'][$TorrentID]) || !isset($GroupInfo['ID'])) {
                    // If $GroupInfo['ID'] is unset, the torrent group associated with the torrent doesn't exist
                    continue;
                }
                $GroupName = empty($GroupInfo['Name']) ? (empty($GroupInfo['Title2']) ? $GroupInfo['NameJP'] : $GroupInfo['Title2']) : $GroupInfo['Name'];
                $TorrentInfo = $GroupInfo['Torrents'][$TorrentID];
                // generate torrent's title
                $DisplayName = '';
                if (!empty($GroupInfo['Artists'])) {
                    $MatchingArtists = [];
                    foreach ($GroupInfo['Artists'] as $GroupArtists) {
                        foreach ($GroupArtists as $GroupArtist) {
                            if (isset($Filters[$FilterID]['Artists'][mb_strtolower($GroupArtist['name'], 'UTF-8')])) {
                                $MatchingArtists[] = $GroupArtist['name'];
                            }
                        }
                    }
                    $MatchingArtistsText = (!empty($MatchingArtists) ? 'Caught by filter for '.implode(', ', $MatchingArtists) : '');
                    $DisplayName = Artists::display_artists($GroupInfo['Artists'], true, true);
                }
                $DisplayName .= "<a href=\"torrents.php?id=$GroupID&amp;torrentid=$TorrentID#torrent$TorrentID\" ";
                if (!isset($user['CoverArt']) || $user['CoverArt']) {
                    $DisplayName .= 'data-cover="'.ImageTools::process($GroupInfo['WikiImage'], 'thumb').'" ';
                }
                $DisplayName .= "class=\"tooltip\" title=\"View torrent\" dir=\"ltr\">" . $GroupName . '</a>';

                $GroupCategoryID = $GroupInfo['CategoryID'];
                /*
                      if ($GroupCategoryID === 1) {
                */
                if ($GroupInfo['Year'] > 0) {
                    $DisplayName .= " [$GroupInfo[Year]]";
                }
                /*
                        if ($GroupInfo['ReleaseType'] > 0) {
                          $DisplayName .= ' ['.$ReleaseTypes[$GroupInfo['ReleaseType']].']';
                        }
                      }
                */

                // append extra info to torrent title
                $ExtraInfo = Torrents::torrent_info($TorrentInfo, true, true);

                $TorrentTags = new Tags($GroupInfo['TagList']);

                if ($GroupInfo['TagList'] === '') {
                    $TorrentTags->set_primary($Categories[$GroupCategoryID - 1]);
                }

                // echo row?>
      <tr
        class="torrent torrent_row<?=($TorrentInfo['IsSnatched'] ? ' snatched_torrent' : '') . ($GroupInfo['Flags']['IsSnatched'] ? ' snatched_group' : '') . ($MatchingArtistsText ? ' tooltip" title="'.Text::esc($MatchingArtistsText) : '')?>"
        id="torrent<?=$TorrentID?>">
        <td style="text-align: center;">
          <input type="checkbox"
            class="notify_box notify_box_<?=$FilterID?>"
            value="<?=$TorrentID?>"
            id="clear_<?=$TorrentID?>" tabindex="1" />
        </td>
        <td class="center cats_col">
        </td>
        <td class="big_info">
          <div class="group_info clear">
            <span>
              [ <a
                href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$user['AuthKey']?>&amp;torrent_pass=<?=$user['torrent_pass']?>"
                class="tooltip" title="Download">DL</a>
              <?php if (Torrents::can_use_token($TorrentInfo)) { ?>
              | <a
                href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$user['AuthKey']?>&amp;torrent_pass=<?=$user['torrent_pass']?>&amp;usetoken=1"
                class="tooltip" title="Use a FL Token"
                onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
              <?php
      }
                if (!$Sneaky) { ?>
              | <a href="#"
                onclick="clearItem(<?=$TorrentID?>); return false;"
                class="tooltip" title="Remove from notifications list">CL</a>
              <?php } ?> ]
            </span>
            <?=$DisplayName?>
            <div class="torrent_info">
              <?=$ExtraInfo?>
              <?php if ($Result['UnRead']) {
                    echo '<strong class="new">New!</strong>';
                } ?>
              <?php if (Bookmarks::has_bookmarked('torrent', $GroupID)) { ?>
              <span class="remove_bookmark u-pull-right">
                <a href="#" id="bookmarklink_torrent_<?=$GroupID?>"
                  class="brackets"
                  onclick="Unbookmark('torrent', <?=$GroupID?>, 'Bookmark'); return false;">Remove
                  bookmark</a>
              </span>
              <?php } else { ?>
              <span class="add_bookmark u-pull-right">
                <a href="#" id="bookmarklink_torrent_<?=$GroupID?>"
                  class="brackets"
                  onclick="Bookmark('torrent', <?=$GroupID?>, 'Remove bookmark'); return false;">Bookmark</a>
                <?php } ?>
              </span>
            </div>
            <div class="tags"><?=$TorrentTags->format()?>
            </div>
          </div>
        </td>
        <td><?=$TorrentInfo['FileCount']?>
        </td>
        <td class="number_column nobr"><?=time_diff($TorrentInfo['Time'])?>
        </td>
        <td class="number_column nobr"><?=Format::get_size($TorrentInfo['Size'])?>
        </td>
        <td class="number_column"><?=Text::float($TorrentInfo['Snatched'])?>
        </td>
        <td class="number_column"><?=Text::float($TorrentInfo['Seeders'])?>
        </td>
        <td class="number_column"><?=Text::float($TorrentInfo['Leechers'])?>
        </td>
      </tr>
      <?php
            } ?>
    </table>
  </form>
  <?php
        }
    }

  if ($Pages) { ?>
  <div class="linkbox"><?=$Pages?>
  </div>
  <?php } ?>
</div>
<?php View::footer();
