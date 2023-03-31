<?php
#declare(strict_types=1);

$app = \Gazelle\App::go();

/*
User collage subscription page
*/
if (!check_perms('site_collages_subscribe')) {
    error(403);
}

View::header('Subscribed collections', 'browse,collage');

$ShowAll = !empty($_GET['showall']);

if (!$ShowAll) {
    $sql = "
    SELECT
      c.ID,
      c.Name,
      c.NumTorrents,
      s.LastVisit
    FROM collages AS c
      JOIN users_collage_subs AS s ON s.CollageID = c.ID
      JOIN collages_torrents AS ct ON ct.CollageID = c.ID
    WHERE s.UserID = {$app->user->core['id']} AND c.Deleted = '0'
      AND ct.AddedOn > s.LastVisit
    GROUP BY c.ID";
} else {
    $sql = "
    SELECT
      c.ID,
      c.Name,
      c.NumTorrents,
      s.LastVisit
    FROM collages AS c
      JOIN users_collage_subs AS s ON s.CollageID = c.ID
      LEFT JOIN collages_torrents AS ct ON ct.CollageID = c.ID
    WHERE s.UserID = {$app->user->core['id']} AND c.Deleted = '0'
    GROUP BY c.ID";
}

$app->dbOld->prepared_query($sql);
$NumResults = $app->dbOld->record_count();
$CollageSubs = $app->dbOld->to_array();
?>
<div>
  <div class="header">
    <h2>Subscribed collections<?=($ShowAll ? '' : ' with new additions')?>
    </h2>

    <div class="linkbox">
      <?php
if ($ShowAll) {
    ?>
      <br /><br />
      <a href="userhistory.php?action=subscribed_collages&amp;showall=0" class="brackets">Only display collections with
        new additions</a>&nbsp;&nbsp;&nbsp;
      <?php
} else {
    ?>
      <br /><br />
      <a href="userhistory.php?action=subscribed_collages&amp;showall=1" class="brackets">Show all subscribed
        collections</a>&nbsp;&nbsp;&nbsp;
      <?php
}
?>
      <a href="userhistory.php?action=catchup_collages&amp;auth=<?=$app->user->extra['AuthKey']?>"
        class="brackets">Catch up</a>&nbsp;&nbsp;&nbsp;
    </div>
  </div>
  <?php
