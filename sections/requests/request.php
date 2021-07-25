<?php
#declare(strict_types = 1);

/*
 * This is the page that displays the request to the end user after being created.
 */

if (empty($_GET['id']) || !is_number($_GET['id'])) {
    error(0);
}

$RequestID = $_GET['id'];

// First things first, let's get the data for the request

$Request = Requests::get_request($RequestID);
if ($Request === false) {
    error(404);
}

// Convenience variables
$IsFilled = !empty($Request['TorrentID']);
$CanVote = !$IsFilled && check_perms('site_vote');

if ($Request['CategoryID'] === '0') {
    $CategoryName = 'Unknown';
} else {
    $CategoryName = $Categories[$Request['CategoryID'] - 1];
}

$Title = empty($Request['Title']) ? (empty($Request['Title2']) ? $Request['TitleJP'] : $Request['Title2']) : $Request['Title'];

// Do we need to get artists?
$ArtistForm = Requests::get_artists($RequestID);
$ArtistName = Artists::display_artists($ArtistForm, false, true);
$ArtistLink = Artists::display_artists($ArtistForm, true, true);

if ($IsFilled) {
    $DisplayLink = "<a href='torrents.php?torrentid=$Request[TorrentID]' dir='ltr'>$Title</a>";
#$DisplayLink = "$ArtistLink<a href='torrents.php?torrentid=$Request[TorrentID]' dir='ltr'>$Title</a>";
} else {
    $DisplayLink = '<span dir="ltr">'.$Title."</span>";
    #$DisplayLink = $ArtistLink.'<span dir="ltr">'.$Title."</span>";
}

$Extra = "";

if (!empty($Request['Title2']) && $Request['Title2'] != $Title) {
    $Extra .= '<br />' . $Request['Title2'];
}

if (!empty($Request['TitleJP']) && $Request['TitleJP'] != $Title) {
    $Extra .= '<br />' . $Request['TitleJP'];
}

if (!empty($Request['CatalogueNumber'])) {
    $Extra .= "<br />[$Request[CatalogueNumber]]";
}

$DisplayLink .= $Extra;

// Votes time
$RequestVotes = Requests::get_votes_array($RequestID);
$VoteCount = count($RequestVotes['Voters']);
$ProjectCanEdit = (check_perms('project_team') && !$IsFilled && ($Request['CategoryID'] === '0' || ($CategoryName === 'Music' && $Request['Year'] === '0')));
$UserCanEdit = (!$IsFilled && $LoggedUser['ID'] === $Request['UserID'] && $VoteCount < 2);
$CanEdit = ($UserCanEdit || $ProjectCanEdit || check_perms('site_moderate_requests'));

// Comments (must be loaded before View::show_header so that subscriptions and quote notifications are handled properly)
list($NumComments, $Page, $Thread, $LastRead) = Comments::load('requests', $RequestID);

View::show_header(
    "View request: $Title",
    'comments,requests,bbcode,subscriptions,vendor/easymde.min',
    'vendor/easymde.min'
);
?>

<div>
  <div class="header">
    <h2><a href="requests.php">Requests</a> &rsaquo; <?=$CategoryName?> &rsaquo;
      <?=$DisplayLink?>
    </h2>
    <div class="linkbox">
      <?php if ($CanEdit) { ?>
      <a href="requests.php?action=edit&amp;id=<?=$RequestID?>"
        class="brackets">Edit</a>
      <?php }
  if ($UserCanEdit || check_perms('users_mod')) { //check_perms('site_moderate_requests')) {?>
      <a href="requests.php?action=delete&amp;id=<?=$RequestID?>"
        class="brackets">Delete</a>
      <?php }
  if (Bookmarks::has_bookmarked('request', $RequestID)) { ?>
      <a href="#" id="bookmarklink_request_<?=$RequestID?>"
        onclick="Unbookmark('request', <?=$RequestID?>, 'Bookmark'); return false;"
        class="brackets">Remove bookmark</a>
      <?php } else { ?>
      <a href="#" id="bookmarklink_request_<?=$RequestID?>"
        onclick="Bookmark('request', <?=$RequestID?>, 'Remove bookmark'); return false;"
        class="brackets">Bookmark</a>
      <?php } ?>
      <a href="#" id="subscribelink_requests<?=$RequestID?>"
        class="brackets"
        onclick="SubscribeComments('requests',<?=$RequestID?>);return false;"><?=Subscriptions::has_subscribed_comments('requests', $RequestID) !== false ? 'Unsubscribe' : 'Subscribe'?></a>
      <a href="reports.php?action=report&amp;type=request&amp;id=<?=$RequestID?>"
        class="brackets">Report request</a>
      <?php if (!$IsFilled) { ?>
      <a href="upload.php?requestid=<?=$RequestID?><?=($Request['GroupID'] ? "&amp;groupid=$Request[GroupID]" : '')?>"
        class="brackets">Upload request</a>
      <?php }
  if (!$IsFilled && ($Request['CategoryID'] === '0' || ($CategoryName === 'Music' && $Request['Year'] === '0'))) { ?>
      <a href="reports.php?action=report&amp;type=request_update&amp;id=<?=$RequestID?>"
        class="brackets">Request update</a>
      <?php } ?>

      <?php
