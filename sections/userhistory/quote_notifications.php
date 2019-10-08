<?php
if (!empty($LoggedUser['DisableForums'])) {
  error(403);
}

$UnreadSQL = 'AND q.UnRead';
if ($_GET['showall'] ?? false) {
  $UnreadSQL = '';
}

if ($_GET['catchup'] ?? false) {
  $DB->query("UPDATE users_notify_quoted SET UnRead = '0' WHERE UserID = '$LoggedUser[ID]'");
  $Cache->delete_value('notify_quoted_' . $LoggedUser['ID']);
  header('Location: userhistory.php?action=quote_notifications');
  die();
}

if (isset($LoggedUser['PostsPerPage'])) {
  $PerPage = $LoggedUser['PostsPerPage'];
} else {
  $PerPage = POSTS_PER_PAGE;
}
list($Page, $Limit) = Format::page_limit($PerPage);

// Get $Limit last quote notifications
// We deal with the information about torrents and requests later on...
$sql = "
  SELECT
    SQL_CALC_FOUND_ROWS
    q.Page,
    q.PageID,
    q.PostID,
    q.QuoterID,
    q.Date,
    q.UnRead,
    f.ID as ForumID,
    f.Name as ForumName,
    t.Title as ForumTitle,
    a.Name as ArtistName,
    c.Name as CollageName
  FROM users_notify_quoted AS q
    LEFT JOIN forums_topics AS t ON t.ID = q.PageID
    LEFT JOIN forums AS f ON f.ID = t.ForumID
    LEFT JOIN artists_group AS a ON a.ArtistID = q.PageID
    LEFT JOIN collages AS c ON c.ID = q.PageID
  WHERE q.UserID = $LoggedUser[ID]
    AND (q.Page != 'forums' OR " . Forums::user_forums_sql() . ")
    AND (q.Page != 'collages' OR c.Deleted = '0')
    $UnreadSQL
  ORDER BY q.Date DESC
  LIMIT $Limit";
$DB->query($sql);
$Results = $DB->to_array(false, MYSQLI_ASSOC, false);
$DB->query('SELECT FOUND_ROWS()');
list($NumResults) = $DB->next_record();

$TorrentGroups = $Requests = [];
foreach ($Results as $Result) {
  if ($Result['Page'] == 'torrents') {
    $TorrentGroups[] = $Result['PageID'];
  } elseif ($Result['Page'] == 'requests') {
    $Requests[] = $Result['PageID'];
  }
}

$TorrentGroups = Torrents::get_groups($TorrentGroups, true, true, false);
$Requests = Requests::get_requests($Requests);

//Start printing page
View::show_header('Quote Notifications');
?>
<div class="thin">
  <div class="header">
    <h2>
      Quote notifications
      <?=$NumResults && !empty($UnreadSQL) ? " ($NumResults new)" : '' ?>
    </h2>
    <div class="linkbox pager">
      <br />
<? if ($UnreadSQL) { ?>
      <a href="userhistory.php?action=quote_notifications&amp;showall=1" class="brackets">Show all quotes</a>&nbsp;&nbsp;&nbsp;
<? } else { ?>
      <a href="userhistory.php?action=quote_notifications" class="brackets">Show unread quotes</a>&nbsp;&nbsp;&nbsp;
<? } ?>
      <a href="userhistory.php?action=subscriptions" class="brackets">Show subscriptions</a>&nbsp;&nbsp;&nbsp;
      <a href="userhistory.php?action=quote_notifications&amp;catchup=1" class="brackets">Catch up</a>&nbsp;&nbsp;&nbsp;
      <br /><br />
<?
      $Pages = Format::get_pages($Page, $NumResults, TOPICS_PER_PAGE, 9);
      echo $Pages;
      ?>
    </div>
  </div>
<? if (!$NumResults) { ?>
  <div class="center">No<?=($UnreadSQL ? ' new' : '')?> quotes.</div>
<? } ?>
  <br />
