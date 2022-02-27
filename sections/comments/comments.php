<?php
declare(strict_types=1);

/**
 * $_REQUEST['action'] is artist, collages, requests or torrents (default torrents)
 * $_REQUEST['type'] depends on the page:
 *     collages:
 *        created = comments left on one's collages
 *        contributed = comments left on collages one contributed to
 *     requests:
 *        created = comments left on one's requests
 *        voted = comments left on requests one voted on
 *     torrents:
 *        uploaded = comments left on one's uploads
 *     If missing or invalid, this defaults to the comments one made
 */

// User ID
if (isset($_GET['id']) && is_number($_GET['id'])) {
  $UserID = (int)$_GET['id'];
  $UserInfo = Users::user_info($UserID);
  $Username = $UserInfo['Username'];

  if ($user['ID'] === $UserID) {
    $Self = true;
  } else {
    $Self = false;
  }

  $Perms = Permissions::get_permissions($UserInfo['PermissionID']);
  $UserClass = $Perms['Class'];

  if (!check_paranoia('torrentcomments', $UserInfo['Paranoia'], $UserClass, $UserID)) {
    error(403);
  }
} else {
  $UserID = $user['ID'];
  $Username = $user['Username'];
  $Self = true;
}

// Posts per page limit stuff
if (isset($user['PostsPerPage'])) {
  $PerPage = $user['PostsPerPage'];
} else {
  $PerPage = POSTS_PER_PAGE;
}
list($Page, $Limit) = Format::page_limit($PerPage);

if (!isset($_REQUEST['action'])) {
  $Action = 'torrents';
} else {
  $Action = $_REQUEST['action'];
}

if (!isset($_REQUEST['type'])) {
  $Type = 'default';
} else {
  $Type = $_REQUEST['type'];
}


// Construct the SQL query

$Conditions = $Join = [];
switch ($Action) {
  # artist comments
  case 'artist':
    $Field1 = '`artists_group`.`ArtistID`';
    $Field2 = '`artists_group`.`Name`';
    $Table = '`artists_group`';
    $Title = 'Artist comments left by ' . ($Self ? 'you' : $Username);
    $Header = 'Artist comments left by ' . ($Self ? 'you' : Users::format_username($UserID, false, false, false));
    $Conditions[] = "`comments`.`AuthorID` = $UserID";
    break;

  
  # collage comments
  case 'collages':
    $Field1 = '`collages`.`ID`';
    $Field2 = '`collages`.`Name`';
    $Table = '`collages`';
    $Conditions[] = "`collages`.`Deleted` = '0'";

    if ($Type == 'created') {
      $Conditions[] = "`collages`.`UserID` = $UserID";
      $Conditions[] = "`comments`.`AuthorID` != $UserID";
      $Title = 'Comments left on collages ' . ($Self ? 'you' : $Username) . ' created';
      $Header = 'Comments left on collages ' . ($Self ? 'you' : Users::format_username($UserID, false, false, false)) . ' created';
    } elseif ($Type == 'contributed') {
      $Conditions[] = 'IF(`collages`.`CategoryID` = ' . array_search('Artists', $CollageCats) . ', `collages_artists`.`ArtistID`, `collages_torrents`.`GroupID`) IS NOT NULL';
      $Conditions[] = "`comments`.`AuthorID` != $UserID";
      $Join[] = "LEFT JOIN `collages_torrents` ON `collages_torrents`.`CollageID` = `collages`.`ID` AND `collages_torrents`.`UserID` = $UserID";
      $Join[] = "LEFT JOIN `collages_artists` ON `collages_artists`.`CollageID` = `collages`.`ID` AND `collages_artists`.`UserID` = $UserID";
      $Title = 'Comments left on collages ' . ($Self ? 'you\'ve' : $Username . ' has') . ' contributed to';
      $Header = 'Comments left on collages ' . ($Self ? 'you\'ve' : Users::format_username($UserID, false, false, false).' has') . ' contributed to';
    } else {
      $Type = 'default';
      $Conditions[] = "`comments`.`AuthorID` = $UserID";
      $Title = 'Collage comments left by ' . ($Self ? 'you' : $Username);
      $Header = 'Collage comments left by ' . ($Self ? 'you' : Users::format_username($UserID, false, false, false));
    }
    break;


  # request comments
  case 'requests':
    $Field1 = '`requests`.`ID`';
    $Field2 = '`requests`.`Title`';
    $Table = 'requests';

    if ($Type == 'created') {
      $Conditions[] = "`requests`.`UserID` = $UserID";
      $Conditions[] = "`comments`.`AuthorID` != $UserID";
      $Title = 'Comments left on requests ' . ($Self ? 'you' : $Username) . ' created';
      $Header = 'Comments left on requests ' . ($Self ? 'you' : Users::format_username($UserID, false, false, false)) . ' created';
    } elseif ($Type == 'voted') {
      $Conditions[] = "`requests_votes`.`UserID` = $UserID";
      $Conditions[] = "`comments`.`AuthorID` != $UserID";
      $Join[] = 'JOIN `requests_votes` ON `requests_votes`.`RequestID` = `requests`.`ID`';
      $Title = 'Comments left on requests ' . ($Self ? 'you\'ve' : $Username . ' has') . ' voted on';
      $Header = 'Comments left on requests ' . ($Self ? 'you\'ve' : Users::format_username($UserID, false, false, false) . ' has') . ' voted on';
    } else {
      $Type = 'default';
      $Conditions[] = "`comments`.`AuthorID` = $UserID";
      $Title = 'Request comments left by ' . ($Self ? 'you' : $Username);
      $Header = 'Request comments left by ' . ($Self ? 'you' : Users::format_username($UserID, false, false, false));
    }
    break;


  # torrent comments
  case 'torrents':
  default:
    $Action = 'torrents';
    $Field1 = '`torrents`.`GroupID`';
    $Field2 = "COALESCE(NULLIF(tg.`title`,''),NULLIF(tg.`subject`,''),tg.`object`) AS Name";
    $Table = '`torrents`';
    $Join[] = 'JOIN `torrents_group` AS tg ON `torrents`.`GroupID` = tg.`id`';

    if ($Type == 'uploaded') {
      $Conditions[] = "`torrents`.`UserID` = $UserID";
      $Conditions[] = '`comments`.`AddedTime` > `torrents`.`Time`';
      $Conditions[] = "`comments`.`AuthorID` != $UserID";
      $Title = 'Comments left on torrents ' . ($Self ? 'you\'ve' : $Username . ' has') . ' uploaded';
      $Header = 'Comments left on torrents ' . ($Self ? 'you\'ve' : Users::format_username($UserID, false, false, false) . ' has') . ' uploaded';
    } else {
      $Type = 'default';
      $Conditions[] = "`comments`.`AuthorID` = $UserID";
      $Title = 'Torrent comments left by ' . ($Self ? 'you' : $Username);
      $Header = 'Torrent comments left by ' . ($Self ? 'you' : Users::format_username($UserID, false, false, false));
    }
    break;
}