// Create a search URL to WorldCat and Google based on title
$encoded_title = urlencode(preg_replace("/\([^\)]+\)/", '', $Request['Title']));
$encoded_artist = substr(str_replace('&amp;', 'and', $ArtistName), 0, -3);
$encoded_artist = str_ireplace('Performed By', '', $encoded_artist);
$encoded_artist = preg_replace("/\([^\)]+\)/", '', $encoded_artist);
$encoded_artist = urlencode($encoded_artist);

?>
    </div>
  </div>
  <div class="sidebar">
    <?php if ($Request['CategoryID'] !== '0') { ?>
    <div class="box box_image box_image_albumart box_albumart">
      <!-- .box_albumart deprecated -->
      <div class="head"><strong>Picture</strong></div>
      <div id="covers">
        <?php
    if (!empty($Request['Image'])) {
        ?>
        <img style="width: 100%;"
          src="<?=ImageTools::process($Request['Image'], 'thumb')?>"
          lightbox-img="<?=ImageTools::process($Request['Image'])?>"
          alt="<?=$Title?>" class="lightbox-init" />
        <?php
    } else { ?>
        <img style="width: 100%;"
          src="<?=STATIC_SERVER?>common/noartwork/music.png"
          alt="<?=$CategoryName?>" class="tooltip"
          title="<?=$CategoryName?>" height="220" border="0" />
        <?php } ?>
      </div>
    </div>
    <?php
  }

  $ArtistVariant = "Author(s)";
  /*
  switch ($CategoryName) {
    case "Movies":
      $ArtistVariant = "Idols";
      break;
    case "Anime":
      $ArtistVariant = "Studios";
      break;
    case "Manga":
      $ArtistVariant = "Artists";
      break;
    case "Games":
      $ArtistVariant = "Developers";
      break;
    case "Other":
      $ArtistVariant = "Creators";
      break;
    default:
      $ArtistVariant = "Artists";
  }
  */
?>
    <div class="box box_artists">
      <div class="head"><strong><?= $ArtistVariant ?></strong></div>
      <ul class="stats nobullet">
        <?php foreach ($ArtistForm as $Artist) { ?>
        <li class="artist">
          <?= Artists::display_artist($Artist) ?>
        </li>
        <?php } ?>
      </ul>
    </div>
    <div class="box box_tags">
      <div class="head"><strong>Tags</strong></div>
      <ul class="stats nobullet">
        <?php foreach ($Request['Tags'] as $TagID => $TagName) {
    $Split = Tags::get_name_and_class($TagName); ?>
        <li>
          <a class="<?= $Split['class']?>"
            href="torrents.php?taglist=<?=$TagName?>"><?=display_str($Split['name']) ?></a>
          <br style="clear: both;" />
        </li>
        <?php
} ?>
      </ul>
    </div>
    <div class="box box_votes">
      <div class="head"><strong>Top Contributors</strong></div>
      <table class="layout" id="request_top_contrib">
        <?php
  $VoteMax = ($VoteCount < 5 ? $VoteCount : 5);
  $ViewerVote = false;
  for ($i = 0; $i < $VoteMax; $i++) {
      $User = array_shift($RequestVotes['Voters']);
      $Boldify = false;
      if ($User['UserID'] === $LoggedUser['ID']) {
          $ViewerVote = true;
          $Boldify = true;
      } ?>
        <tr>
          <td>
            <a
              href="user.php?id=<?= $User['UserID'] ?>"><?= ($Boldify ? '<strong>' : '') . display_str($User['Username']) . ($Boldify ? '</strong>' : '') ?></a>
          </td>
          <td class="number_column">
            <?= ($Boldify ? '<strong>' : '') . Format::get_size($User['Bounty']) . ($Boldify ? "</strong>\n" : "\n") ?>
          </td>
        </tr>
        <?php
  }
  reset($RequestVotes['Voters']);
  if (!$ViewerVote) {
      foreach ($RequestVotes['Voters'] as $User) {
          if ($User['UserID'] === $LoggedUser['ID']) { ?>
        <tr>
          <td>
            <a
              href="user.php?id=<?= $User['UserID'] ?>"><strong><?= display_str($User['Username']) ?></strong></a>
          </td>
          <td class="number_column">
            <strong><?= Format::get_size($User['Bounty']) ?></strong>
          </td>
        </tr>
        <?php }
      }
  }