<?
foreach ($Results as $Result) {
  if ($Result['Page'] == 'forums') {
    $Links = 'Forums: <a href="forums.php?action=viewforum&amp;forumid=' . $Result['ForumID'] . '">' . display_str($Result['ForumName']) . '</a> &gt; ';
    $Links .= '<a href="forums.php?action=viewthread&amp;threadid=' . $Result['PageID'] . '" class="tooltip" title="' . display_str($Result['ForumTitle']) . '">' . Format::cut_string($Result['ForumTitle'], 75) . '</a> &gt; ';
    $Links .= '<a href="forums.php?action=viewthread&amp;threadid=' . $Result['PageID'] . '&amp;postid=' . $Result['PostID'] . '#post' . $Result['PostID'] . '">' . 'Post #' . $Result['PostID'] . '</a>';
  } elseif ($Result['Page'] == 'artist') {
    $Links = 'Artist: <a href="artist.php?id=' . $Result['PageID'] . '">' . display_str($Result['ArtistName']) . '</a> &gt; ';
    $Links .= '<a href="artist.php?id=' . $Result['PageID'] . '&amp;postid=' . $Result['PostID'] . '#post' . $Result['PostID'] . '">Post #' . $Result['PostID'] . '</a>';
  } elseif ($Result['Page'] == 'collages') {
    $Links = 'Collage: <a href="collages.php?id=' . $Result['PageID'] . '">' . display_str($Result['CollageName']) . '</a> &gt; ';
    $Links .= '<a href="collages.php?action=comments&amp;collageid=' . $Result['PageID'] . '&amp;postid=' . $Result['PostID'] . '#post' . $Result['PostID'] . '">Post #' . $Result['PostID'] . '</a>';
  } elseif ($Result['Page'] == 'requests') {
    if (!isset($Requests[$Result['PageID']])) {
      continue;
    }
    $Request = $Requests[$Result['PageID']];
    $CategoryName = $Categories[$Request['CategoryID'] - 1];
    $Links = 'Request: ';
    $Links .= Artists::display_artists(Requests::get_artists($Result['PageID'])) . '<a href="requests.php?action=view&amp;id=' . $Result['PageID'] . '">' . $Request['Title'] . "</a> &gt; ";
    $Links .= '<a href="requests.php?action=view&amp;id=' . $Result['PageID'] . '&amp;postid=' . $Result['PostID'] . '#post' . $Result['PostID'] . '"> Post #' . $Result['PostID'] . '</a>';
  } elseif ($Result['Page'] == 'torrents') {
    if (!isset($TorrentGroups[$Result['PageID']])) {
      continue;
    }
    $GroupInfo = $TorrentGroups[$Result['PageID']];
    $Links = 'Torrent: ' . Artists::display_artists($GroupInfo['ExtendedArtists']) . '<a href="torrents.php?id=' . $GroupInfo['ID'] . '">' . ($GroupInfo['Name'] ? $GroupInfo['Name'] : ($GroupInfo['NameRJ'] ? $GroupInfo['NameRJ'] : $GroupInfo['NameJP'])) . '</a> &gt; ';
    $Links .= '<a href="torrents.php?id=' . $GroupInfo['ID'] . '&postid=' . $Result['PostID'] . '#post' . $Result['PostID'] . '"> Post #' . $Result['PostID'] . '</a>';
  } else {
    continue;
  }
?>
  <table class="forum_post box vertical_margin noavatar">
    <tr class="colhead_dark notify_<?=$Result['Page']?>">
      <td colspan="2">
        <span class="float_left">
          <?=$Links?>
          <?=($Result['UnRead'] ? ' <span class="new">(New!)</span>' : '')?>
        </span>
      <td colspan="1">
        <span class="float_right">
          Quoted by <?=Users::format_username($Result['QuoterID'], false, false, false, false) . ' ' . time_diff($Result['Date']) ?>
        </span>
      </td>
    </tr>
  </table>
<? } ?>
</div>
<? View::show_footer(); ?>