if (!$NumResults) {
    ?>
  <div class="center">
    No subscribed collections<?=($ShowAll ? '' : ' with new additions')?>
  </div>
  <?php
} else {
    $HideGroup = '';
    $ActionTitle = 'Hide';
    $ActionURL = 'hide';
    $ShowGroups = 0;

    foreach ($CollageSubs as $Collage) {
        $TorrentTable = '';

        list($CollageID, $CollageName, $CollageSize, $LastVisit) = $Collage;
        $RS = $app->dbOld->prepared_query("
      SELECT GroupID
      FROM collages_torrents
      WHERE CollageID = $CollageID
        AND AddedOn > '" . db_string($LastVisit) . "'
      ORDER BY AddedOn");
        $NewTorrentCount = $app->dbOld->record_count();

        $GroupIDs = $app->dbOld->collect('GroupID', false);
        if (count($GroupIDs) > 0) {
            $TorrentList = Torrents::get_groups($GroupIDs);
        } else {
            $TorrentList = [];
        }

        $Artists = Artists::get_artists($GroupIDs);
        $Number = 0;

        foreach ($GroupIDs as $GroupID) {
            if (!isset($TorrentList[$GroupID])) {
                continue;
            }
            $Group = $TorrentList[$GroupID];
            extract(Torrents::array_group($Group));

            $TorrentTags = new Tags($TagList);

            $DisplayName = '';

            if (isset($Artists)) {
                $DisplayName .= '<div>'.Artists::display_artists($Artists).'</div> ';
            }
            $DisplayName .= "<a class=\"torrentTitle\" href=\"torrents.php?id=$GroupID\" ";
            if (!isset($app->user->extra['CoverArt']) || $app->user->extra['CoverArt']) {
                $DisplayName .= 'data-cover="'.ImageTools::process($WikiImage).'" ';
            }
            $DisplayName .= "dir=\"ltr\">".($GroupName ? $GroupName : ($GroupTitle2 ? $GroupTitle2 : $GroupNameJP))."</a>";
            if ($GroupYear > 0) {
                $DisplayName = "$DisplayName [$GroupYear]";
            }

            $SnatchedGroupClass = $GroupFlags['IsSnatched'] ? ' snatched_group' : '';

            // Start an output buffer, so we can store this output in $TorrentTable
            ob_start();
            if (count($Torrents) > 1 || $GroupCategoryID == 1) {
                ?>
  <tr class="group<?=$SnatchedGroupClass?>"
    id="group_<?=$CollageID?>_<?=$GroupID?>">
    <td class="center">
      <div
        id="showimg_<?=$CollageID?>_<?=$GroupID?>"
        class="<?=($ShowGroups ? 'hide' : 'show')?>_torrents">
        <a class="tooltip show_torrents_link"
          onclick="toggle_group('<?=$CollageID?>_<?=$GroupID?>', this, event);"
          title="Toggle this group (Hold &quot;Shift&quot; to toggle all groups)"></a>
      </div>
    </td>
    <td class="center categoryColumn">
      <div title="<?=Format::pretty_category($GroupCategoryID)?>"
        class="tooltip <?=Format::css_category($GroupCategoryID)?>">
      </div>
    </td>
    <td colspan="5" class="big_info">
      <div class="group_info clear">
        <strong><?=$DisplayName?></strong>
        <div class="tags"><?=$TorrentTags->format()?>
          </tags>
        </div>
    </td>
  </tr>
  <?php
        foreach ($Torrents as $TorrentID => $Torrent) {
            $SnatchedTorrentClass = $Torrent['IsSnatched'] ? ' snatched_torrent' : ''; ?>
  <tr
    class="group_torrent groupid_<?=$CollageID?>_<?=$GroupID?> hidden<?=$SnatchedTorrentClass . $SnatchedGroupClass?>">
    <td colspan="3">
      <span>
        <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$app->user->extra['AuthKey']?>&amp;torrent_pass=<?=$app->user->extra['torrent_pass']?>"
          title="Download" class="brackets tooltip">DL</a>
      </span>
      &nbsp;&nbsp;&raquo;&nbsp;<a
        href="torrents.php?id=<?=$GroupID?>&amp;torrentid=<?=$TorrentID?>"><?=Torrents::torrent_info($Torrent)?></a>
    </td>
    <td class="number_column nobr"><?=Format::get_size($Torrent['Size'])?>
    </td>
    <td class="number_column"><?=Text::float($Torrent['Snatched'])?>
    </td>
    <td
      class="number_column<?=($Torrent['Seeders'] == 0) ? ' r00' : ''?>">
      <?=Text::float($Torrent['Seeders'])?>
    </td>
    <td class="number_column"><?=Text::float($Torrent['Leechers'])?>
    </td>
  </tr>
  <?php
        }
            } else {
                // Viewing a type that does not require grouping

                $TorrentID = key($Torrents);
                $Torrent = current($Torrents);

                $DisplayName = '';

                if (isset($Artists)) {
                    $DisplayName .= '<div>'.Artists::display_artists($Artists).'</div> ';
                }

                $DisplayName .= "<a class=\"torrentTitle\" href=\"torrents.php?id=$GroupID\" ";
                if (!isset($app->user->extra['CoverArt']) || $app->user->extra['CoverArt']) {
                    $DisplayName .= 'data-cover="'.ImageTools::process($WikiImage).'" ';
                }

                $DisplayName .= "dir=\"ltr\">".($GroupName ? $GroupName : ($GroupTitle2 ? $GroupTitle2 : $GroupNameJP))."</a>";
                if ($Torrent['IsSnatched']) {
                    $DisplayName .= ' ' . Format::torrent_label('Snatched', 'bold');
                }

                if (!empty($Torrent['FreeTorrent'])) {
                    $DisplayName .= ' ' . Format::torrent_label('Freeleech', 'important_text_alt');
                }
                $SnatchedTorrentClass = $Torrent['IsSnatched'] ? ' snatched_torrent' : ''; ?>

  <tr class="torrent<?=$SnatchedTorrentClass?>"
    id="group_<?=$CollageID?>_<?=$GroupID?>">
    <td></td>
    <td class="center">
      <div title="<?=Format::pretty_category($GroupCategoryID)?>"
        class="tooltip <?=Format::css_category($GroupCategoryID)?>">
      </div>
    </td>
    <td class="big_info">
      <div class="group_info clear">
        <span>
          [ <a
            href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$app->user->extra['AuthKey']?>&amp;torrent_pass=<?=$app->user->extra['torrent_pass']?>"
            class="tooltip" title="Download">DL</a>
          | <a
            href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>"
            class="tooltip" title="Report">RP</a> ]
        </span>
        <strong><?=$DisplayName?></strong>
        <div class="tags"><?=$TorrentTags->format()?>
        </div>
      </div>
    </td>
    <td class="number_column nobr"><?=Format::get_size($Torrent['Size'])?>
    </td>
    <td class="number_column"><?=Text::float($Torrent['Snatched'])?>
    </td>
    <td
      class="number_column<?=($Torrent['Seeders'] == 0) ? ' r00' : ''?>">
      <?=Text::float($Torrent['Seeders'])?>
    </td>
    <td class="number_column"><?=Text::float($Torrent['Leechers'])?>
    </td>
  </tr>
  <?php
            }
            $TorrentTable .= ob_get_clean();
        } ?>
  <!-- I hate that proton is making me do it like this -->
  <!--<div class="head colhead_dark" style="margin-top: 8px;">-->
  <table style="margin-top: 8px;" class="subscribed_collages_table">
    <tr class="colhead_dark">
      <td>
        <span class="u-pull-left">
          <strong><a href="collage.php?id=<?=$CollageID?>"><?=$CollageName?></a></strong> (<?=$NewTorrentCount?> new torrent<?=($NewTorrentCount == 1 ? '' : 's')?>)
        </span>&nbsp;
        <span class="u-pull-right">
          <a data-toggle-target="#discog_table_<?=$CollageID?>"
            data-toggle-replace="<?=($ShowAll ? 'Hide' : 'Show')?>"
            class="brackets"><?=($ShowAll ? 'Show' : 'Hide')?></a>&nbsp;&nbsp;&nbsp;<a
            href="userhistory.php?action=catchup_collages&amp;auth=<?=$app->user->extra['AuthKey']?>&amp;collageid=<?=$CollageID?>"
            class="brackets">Catch up</a>&nbsp;&nbsp;&nbsp;<a href="#"
            onclick="CollageSubscribe(<?=$CollageID?>); return false;"
            id="subscribelink<?=$CollageID?>"
            class="brackets">Unsubscribe</a>
        </span>
      </td>
    </tr>
  </table>
  <!--</div>-->
  <table
    class="torrent_table<?=$ShowAll ? ' hidden' : ''?>"
    id="discog_table_<?=$CollageID?>">
    <tr class="colhead">
      <td class="small"></td>
      <td class="small categoryColumn"></td>
      <td><strong>Torrents</strong></td>
      <td>Size</td>
      <td class="sign snatches">
        ↻
      </td>
      <td class="sign seeders">
        &uarr;
      </td>
      <td class="sign leechers">
        &darr;
      </td>
    </tr>
    <?=$TorrentTable?>
  </table>
  <?php
    } // foreach ()
} // else -- if (empty($NumResults))
?>
</div>

<?php
View::footer();