# end SQL query constructor


$Join[] = "JOIN `comments` ON `comments`.`Page` = '$Action' AND `comments`.`PageID` = $Field1";
$Join = implode("\n\t\t", $Join);
$Conditions = implode(" AND ", $Conditions);
$Conditions = ($Conditions ? 'WHERE ' . $Conditions : '');

$SQL = "
  SELECT
    SQL_CALC_FOUND_ROWS
    `comments`.`AuthorID`,
    `comments`.`Page`,
    `comments`.`PageID`,
    $Field2,
    `comments`.`ID`,
    `comments`.`Body`,
    `comments`.`AddedTime`,
    `comments`.`EditedTime`,
    `comments`.`EditedUserID`
  FROM $Table
    $Join
  $Conditions
  GROUP BY `comments`.`ID`
  ORDER BY `comments`.`ID` DESC
  LIMIT $Limit";

$Comments = $db->query($SQL);
$Count = $db->record_count();

$db->query("SELECT FOUND_ROWS()");
list($Results) = $db->next_record();
$Pages = Format::get_pages($Page, $Results, $PerPage, 11);
$db->set_query_id($Comments);

# Remove the weird comment headings on torrent and request comments
/*
if ($Action === 'requests') {
  $RequestIDs = array_flip(array_flip($db->collect('PageID')));
  $Artists = [];

  foreach ($RequestIDs as $RequestID) {
    $Artists[$RequestID] = Requests::get_artists($RequestID);
  }
  $db->set_query_id($Comments);
} elseif ($Action === 'torrents') {
  $GroupIDs = array_flip(array_flip($db->collect('PageID')));
  $Artists = Artists::get_artists($GroupIDs);
  $db->set_query_id($Comments);
}
*/

# Replace the "shifting" main links with regular static ones
# There are already shifting supplemental links for each type
$ActionLinks[] = '<a href="comments.php?action=torrents' . $LinkID . '" class="brackets">Torrent comments</a>';
$ActionLinks[] = '<a href="comments.php?action=collages' . $LinkID . '" class="brackets">Collections comments</a>';
$ActionLinks[] = '<a href="comments.php?action=requests' . $LinkID . '" class="brackets">Request comments</a>';
$ActionLinks[] = '<a href="comments.php?action=artist' . $LinkID . '" class="brackets">Artist comments</a>';

