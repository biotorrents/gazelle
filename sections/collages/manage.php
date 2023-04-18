<?php
declare(strict_types=1);

$app = \Gazelle\App::go();

$CollageID = (int) $_GET['collageid'];
Security::int($CollageID);

$app->dbOld->prepared_query("
SELECT
  `Name`,
  `UserID`,
  `CategoryID`
FROM
  `collages`
WHERE
  `ID` = '$CollageID'
");
list($Name, $UserID, $CategoryID) = $app->dbOld->next_record();

if ($CategoryID === 0 && $UserID !== $app->user->core['id'] && !check_perms('site_collages_delete')) {
    error(403);
}

if ($CategoryID === array_search(ARTIST_COLLAGE, $CollageCats)) {
    error(404);
}

$app->dbOld->prepared_query("
SELECT
  ct.`GroupID`,
  um.`ID`,
  um.`Username`,
  ct.`Sort`,
  tg.`identifier`
FROM
  `collages_torrents` AS ct
JOIN `torrents_group` AS tg
ON
  tg.`id` = ct.`GroupID`
LEFT JOIN `users_main` AS um
ON
  um.`ID` = ct.`UserID`
WHERE
  ct.`CollageID` = '$CollageID'
ORDER BY
  ct.`Sort`
");

$GroupIDs = $app->dbOld->collect('GroupID');

$CollageDataList = $app->dbOld->to_array('GroupID', MYSQLI_ASSOC);
if (count($GroupIDs) > 0) {
    $TorrentList = Torrents::get_groups($GroupIDs);
} else {
    $TorrentList = [];
}

View::header(
    "Manage collection $Name",
);

?>
<div>
  <div class="header">
    <h2>Manage collection <a
        href="collages.php?id=<?=$CollageID?>"><?=$Name?></a></h2>
  </div>
  <table width="100%" class="layout">
    <tr class="colhead">
      <td id="sorting_head">Sorting</td>
    </tr>
    <tr>
      <td id="drag_drop_textnote">
        <ul>
          <li>Click on the headings to organize columns automatically.</li>
          <li>Sort multiple columns simultaneously by holding down the shift key and clicking other column headers.</li>
          <li>Click and drag any row to change its order.</li>
          <li>Press "Save All Changes" when you are finished sorting.</li>
          <li>Press "Edit" or "Remove" to simply modify one entry.</li>
        </ul>
      </td>
    </tr>
  </table>

  <div class="drag_drop_save hidden">
    <input type="button" name="submit" value="Save All Changes" class="save_sortable_collage" />
  </div>
  <table id="manage_collage_table">
    <thead>
      <tr class="colhead">
        <th style="width: 7%;" data-sorter="false">Order</th>
        <th style="width: 1%;"><span><abbr class="tooltip" title="Current rank">#</abbr></span></th>
        <th style="width: 7%;"><span>Cat.&nbsp;#</span></th>
        <th style="width: 1%;"><span>Year</span></th>
        <th style="width: 15%;" data-sorter="ignoreArticles"><span>Artist</span></th>
        <th data-sorter="ignoreArticles"><span>Torrent</span></th>
        <th style="width: 1%;"><span>User</span></th>
        <th style="width: 1%; text-align: right;" class="nobr" data-sorter="false"><span><abbr class="tooltip"
              title="Modify an individual row">Tweak</abbr></span></th>
      </tr>
    </thead>
    <tbody>
      <?php

  $Number = 0;
foreach ($GroupIDs as $GroupID) {
    if (!isset($TorrentList[$GroupID])) {
        continue;
    }
    $Group = $TorrentList[$GroupID];
    extract(Torrents::array_group($Group));
    list(, $UserID, $Username, $Sort, $CatNum) = array_values($CollageDataList[$GroupID]);

    $Number++;

    $DisplayName = '';
    if (!empty($ExtendedArtists[1]) || !empty($ExtendedArtists[4]) || !empty($ExtendedArtists[5]) || !empty($ExtendedArtists[6])) {
        unset($ExtendedArtists[2]);
        unset($ExtendedArtists[3]);
        $DisplayName .= Artists::display_artists($ExtendedArtists, true, false);
    } elseif (count($Artists) > 0) {
        $DisplayName .= Artists::display_artists($Artists, true, false);
    }
    $GroupNameLang = $title ? $title : ($subject ? $subject : $object);
    $TorrentLink = "<a href=\"torrents.php?id=$GroupID\" class=\"tooltip\" title=\"View torrent group\">$GroupNameLang</a>";
    $GroupYear = $GroupYear > 0 ? $GroupYear : ''; ?>
      <tr class="drag row" id="li_<?=$GroupID?>">
        <form class="manage_form" name="collage" action="collages.php" method="post">
          <td>
            <input class="sort_numbers" type="text" name="sort"
              value="<?=$Sort?>"
              id="sort_<?=$GroupID?>" size="4" />
          </td>
          <td><?=$Number?>
          </td>
          <td><?=trim($CatNum) ?: '&nbsp;'?>
          </td>
          <td><?=trim($GroupYear) ?: '&nbsp;'?>
          </td>
          <td><?=trim($DisplayName) ?: '&nbsp;'?>
          </td>
          <td><?=trim($TorrentLink)?>
          </td>
          <td class="nobr"><?=User::format_username($UserID, $Username, false, false, false)?>
          </td>
          <td class="nobr">
            <input type="hidden" name="action" value="manage_handle" />
            <input type="hidden" name="auth"
              value="<?=$app->user->extra['AuthKey']?>" />
            <input type="hidden" name="collageid"
              value="<?=$CollageID?>" />
            <input type="hidden" name="groupid"
              value="<?=$GroupID?>" />
            <input type="submit" name="submit" value="Edit" />
            <input type="submit" name="submit" value="Remove" />
          </td>
        </form>
      </tr>
      <?php
} ?>
    </tbody>
  </table>
  <div class="drag_drop_save hidden">
    <input type="button" name="submit" value="Save All Changes" class="save_sortable_collage" />
  </div>
  <form class="dragdrop_form hidden" name="collage" action="collages.php" method="post" id="drag_drop_collage_form">
    <div>
      <input type="hidden" name="action" value="manage_handle" />
      <input type="hidden" name="auth"
        value="<?=$app->user->extra['AuthKey']?>" />
      <input type="hidden" name="collageid"
        value="<?=$CollageID?>" />
      <input type="hidden" name="groupid" value="1" />
      <input type="hidden" name="drag_drop_collage_sort_order" id="drag_drop_collage_sort_order" readonly="readonly"
        value="" />
    </div>
  </form>
</div>
<?php View::footer(); ?>