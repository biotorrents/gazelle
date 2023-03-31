<?php
#declare(strict_types=1);

$app = \Gazelle\App::go();

// todo: Cache this
$app->dbOld->query("
  SELECT
    ca.ArtistID,
    ag.Name,
    aw.Image,
    ca.UserID
  FROM collages_artists AS ca
    JOIN artists_group AS ag ON ag.ArtistID = ca.ArtistID
    LEFT JOIN wiki_artists AS aw ON aw.RevisionID = ag.RevisionID
  WHERE ca.CollageID = '$CollageID'
  ORDER BY ca.Sort");

$Artists = $app->dbOld->to_array('ArtistID', MYSQLI_ASSOC);

// Loop through the result set, building up $Collage and $TorrentTable
// Then we print them.
$Collage = [];
$ArtistTable = '';

$NumGroups = count($Artists);
$NumGroupsByUser = 0;
$UserAdditions = [];

foreach ($Artists as $Artist) {
    $UserID = $Artist['UserID'];
    if ($UserID === $app->userNew->core['id']) {
        $NumGroupsByUser++;
    }

    if (!isset($UserAdditions[$UserID])) {
        $UserAdditions[$UserID] = 0;
    }
    $UserAdditions[$UserID]++;

    ob_start(); ?>
<tr>
  <td><a
      href="artist.php?id=<?=$Artist['ArtistID']?>"><?=$Artist['Name']?></a></td>
</tr>
<?php
  $ArtistTable .= ob_get_clean();

    ob_start(); ?>
<li class="image_group_<?=$Artist['ArtistID']?>">
  <a
    href="artist.php?id=<?=$Artist['ArtistID']?>">
    <?php if ($Artist['Image']) { ?>
    <img class="tooltip"
      src="<?=ImageTools::process($Artist['Image'], 'thumb')?>"
      alt="<?=$Artist['Name']?>"
      title="<?=$Artist['Name']?>" width="118" />
    <?php } else { ?>
    <span style="width: 107px; padding: 5px;"><?=$Artist['Name']?></span>
    <?php } ?>
  </a>
</li>
<?php
  $Collage[] = ob_get_clean();
}

if (!check_perms('site_collages_delete') && ($Locked || ($MaxGroups > 0 && $NumGroups >= $MaxGroups) || ($MaxGroupsPerUser > 0 && $NumGroupsByUser >= $MaxGroupsPerUser))) {
    $PreventAdditions = true;
}

// Silly hack for people who are on the old setting
$CollageCovers = (isset($app->userNew->extra['CollageCovers']) ? $app->userNew->extra['CollageCovers'] : 25 * (abs($app->userNew->extra['HideCollage'] - 1)));
$CollagePages = [];

// Pad it out
if ($NumGroups > $CollageCovers) {
    for ($i = $NumGroups + 1; $i <= ceil($NumGroups / $CollageCovers) * $CollageCovers; $i++) {
        $Collage[] = '<li></li>';
    }
}

for ($i = 0; $i < $NumGroups / $CollageCovers; $i++) {
    $Groups = array_slice($Collage, $i * $CollageCovers, $CollageCovers);
    $CollagePage = '';
    foreach ($Groups as $Group) {
        $CollagePage .= $Group;
    }
    $CollagePages[] = $CollagePage;
}

View::header($Name, 'browse,collage,recommend');
?>

<div>
  <div class="header">
    <h2><?=$Name?>
    </h2>
    <div class="linkbox">
      <a href="collages.php" class="brackets">List of collections</a>
      <?php if (check_perms('site_collages_create')) { ?>
      <a href="collages.php?action=new" class="brackets">New collage</a>
      <?php } ?>
      <br /><br />
      <?php if (check_perms('site_collages_subscribe')) { ?>
      <a href="#" id="subscribelink<?=$CollageID?>" class="brackets"
        onclick="CollageSubscribe(<?=$CollageID?>); return false;"><?=(in_array($CollageID, $CollageSubscriptions) ? 'Unsubscribe' : 'Subscribe')?></a>
      <?php
      }
  if (check_perms('site_collages_delete') || (check_perms('site_edit_wiki') && !$Locked)) {
      ?>
      <a href="collages.php?action=edit&amp;collageid=<?=$CollageID?>"
        class="brackets">Edit description</a>
      <?php
  } else { ?>
      <span class="brackets">Locked</span>
      <?php
  }
  if (Bookmarks::isBookmarked('collage', $CollageID)) {
      ?>
      <a href="#" id="bookmarklink_collage_<?=$CollageID?>"
        class="brackets"
        onclick="Unbookmark('collage', <?=$CollageID?>, 'Bookmark'); return false;">Remove
        bookmark</a>
      <?php
  } else { ?>
      <a href="#" id="bookmarklink_collage_<?=$CollageID?>"
        class="brackets"
        onclick="Bookmark('collage', <?=$CollageID?>, 'Remove bookmark'); return false;">Bookmark</a>
      <?php
  }
  if (check_perms('site_collages_manage') && !$Locked) {
      ?>
      <a href="collages.php?action=manage_artists&amp;collageid=<?=$CollageID?>"
        class="brackets">Manage artists</a>
      <?php
  } ?>
      <a href="reports.php?action=report&amp;type=collage&amp;id=<?=$CollageID?>"
        class="brackets">Report collage</a>
      <?php if (check_perms('site_collages_delete') || $CreatorID === $app->userNew->core['id']) { ?>
      <a href="collages.php?action=delete&amp;collageid=<?=$CollageID?>&amp;auth=<?=$app->userNew->extra['AuthKey']?>"
        class="brackets" onclick="return confirm('Are you sure you want to delete this collage?');">Delete</a>
      <?php } ?>
    </div>
  </div>

  <div class="sidebar one-third column">
    <div class="box box_category">
      <div class="head"><strong>Category</strong></div>
      <div class="pad"><a
          href="collages.php?action=search&amp;cats[<?=(int)$CollageCategoryID?>]=1"><?=$CollageCats[(int)$CollageCategoryID]?></a></div>
    </div>

    <div class="box box_description">
      <div class="head"><strong>Description</strong></div>
      <div class="pad"><?=Text::parse($Description)?>
      </div>
    </div>

    <div class="box box_info box_statistics_collage_torrents">
      <div class="head"><strong>Statistics</strong></div>
      <ul class="stats nobullet">
        <li>Artists: <?=Text::float($NumGroups)?>
        </li>
        <li>Subscribers: <?=Text::float((int)$Subscribers)?>
        </li>
        <li>Built by <?=Text::float(count($UserAdditions))?>
          user<?=(count($UserAdditions) > 1 ? 's' : '')?>
        </li>
        <li>Last updated: <?=time_diff($Updated)?>
        </li>
      </ul>
    </div>

    <div class="box box_contributors">
      <div class="head"><strong>Top Contributors</strong></div>
      <div class="pad">
        <ol style="padding-left: 5px;">
          <?php
arsort($UserAdditions);
$i = 0;
foreach ($UserAdditions as $UserID => $Additions) {
    $i++;
    if ($i > 5) {
        break;
    } ?>
          <li><?=User::format_username($UserID, false, false, false)?>
            (<?=Text::float($Additions)?>)</li>
          <?php
}
?>
        </ol>
      </div>
    </div>

    <?php if (check_perms('site_collages_manage') && !isset($PreventAdditions)) { ?>
    <div class="box box_addartist">
      <div class="head"><strong>Add Artists</strong><span class="u-pull-right"><a href="#"
            onclick="$('.add_artist_container').toggleClass('hidden'); this.innerHTML = (this.innerHTML === 'Batch add' ? 'Individual add' : 'Batch add'); return false;"
            class="brackets">Batch add</a></span></div>
      <div class="pad add_artist_container">
        <form class="add_form" name="artist" action="collages.php" method="post">
          <input type="hidden" name="action" value="add_artist" />
          <input type="hidden" name="auth"
            value="<?=$app->userNew->extra['AuthKey']?>" />
          <input type="hidden" name="collageid"
            value="<?=$CollageID?>" />
          <div>
            <input type="text" id="artist" size="20" name="url" />
          </div>
          <div class="submit_div">
            <input type="submit" value="Add" />
          </div>
          <span style="font-style: italic;">Enter the URL of an artist on the site.</span>
        </form>
      </div>

      <div class="pad hidden add_artist_container">
        <form class="add_form" name="artists" action="collages.php" method="post">
          <input type="hidden" name="action" value="add_artist_batch" />
          <input type="hidden" name="auth"
            value="<?=$app->userNew->extra['AuthKey']?>" />
          <input type="hidden" name="collageid"
            value="<?=$CollageID?>" />
          <div>
            <textarea name="urls" rows="5" cols="25" style="white-space: nowrap;"></textarea>
          </div>
          <div class="submit_div">
            <input type="submit" value="Add" />
          </div>
          <span style="font-style: italic;">Enter the URLs of artists on the site, one per line.</span>
        </form>
      </div>
    </div>
    <?php } ?>

    <h3>Comments</h3>
    <?php
if ($CommentList === null) {
    $app->dbOld->query("
    SELECT
      c.ID,
      c.Body,
      c.AuthorID,
      um.Username,
      c.AddedTime
    FROM comments AS c
      LEFT JOIN users_main AS um ON um.ID = c.AuthorID
    WHERE c.Page = 'collages'
      AND c.PageID = $CollageID
    ORDER BY c.ID DESC
    LIMIT 15");
    $CommentList = $app->dbOld->to_array(false, MYSQLI_NUM);
}

foreach ($CommentList as $Comment) {
    list($CommentID, $Body, $UserID, $Username, $CommentTime) = $Comment; ?>
    <div class="box comment">
      <div class="head">
        <?=User::format_username($UserID, false, false, false) ?>
        <?=time_diff($CommentTime) ?>
        <br />
        <a href="reports.php?action=report&amp;type=comment&amp;id=<?=$CommentID?>"
          class="brackets">Report</a>
      </div>
      <div class="pad"><?=Text::parse($Body)?>
      </div>
    </div>
    <?php
}
?>

    <div class="box pad">
      <a href="collages.php?action=comments&amp;collageid=<?=$CollageID?>"
        class="brackets">View all comments</a>
    </div>

    <?php
if (!$app->userNew->extra['DisablePosting']) {
    ?>
    <div class="box box_addcomment">
      <div class="head"><strong>Comment</strong></div>
      <form class="send_form" name="comment" id="quickpostform" onsubmit="quickpostform.submit_button.disabled = true;"
        action="comments.php" method="post">
        <input type="hidden" name="action" value="take_post" />
        <input type="hidden" name="page" value="collages" />
        <input type="hidden" name="auth"
          value="<?=$app->userNew->extra['AuthKey']?>" />
        <input type="hidden" name="pageid" value="<?=$CollageID?>" />
        <div class="pad">
          <div>
            <textarea name="body" cols="24" rows="5"></textarea>
          </div>
          <div class="submit_div">
            <input type="submit" id="submit_button" value="Post" />
          </div>
        </div>
      </form>
    </div>
    <?php
}
?>
  </div>

  <div class="main_column two-thirds column">
    <?php
if ($CollageCovers !== 0) {
    ?>
    <div id="coverart" class="box">
      <div class="head" id="coverhead"><strong>Pictures</strong></div>
      <ul class="collage_images" id="collage_page0">
        <?php
  $Page1 = array_slice($Collage, 0, $CollageCovers);
    foreach ($Page1 as $Group) {
        echo $Group;
    } ?>
      </ul>
    </div>
    <?php if ($NumGroups > $CollageCovers) { ?>
    <div class="linkbox pager" style="clear: left;" id="pageslinksdiv">

      <span id="firstpage" class="invisible">
        <a href="#" class="pageslink" onclick="collageShow.page(0, this); return false;">
          <strong>&raquo;&nbsp;First</strong>
        </a> |
      </span>

      <span id="prevpage" class="invisible">
        <a href="#" class="pageslink" onclick="collageShow.prevPage(); return false;">
          <strong>&lsaquo; Prev</strong>
        </a> |
      </span>

      <?php for ($i = 0; $i < $NumGroups / $CollageCovers; $i++) { ?>
      <span id="pagelink<?=$i?>"
        class="<?=($i > 4 ? 'hidden' : '')?><?=($i === 0 ? 'selected' : '')?>">
        <a href="#" class="pageslink"
          onclick="collageShow.page(<?=$i?>, this); return false;">
          <strong><?=$CollageCovers * $i + 1?>-<?=min($NumGroups, $CollageCovers * ($i + 1))?></strong>
        </a>
        <?=(($i !== ceil($NumGroups / $CollageCovers) - 1) ? ' | ' : '')?>
      </span>
      <?php } ?>

      <span id="nextbar"
        class="<?=($NumGroups / $CollageCovers > 5) ? 'hidden' : ''?>">
        | </span>

      <span id="nextpage">
        <a href="#" class="pageslink" onclick="collageShow.nextPage(); return false;">
          <strong>Next &rsaquo;</strong>
        </a>
      </span>

      <span id="lastpage"
        class="<?=(ceil($NumGroups / $CollageCovers) === 2 ? 'invisible' : '')?>">
        |
        <a href="#" class="pageslink"
          onclick="collageShow.page(<?=ceil($NumGroups / $CollageCovers) - 1?>, this); return false;">
          <strong>Last &raquo;</strong>
        </a>
      </span>
    </div>

    <script type="text/javascript">
      //<![CDATA[
      collageShow.init( <?=json_encode($CollagePages)?> );
      //]]>
    </script>
    <?php
    }
}
?>

    <div class="box">
      <table class="artist_table grouping cats" id="discog_table">
        <tr class="colhead_dark">
          <td><strong>Artists</strong></td>
        </tr>
        <?=$ArtistTable?>
      </table>
    </div>
  </div>
</div>

<?php
View::footer();