/*
$LinkID = (!$Self ? '&amp;id=' . $UserID : '');
$ActionLinks = $TypeLinks = [];
if ($Action !== 'artist') {
  $ActionLinks[] = '<a href="comments.php?action=artist' . $LinkID . '" class="brackets">Artist comments</a>';
}

if ($Action !== 'collages') {
  $ActionLinks[] = '<a href="comments.php?action=collages' . $LinkID . '" class="brackets">Collections comments</a>';
}

if ($Action !== 'requests') {
  $ActionLinks[] = '<a href="comments.php?action=requests' . $LinkID . '" class="brackets">Request comments</a>';
}

if ($Action !== 'torrents') {
  $ActionLinks[] = '<a href="comments.php?action=torrents' . $LinkID . '" class="brackets">Torrent comments</a>';
}
*/

switch ($Action) {
  case 'collages':
    $BaseLink = 'comments.php?action=collages' . $LinkID;
    if ($Type !== 'default') {
      $TypeLinks[] = '<a href="' . $BaseLink . '" class="brackets">Display collage comments ' . ($Self ? 'you\'ve' : $Username . ' has') . ' made</a>';
    }

    if ($Type !== 'created') {
      $TypeLinks[] = '<a href="' . $BaseLink . '&amp;type=created" class="brackets">Display comments left on ' . ($Self ? 'your collections' : 'collections created by ' .$Username) . '</a>';
    }

    if ($Type !== 'contributed') {
      $TypeLinks[] = '<a href="' . $BaseLink . '&amp;type=contributed" class="brackets">Display comments left on collections ' . ($Self ? 'you\'ve' : $Username . ' has') . ' contributed to</a>';
    }
    break;

  case 'requests':
    $BaseLink = 'comments.php?action=requests' . $LinkID;
    if ($Type !== 'default') {
      $TypeLinks[] = '<a href="' . $BaseLink . '" class="brackets">Display request comments you\'ve made</a>';
    }

    if ($Type !== 'created') {
      $TypeLinks[] = '<a href="' . $BaseLink . '&amp;type=created" class="brackets">Display comments left on your requests</a>';
    }

    if ($Type !== 'voted') {
      $TypeLinks[] = '<a href="' . $BaseLink . '&amp;type=voted" class="brackets">Display comments left on requests you\'ve voted on</a>';
    }
    break;

  case 'torrents':
    if ($Type !== 'default') {
      $TypeLinks[] = '<a href="comments.php?action=torrents' . $LinkID . '" class="brackets">Display comments you have made</a>';
    }

    if ($Type !== 'uploaded') {
      $TypeLinks[] = '<a href="comments.php?action=torrents' . $LinkID . '&amp;type=uploaded" class="brackets">Display comments left on your uploads</a>';
    }
    break;
}
$Links = implode(' ', $ActionLinks) . (count($TypeLinks) ? '<br />' . implode(' ', $TypeLinks) : '');

View::header($Title, 'comments');
?><div>
  <div class="header">
    <h2><?=$Header?></h2>
<?php if ($Links !== '') { ?>
    <div class="linkbox">
      <?=$Links?>
    </div>
<?php } ?>
  </div>
  <div class="linkbox">
    <?=$Pages?>
  </div>
<?php
if ($Count > 0) {
  $db->set_query_id($Comments);
  while (list($AuthorID, $Page, $PageID, $Name, $PostID, $Body, $AddedTime, $EditedTime, $EditedUserID) = $db->next_record()) {
    $Link = Comments::get_url($Page, $PageID, $PostID);
    switch ($Page) {
      case 'artist':
        $Header = " on <a href=\"artist.php?id=$PageID\">$Name</a>";
        break;

      case 'collages':
        $Header = " on <a href=\"collages.php?id=$PageID\">$Name</a>";
        break;

      case 'requests':
        $Header = ' on ' . Artists::display_artists($Artists[$PageID]) . " <a href=\"requests.php?action=view&id=$PageID\">$Name</a>";
        break;

      case 'torrents':
        $Header = ' on ' . Artists::display_artists($Artists[$PageID]) . " <a href=\"torrents.php?id=$PageID\">$Name</a>";
        break;
    }
    CommentsView::render_comment($AuthorID, $PostID, $Body, $AddedTime, $EditedUserID, $EditedTime, $Link, false, $Header, false);
  }
} else { ?>
  <h2 class="center">No results.</h2>
<?php } ?>
  <div class="linkbox">
    <?=$Pages?>
  </div>
</div>
<?php View::footer();