?>
      </table>
    </div>
  </div>
  <div class="main_column">
    <div class="box">
      <div class="head"><strong>Info</strong></div>
      <div class="pad">
        <table class="layout request_form">
          <tr>
            <td class="label">Created</td>
            <td>
              <?= time_diff($Request['TimeAdded']) ?> by
              <strong><?= Users::format_username($Request['UserID'], false, false, false) ?></strong>
            </td>
          </tr>
          <?php if ($CategoryName == 'Movies') {
    if (!empty($Request['CatalogueNumber'])) { ?>
          <tr>
            <td class="label">Catalogue number</td>
            <td><?= $Request['CatalogueNumber'] ?>
            </td>
          </tr>
          <?php
    }
}

/*
  $Worldcat = '';
  $OCLC = str_replace(' ', '', $Request['OCLC']);
  if ($OCLC !== '') {
    $OCLCs = explode(',', $OCLC);
    for ($i = 0; $i < count($OCLCs); $i++) {
      if (!empty($Worldcat)) {
        $Worldcat .= ', <a href="https://www.worldcat.org/oclc/'.$OCLCs[$i].'">'.$OCLCs[$i].'</a>';
      } else {
        $Worldcat = '<a href="https://www.worldcat.org/oclc/'.$OCLCs[$i].'">'.$OCLCs[$i].'</a>';
      }
    }
  }
  if (!empty($Worldcat)) {
?>
          <tr>
            <td class="label">WorldCat (OCLC) ID</td>
            <td><?=$Worldcat?>
            </td>
          </tr>
          <?
  }
*/
  if ($Request['GroupID']) {
      ?>
          <tr>
            <td class="label">Torrent Group</td>
            <td><a
                href="torrents.php?id=<?= $Request['GroupID'] ?>">torrents.php?id=<?= $Request['GroupID'] ?></a></td>
          </tr>
          <?php
  } ?>
          <tr>
            <td class="label">Votes</td>
            <td>
              <span id="votecount"><?= number_format($VoteCount) ?></span>
              <?php if ($CanVote) { ?>
              &nbsp;&nbsp;<a href="javascript:Vote(0)" class="brackets"><strong>+</strong></a>
              <strong>Costs <?= Format::get_size($MinimumVote, 0) ?></strong>
              <?php } ?>
            </td>
          </tr>
          <?php if ($Request['LastVote'] > $Request['TimeAdded']) { ?>
          <tr>
            <td class="label">Last Voted</td>
            <td><?= time_diff($Request['LastVote']) ?>
            </td>
          </tr>
          <?php
  }
  if ($CanVote) {
      ?>
          <tr id="voting">
            <td class="label">Custom Vote</td>
            <td>
              These units are in base 2, not base 10, e.g., there are 1,024 MiB in 1 GiB.
              <strong>The system deducts <?= ($RequestTax * 100) ?>% as tax.</strong>
              <br />

              <input type="text" id="amount_box" size="8" onchange="Calculate();" />
              <select id="unit" name="unit" onchange="Calculate();">
                <option value="mb">MiB</option>
                <option value="gb">GiB</option>
              </select>
              <input type="button" value="Preview" onclick="Calculate();" />
            </td>
          </tr>
          <tr>
            <td class="label">New Stats</td>
            <td>
              <form class="add_form" name="request" action="requests.php" method="get" id="request_form">
                <input type="hidden" name="action" value="vote" />
                <input type="hidden" name="auth"
                  value="<?=$LoggedUser['AuthKey']?>" />
                <input type="hidden" id="request_tax"
                  value="<?=$RequestTax?>" />
                <input type="hidden" id="requestid" name="id"
                  value="<?=$RequestID?>" />
                <input type="hidden" id="auth" name="auth"
                  value="<?=$LoggedUser['AuthKey']?>" />
                <input type="hidden" id="amount" name="amount" value="0" />
                <input type="hidden" id="current_uploaded"
                  value="<?=$LoggedUser['BytesUploaded']?>" />
                <input type="hidden" id="current_downloaded"
                  value="<?=$LoggedUser['BytesDownloaded']?>" />
                <input type="hidden" id="current_rr"
                  value="<?=(float)$LoggedUser['RequiredRatio']?>" />
                <input id="total_bounty" type="hidden"
                  value="<?=$RequestVotes['TotalBounty']?>" />

                <ul>
                  <!-- todo: Return this feature
              <li><strong>Bounty:</strong> <span id="bounty_after_tax">0.00 MiB</span></li> -->
                  <li><strong>Uploaded:</strong> <span id="new_uploaded"><?= Format::get_size($LoggedUser['BytesUploaded']) ?></span>
                  </li>
                  <li><strong>Ratio:</strong> <span id="new_ratio"><?= Format::get_ratio_html($LoggedUser['BytesUploaded'], $LoggedUser['BytesDownloaded']) ?></span>
                  </li>
                </ul>
                <input type="button" id="button" value="Vote!" class="button-primary" disabled="disabled" onclick="Vote();" />
              </form>
            </td>
          </tr>
          <?php
  } ?>
          <tr id="bounty">
            <td class="label">Bounty</td>
            <td id="formatted_bounty"><?=Format::get_size($RequestVotes['TotalBounty'])?>
            </td>
          </tr>
          <?php
  if ($IsFilled) {
      $TimeCompare = 1267643718; // Requests v2 was implemented 2010-03-03 20:15:18?>
          <tr>
            <td class="label">Filled</td>
            <td>
              <strong><a
                  href="torrents.php?<?=(strtotime($Request['TimeFilled']) < $TimeCompare ? 'id=' : 'torrentid=') . $Request['TorrentID']?>">Yes</a></strong>,
              by user <?=($Request['AnonymousFill'] ? '<em>Anonymous</em>' : Users::format_username($Request['FillerID'], false, false, false))?>
              <?php if ($LoggedUser['ID'] == $Request['UserID'] || $LoggedUser['ID'] == $Request['FillerID'] || check_perms('site_moderate_requests')) { ?>
              <strong><a
                  href="requests.php?action=unfill&amp;id=<?=$RequestID?>"
                  class="brackets">Unfill</a></strong> Unfilling a request without a valid, nontrivial reason will
              result in a warning.
              <?php } ?>
            </td>
          </tr>
          <?php
  } else { ?>
          <tr>
            <td class="label" valign="top">Fill Request</td>
            <td>
              <form class="edit_form" name="request" action="" method="post">
                <div>
                  <input type="hidden" name="action" value="takefill" />
                  <input type="hidden" name="auth"
                    value="<?=$LoggedUser['AuthKey']?>" />
                  <input type="hidden" name="requestid"
                    value="<?=$RequestID?>" />
                  <input type="text" size="50" name="link" <?=(!empty($Link) ? " value='$Link'" : '')?>
                  />
                  <br />
                  <strong>Should be the permalink [PL] of the torrent</strong>
                </div>
                <?php if (check_perms('site_moderate_requests')) { ?>
                <div>
                  <strong>For User</strong> <input type="text" size="25" name="user" <?= (!empty($FillerUsername) ? " value='$FillerUsername'" : '') ?>
                  />
                </div>
                <?php } ?>
                <div class="submit_div">
                  <input type="submit" class="button-primary" value="Fill" />
                </div>
              </form>
            </td>
          </tr>
          <?php } ?>
        </table>
      </div>
    </div>
    <div class="box box_request_desc">
      <div class="head"><strong>Description</strong></div>
      <div class="pad">
        <?=       Text::full_format($Request['Description']);?>
      </div>
    </div>
    <div id="request_comments">
      <div class="linkbox">
        <a name="comments"></a>
        <?php
$Pages = Format::get_pages($Page, $NumComments, TORRENT_COMMENTS_PER_PAGE, 9, '#comments');
echo $Pages;
?>
      </div>
      <?php

//---------- Begin printing
CommentsView::render_comments($Thread, $LastRead, "requests.php?action=view&amp;id=$RequestID");

if ($Pages) { ?>
      <div class="linkbox pager"><?=$Pages?>
      </div>
      <?php
}

View::parse('generic/reply/quickreply.php', array(
  'InputName' => 'pageid',
  'InputID' => $RequestID,
  'Action' => 'comments.php?page=requests',
  'InputAction' => 'take_post',
  'SubscribeBox' => true
));
?>
    </div>
  </div>
</div>
<?php View::show_footer();
