<?php
#declare(strict_types=1);

$app = \Gazelle\App::go();

if (!empty($_GET['userid'])) {
    if ($app->user->cant(["admin" => "sensitiveUserData"])) {
        error(403);
    }

    $UserID = $_GET['userid'];
    $Sneaky = $UserID !== $app->user->core['id'];

    if (!is_numeric($UserID)) {
        error(404);
    }

    $app->dbOld->prepared_query("
      SELECT Username
      FROM users_main
      WHERE ID = '$UserID'");
    list($Username) = $app->dbOld->next_record();
} else {
    $UserID = $app->user->core['id'];
}

$Sneaky = $UserID !== $app->user->core['id'];
//$ArtistList = Bookmarks::all_bookmarks('artist', $UserID);

$app->dbOld->prepared_query("
  SELECT ag.ArtistID, ag.Name
  FROM bookmarks_artists AS ba
    INNER JOIN artists_group AS ag ON ba.ArtistID = ag.ArtistID
  WHERE ba.UserID = $UserID
  ORDER BY ag.Name");
$ArtistList = $app->dbOld->to_array();

$Title = $Sneaky ? "$Username's bookmarked artists" : 'Your bookmarked artists';
View::header($Title, 'browse');
?>

<div>
  <div class="header">
    <h2>
      <?=$Title?>
    </h2>

    <div class="linkbox">
      <a href="bookmarks.php?type=torrents" class="brackets">Torrents</a>
      <a href="bookmarks.php?type=artists" class="brackets">Artists</a>
      <a href="bookmarks.php?type=collages" class="brackets">Collections</a>
      <a href="bookmarks.php?type=requests" class="brackets">Requests</a>
    </div>
  </div>

  <div class="box pad" align="center">
    <?php if (count($ArtistList) === 0) { ?>
    <h2>
      You have not bookmarked any artists
    </h2>
  </div>
</div>

<!--content-->
<?php
  View::footer();
        error();
    } ?>

<table width="100%" class="artist_table">
  <tr class="colhead">
    <td>Artist</td>
  </tr>

  <?php
foreach ($ArtistList as $Artist) {
    list($ArtistID, $Name) = $Artist; ?>

  <tr class="row bookmark_<?=$ArtistID?>">
    <td>
      <a href="artist.php?id=<?=$ArtistID?>"><?=$Name?></a>
      <span class="u-pull-right">
        <?php
  if (check_perms('site_torrents_notify')) {
      if (($Notify = $app->cache->get('notify_artists_'.$app->user->core['id'])) === false) {
          $app->dbOld->prepared_query("
            SELECT ID, Artists
            FROM users_notify_filters
            WHERE UserID = '{$app->user->core['id']}'
              AND Label = 'Artist notifications'
            LIMIT 1");

          $Notify = $app->dbOld->next_record(MYSQLI_ASSOC);
          $app->cache->set('notify_artists_'.$app->user->core['id'], $Notify, 0);
      }

      if (stripos($Notify['Artists'], "|$Name|") === false) { ?>
        <a href="artist.php?action=notify&amp;artistid=<?=$ArtistID?>&amp;auth=<?=$app->user->extra['AuthKey']?>"
          class="brackets">Notify of new uploads</a>
        <?php
      } else { ?>
        <a href="artist.php?action=notifyremove&amp;artistid=<?=$ArtistID?>&amp;auth=<?=$app->user->extra['AuthKey']?>"
          class="brackets">Do not notify of new uploads</a>
        <?php
      }
  } ?>

        <a href="#" id="bookmarklink_artist_<?=$ArtistID?>"
          onclick="Unbookmark('artist', <?=$ArtistID?>, 'Bookmark'); return false;"
          class="brackets">Remove bookmark</a>
      </span>
    </td>
  </tr>
  <?php
} ?>
</table>
</div>
</div>

<?php
View::footer();
$app->cache->set('bookmarks_'.$UserID, serialize(array(array($Username, $TorrentList, $CollageDataList))), 3600);
