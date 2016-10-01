<?

define('FOOTER_FILE', SERVER_ROOT.'/design/privatefooter.php');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title><?=display_str($PageTitle)?></title>
  <meta charset="utf-8" />
  <link rel="shortcut icon" href="favicon.ico?v=<?=md5_file('favicon.ico');?>" />
  <link rel="search" type="application/opensearchdescription+xml" title="<?=SITE_NAME?>" href="/static/opensearch.xml">
  <link rel="alternate" type="application/rss+xml"
      href="feeds.php?feed=feed_news&amp;user=<?=G::$LoggedUser['ID']?>&amp;auth=<?=G::$LoggedUser['RSS_Auth']?>&amp;passkey=<?=G::$LoggedUser['torrent_pass']?>&amp;authkey=<?=G::$LoggedUser['AuthKey']?>"
      title="<?=SITE_NAME?> - News" />
  <link rel="alternate" type="application/rss+xml"
      href="feeds.php?feed=feed_blog&amp;user=<?=G::$LoggedUser['ID']?>&amp;auth=<?=G::$LoggedUser['RSS_Auth']?>&amp;passkey=<?=G::$LoggedUser['torrent_pass']?>&amp;authkey=<?=G::$LoggedUser['AuthKey']?>"
      title="<?=SITE_NAME?> - Blog" />
  <link rel="alternate" type="application/rss+xml"
      href="feeds.php?feed=feed_changelog&amp;user=<?=G::$LoggedUser['ID']?>&amp;auth=<?=G::$LoggedUser['RSS_Auth']?>&amp;passkey=<?=G::$LoggedUser['torrent_pass']?>&amp;authkey=<?=G::$LoggedUser['AuthKey']?>"
      title="<?=SITE_NAME?> - Gazelle Change Log" />
  <link rel="alternate" type="application/rss+xml"
      href="feeds.php?feed=torrents_notify_<?=G::$LoggedUser['torrent_pass']?>&amp;user=<?=G::$LoggedUser['ID']?>&amp;auth=<?=G::$LoggedUser['RSS_Auth']?>&amp;passkey=<?=G::$LoggedUser['torrent_pass']?>&amp;authkey=<?=G::$LoggedUser['AuthKey']?>"
      title="<?=SITE_NAME?> - P.T.N." />
<?
if (isset(G::$LoggedUser['Notify'])) {
  foreach (G::$LoggedUser['Notify'] as $Filter) {
    list($FilterID, $FilterName) = $Filter;
?>
  <link rel="alternate" type="application/rss+xml"
      href="feeds.php?feed=torrents_notify_<?=$FilterID?>_<?=G::$LoggedUser['torrent_pass']?>&amp;user=<?=G::$LoggedUser['ID']?>&amp;auth=<?=G::$LoggedUser['RSS_Auth']?>&amp;passkey=<?=G::$LoggedUser['torrent_pass']?>&amp;authkey=<?=G::$LoggedUser['AuthKey']?>&amp;name=<?=urlencode($FilterName)?>"
      title="<?=SITE_NAME?> - <?=display_str($FilterName)?>" />
<?
  }
}
?>
  <link rel="alternate" type="application/rss+xml"
      href="feeds.php?feed=torrents_all&amp;user=<?=G::$LoggedUser['ID']?>&amp;auth=<?=G::$LoggedUser['RSS_Auth']?>&amp;passkey=<?=G::$LoggedUser['torrent_pass']?>&amp;authkey=<?=G::$LoggedUser['AuthKey']?>"
      title="<?=SITE_NAME?> - All Torrents" />
  <link rel="alternate" type="application/rss+xml"
      href="feeds.php?feed=torrents_movies&amp;user=<?=G::$LoggedUser['ID']?>&amp;auth=<?=G::$LoggedUser['RSS_Auth']?>&amp;passkey=<?=G::$LoggedUser['torrent_pass']?>&amp;authkey=<?=G::$LoggedUser['AuthKey']?>"
      title="<?=SITE_NAME?> - Movie Torrents" />
  <link rel="alternate" type="application/rss+xml"
      href="feeds.php?feed=torrents_anime&amp;user=<?=G::$LoggedUser['ID']?>&amp;auth=<?=G::$LoggedUser['RSS_Auth']?>&amp;passkey=<?=G::$LoggedUser['torrent_pass']?>&amp;authkey=<?=G::$LoggedUser['AuthKey']?>"
      title="<?=SITE_NAME?> - Anime Torrents" />
  <link rel="alternate" type="application/rss+xml"
      href="feeds.php?feed=torrents_manga&amp;user=<?=G::$LoggedUser['ID']?>&amp;auth=<?=G::$LoggedUser['RSS_Auth']?>&amp;passkey=<?=G::$LoggedUser['torrent_pass']?>&amp;authkey=<?=G::$LoggedUser['AuthKey']?>"
      title="<?=SITE_NAME?> - Manga Torrents" />
  <link rel="alternate" type="application/rss+xml"
      href="feeds.php?feed=torrents_games&amp;user=<?=G::$LoggedUser['ID']?>&amp;auth=<?=G::$LoggedUser['RSS_Auth']?>&amp;passkey=<?=G::$LoggedUser['torrent_pass']?>&amp;authkey=<?=G::$LoggedUser['AuthKey']?>"
      title="<?=SITE_NAME?> - Games Torrents" />
  <link rel="alternate" type="application/rss+xml"
      href="feeds.php?feed=torrents_other&amp;user=<?=G::$LoggedUser['ID']?>&amp;auth=<?=G::$LoggedUser['RSS_Auth']?>&amp;passkey=<?=G::$LoggedUser['torrent_pass']?>&amp;authkey=<?=G::$LoggedUser['AuthKey']?>"
      title="<?=SITE_NAME?> - Other Torrents" />
  <link rel="stylesheet" type="text/css"
      href="<?=STATIC_SERVER?>styles/global.css?v=<?=filemtime(SERVER_ROOT.'/static/styles/global.css')?>" />
  <link rel="stylesheet" href="<?=STATIC_SERVER?>styles/tooltipster/style.css?v=<?=filemtime(SERVER_ROOT.'/static/styles/tooltipster/style.css')?>" type="text/css" media="screen" />
<?
if (empty(G::$LoggedUser['StyleURL'])) {
?>
<link rel="stylesheet" type="text/css" title="<?=G::$LoggedUser['StyleName']?>" media="screen"
    href="<?=STATIC_SERVER?>styles/<?=G::$LoggedUser['StyleName']?>/style.css?v=<?=filemtime(SERVER_ROOT.'/static/styles/'.G::$LoggedUser['StyleName'].'/style.css')?>" />
<?
} else {
  $StyleURLInfo = parse_url(G::$LoggedUser['StyleURL']);
  if (substr(G::$LoggedUser['StyleURL'], -4) == '.css'
      && empty($StyleURLInfo['query']) && empty($StyleURLInfo['fragment'])
      && ($StyleURLInfo['host'] == SITE_DOMAIN)
      && file_exists(SERVER_ROOT.$StyleURLInfo['path'])) {
    $StyleURL = G::$LoggedUser['StyleURL'].'?v='.filemtime(SERVER_ROOT.$StyleURLInfo['path']);
  } else {
    $StyleURL = G::$LoggedUser['StyleURL'];
  }
?>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$StyleURL?>" title="External CSS" />
<?
}
$ExtraCSS = explode(',', $CSSIncludes);
foreach ($ExtraCSS as $CSS) {
  if (trim($CSS) == '') {
    continue;
  }
?>
  <link rel="stylesheet" type="text/css" media="screen" href="<?=STATIC_SERVER."styles/$CSS/style.css?v=".filemtime(SERVER_ROOT."/static/styles/$CSS/style.css")?>" />
<?
}
?>
  <script type="text/javascript">
    var authkey = "<?=G::$LoggedUser['AuthKey']?>";
    var userid = <?=G::$LoggedUser['ID']?>;
  </script>
<?

$Scripts = array_merge(array('jquery', 'script_start', 'ajax.class', 'global', 'jquery.autocomplete', 'autocomplete', 'tooltipster', 'tooltipster_settings'), explode(',', $JSIncludes));
foreach ($Scripts as $Script) {
  if (trim($Script) == '') {
    continue;
  }
  if (($ScriptStats = G::$Cache->get_value("script_stats_$Script")) === false || $ScriptStats['mtime'] != filemtime(STATIC_SERVER."functions/$Script.js")) {
    $ScriptStats['mtime'] = filemtime(STATIC_SERVER."functions/$Script.js");
    $ScriptStats['hash'] = base64_encode(hash_file(INTEGRITY_ALGO, STATIC_SERVER."functions/$Script.js", true));
    $ScriptStats['algo'] = INTEGRITY_ALGO;
    G::$Cache->cache_value("script_stats_$Script", $ScriptStats);
  }
?>
  <script src="<?=STATIC_SERVER."functions/$Script.js?v=$ScriptStats[mtime]"?>" type="text/javascript" integrity="<?="$ScriptStats[algo]-$ScriptStats[hash]"?>"></script>
<?
}

global $ClassLevels;
// Get notifications early to change menu items if needed
global $NotificationSpans;
$NotificationsManager = new NotificationsManager(G::$LoggedUser['ID']);
$Notifications = $NotificationsManager->get_notifications();
$UseNoty = $NotificationsManager->use_noty();
$NewSubscriptions = false;
$NotificationSpans = array();
foreach ($Notifications as $Type => $Notification) {
  if ($Type === NotificationsManager::SUBSCRIPTIONS) {
    $NewSubscriptions = true;
  }
  if ($UseNoty) {
    $NotificationSpans[] = "<span class=\"noty-notification\" style=\"display: none;\" data-noty-type=\"$Type\" data-noty-id=\"$Notification[id]\" data-noty-importance=\"$Notification[importance]\" data-noty-url=\"$Notification[url]\">$Notification[message]</span>";
  }
}
if ($UseNoty && !empty($NotificationSpans)) {
  NotificationsManagerView::load_js();
}
if ($NotificationsManager->is_skipped(NotificationsManager::SUBSCRIPTIONS)) {
  $NewSubscriptions = Subscriptions::has_new_subscriptions();
}
?>
</head>
<body id="<?=$Document == 'collages' ? 'collage' : $Document?>">
  <div id="wrapper">
    <h1 class="hidden"><?=SITE_NAME?></h1>
    <div id="header">
      <div id="logo">
        <a href="index.php"></a>
      </div>
<?
if (isset(G::$LoggedUser['SearchType']) && G::$LoggedUser['SearchType']) { // Advanced search
  $UseAdvancedSearch = true;
} else {
  $UseAdvancedSearch = false;
}
?>
      <div id="searchbars">
        <ul>
          <li id="searchbar_torrents">
            <span class="hidden">Torrents: </span>
            <form class="search_form" name="torrents" action="torrents.php" method="get">
<?  if ($UseAdvancedSearch) { ?>
              <input type="hidden" name="action" value="advanced" />
<?  } ?>
              <input id="torrentssearch" accesskey="t" spellcheck="false"
                  onfocus="if (this.value == 'Torrents') { this.value = ''; }"
                  onblur="if (this.value == '') { this.value = 'Torrents'; }"
                  value="Torrents" placeholder="Torrents" type="text" name="<?=$UseAdvancedSearch ? 'groupname' : 'searchstr' ?>" size="17" />
            </form>
          </li>
          <li id="searchbar_artists">
            <span class="hidden">Artist: </span>
            <form class="search_form" name="artists" action="artist.php" method="get">
              <input id="artistsearch"<?=Users::has_autocomplete_enabled('search');
                  ?> accesskey="a"
                  spellcheck="false" autocomplete="off"
                  onfocus="if (this.value == 'Artists') { this.value = ''; }"
                  onblur="if (this.value == '') { this.value = 'Artists'; }"
                  value="Artists" placeholder="Artists" type="text" name="artistname" size="17" />
            </form>
          </li>
          <li id="searchbar_requests">
            <span class="hidden">Requests: </span>
            <form class="search_form" name="requests" action="requests.php" method="get">
              <input id="requestssearch" spellcheck="false"
                  onfocus="if (this.value == 'Requests') { this.value = ''; }"
                  onblur="if (this.value == '') { this.value = 'Requests'; }"
                  value="Requests" placeholder="Requests" type="text" name="search" size="17" />
            </form>
          </li>
          <li id="searchbar_forums">
            <span class="hidden">Forums: </span>
            <form class="search_form" name="forums" action="forums.php" method="get">
              <input value="search" type="hidden" name="action" />
              <input id="forumssearch"
                  onfocus="if (this.value == 'Forums') { this.value = ''; }"
                  onblur="if (this.value == '') { this.value = 'Forums'; }"
                  value="Forums" placeholder="Forums" type="text" name="search" size="17" />
            </form>
          </li>
<!--
          <li id="searchbar_wiki">
            <span class="hidden">Wiki: </span>
            <form class="search_form" name="wiki" action="wiki.php" method="get">
              <input type="hidden" name="action" value="search" />
              <input
                  onfocus="if (this.value == 'Wiki') { this.value = ''; }"
                  onblur="if (this.value == '') { this.value = 'Wiki'; }"
                  value="Wiki" placeholder="Wiki" type="text" name="search" size="17" />
            </form>
          </li>
-->
          <li id="searchbar_log">
            <span class="hidden">Log: </span>
            <form class="search_form" name="log" action="log.php" method="get">
              <input id="logsearch"
                  onfocus="if (this.value == 'Log') { this.value = ''; }"
                  onblur="if (this.value == '') { this.value = 'Log'; }"
                  value="Log" placeholder="Log" type="text" name="search" size="17" />
            </form>
          </li>
          <li id="searchbar_users">
            <span class="hidden">Users: </span>
            <form class="search_form" name="users" action="user.php" method="get">
              <input type="hidden" name="action" value="search" />
              <input
                  id="userssearch"
                  onfocus="if (this.value == 'Users') { this.value = ''; }"
                  onblur="if (this.value == '') { this.value = 'Users'; }"
                  value="Users" placeholder="Users" type="text" name="search" size="20" />
            </form>
          </li>
        </ul>
      </div>
      <div id="menu">
        <h4 class="hidden">Site Menu</h4>
        <ul>
          <li id="nav_index"<?=
            Format::add_class($PageID, array('index'), 'active', true)?>>
            <a href="index.php">Home</a>
          </li>
          <li id="nav_torrents"<?=
            Format::add_class($PageID, array('torrents', false, false), 'active', true)?>>
            <a href="torrents.php">Torrents</a>
          </li>
          <li id="nav_collages"<?=
            Format::add_class($PageID, array('collages'), 'active', true)?>>
            <a href="collages.php">Collections</a>
          </li>
          <li id="nav_requests"<?=
            Format::add_class($PageID, array('requests'), 'active', true)?>>
            <a href="requests.php">Requests</a>
          </li>
          <li id="nav_forums"<?=
            Format::add_class($PageID, array('forums'), 'active', true)?>>
            <a href="forums.php">Forums</a>
          </li>
          <li id="nav_irc"<?=
            Format::add_class($PageID, array('chat'), 'active', true)?>>
            <a href="chat.php">IRC</a>
          </li>
          <li id="nav_top10"<?=
            Format::add_class($PageID, array('top10'), 'active', true)?>>
            <a href="top10.php">Top 10</a>
          </li>
          <li id="nav_rules"<?=
            Format::add_class($PageID, array('rules'), 'active', true)?>>
            <a href="rules.php">Rules</a>
          </li>
          <li id="nav_wiki"<?=
            Format::add_class($PageID, array('wiki'), 'active', true)?>>
            <a href="wiki.php">Wiki</a>
          </li>
          <li id="nav_staff"<?=
            Format::add_class($PageID, array('staff'), 'active', true)?>>
            <a href="staff.php">Staff</a>
          </li>
        </ul>
      </div>
      <div id="userinfo">
        <ul id="userinfo_username">
          <li id="nav_userinfo" <?=Format::add_class($PageID, array('user', false, false), 'active', true, 'id')?>>
            <a href="user.php?id=<?=G::$LoggedUser['ID']?>" class="username"><?=G::$LoggedUser['Username']?></a>
          </li>
          <li id="nav_userclass">
            <span class="hidden userclass"><?=$ClassLevels[G::$LoggedUser['Class']]['Name']?></span>
          </li>
          <li id="nav_useredit" class="brackets<?=Format::add_class($PageID, array('user','edit'), 'active', false)?>">
            <a href="user.php?action=edit&amp;userid=<?=G::$LoggedUser['ID']?>">Edit</a>
          </li>
          <li id="nav_logout" class="brackets">
            <a href="logout.php?auth=<?=G::$LoggedUser['AuthKey']?>">Logout</a>
          </li>
        </ul>
        <ul id="userinfo_major">
          <li id="nav_upload" class="brackets<?=Format::add_class($PageID, array('upload'), 'active', false)?>">
            <a href="upload.php">Upload</a>
          </li>
<?
if (check_perms('site_send_unlimited_invites')) {
  $Invites = ' (∞)';
} elseif (G::$LoggedUser['Invites'] > 0) {
  $Invites = ' ('.G::$LoggedUser['Invites'].')';
} else {
  $Invites = '';
}
?>
          <li id="nav_invite" class="brackets<?=Format::add_class($PageID, array('user','invite'), 'active', false)?>">
            <a href="user.php?action=invite">Invite<?=$Invites?></a>
          </li>
          <li id="nav_donate" class="brackets<?=Format::add_class($PageID, array('donate'), 'active', false)?>">
            <a href="donate.php">Donate</a>
          </li>
        </ul>
        <ul id="userinfo_stats">
          <li id="stats_seeding">
            <a href="torrents.php?type=seeding&amp;userid=<?=G::$LoggedUser['ID']?>">Up</a>:
            <span class="stat tooltip" title="<?=Format::get_size(G::$LoggedUser['BytesUploaded'], 5)?>"><?=Format::get_size(G::$LoggedUser['BytesUploaded'])?></span>
          </li>
          <li id="stats_leeching">
            <a href="torrents.php?type=leeching&amp;userid=<?=G::$LoggedUser['ID']?>">Down</a>:
            <span class="stat tooltip" title="<?=Format::get_size(G::$LoggedUser['BytesDownloaded'], 5)?>"><?=Format::get_size(G::$LoggedUser['BytesDownloaded'])?></span>
          </li>
          <li id="stats_ratio">
            Ratio: <span class="stat"><?=Format::get_ratio_html(G::$LoggedUser['BytesUploaded'], G::$LoggedUser['BytesDownloaded'])?></span>
          </li>
<?  if (!empty(G::$LoggedUser['RequiredRatio']) && G::$LoggedUser['RequiredRatio'] > 0) { ?>
          <li id="stats_required">
            <a href="rules.php?p=ratio">Required</a>:
            <span class="stat tooltip" title="<?=number_format(G::$LoggedUser['RequiredRatio'], 5)?>"><?=number_format(G::$LoggedUser['RequiredRatio'], 2)?></span>
          </li>
<?  }

  if (G::$LoggedUser['FLTokens'] > 0) { ?>
          <li id="fl_tokens">
            <a href="wiki.php?action=article&amp;id=7">Tokens</a>:
            <span class="stat">
              <a href="userhistory.php?action=token_history&amp;userid=<?=G::$LoggedUser['ID']?>"><?=G::$LoggedUser['FLTokens']?></a>
            </span>
          </li>
<?  }
?>
          <li id="bonus_points">
            <a href="wiki.php?action=article&amp;id=8">Nips</a>:
            <span class="stat">
              <a href="store.php"><?=number_format(G::$LoggedUser['BonusPoints'])?></a>
            </span>
          </li>
<?  if (G::$LoggedUser['HnR'] > 0) { ?>
          <li id="hnr">
            <a href="snatchlist.php">HnRs</a>:
            <span class="stat">
              <a><?=G::$LoggedUser['HnR']?></a>
            </span>
          </li>
<?  }
?>
        </ul>
        <ul id="userinfo_minor"<?=$NewSubscriptions ? ' class="highlite"' : ''?>>
          <li>
            <span id="header_links_menu" class="brackets">Links ▾</span>
            <ul>
              <li id="nav_inbox"<?=
                Format::add_class($PageID, array('inbox'), 'active', true)?>>
                <a href="<?=Inbox::get_inbox_link(); ?>">Inbox</a>
              </li>
              <li id="nav_staffinbox"<?=
                Format::add_class($PageID, array('staffpm'), 'active', true)?>>
                <a href="staffpm.php">Staff Inbox</a>
              </li>
              <li id="nav_uploaded"<?=
                Format::add_class($PageID, array('torrents', false, 'uploaded'), 'active', true, 'userid')?>>
                <a href="torrents.php?type=uploaded&amp;userid=<?=G::$LoggedUser['ID']?>">Uploads</a>
              </li>
              <li id="nav_bookmarks"<?=
                Format::add_class($PageID, array('bookmarks'), 'active', true)?>>
                <a href="bookmarks.php?type=torrents">Bookmarks</a>
              </li>
<?  if (check_perms('site_torrents_notify')) { ?>
              <li id="nav_notifications"<?=
                Format::add_class($PageID, array(array('torrents', 'notify'), array('user', 'notify')), 'active', true, 'userid')?>>
                <a href="user.php?action=notify">Notifications</a>
              </li>
<?  }
  $ClassNames = $NewSubscriptions ? 'new-subscriptions' : '';
  $ClassNames = trim($ClassNames.Format::add_class($PageID, array('userhistory', 'subscriptions'), 'active', false));
?>
              <li id="nav_subscriptions"<?=$ClassNames ? " class=\"$ClassNames\"" : ''?>>
                <a href="userhistory.php?action=subscriptions">Subscriptions</a>
              </li>
              <li id="nav_comments"<?=
                Format::add_class($PageID, array('comments'), 'active', true, 'userid')?>>
                <a href="comments.php">Comments</a></li>
              <li id="nav_friends"<?=
                Format::add_class($PageID, array('friends'), 'active', true)?>>
                <a href="friends.php">Friends</a></li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
<?
//Start handling alert bars
$Alerts = array();
$ModBar = array();

// Staff blog
if (check_perms('users_mod')) {
  global $SBlogReadTime, $LatestSBlogTime;
  if (!$SBlogReadTime && ($SBlogReadTime = G::$Cache->get_value('staff_blog_read_'.G::$LoggedUser['ID'])) === false) {
    G::$DB->query("
      SELECT Time
      FROM staff_blog_visits
      WHERE UserID = ".G::$LoggedUser['ID']);
    if (list($SBlogReadTime) = G::$DB->next_record()) {
      $SBlogReadTime = strtotime($SBlogReadTime);
    } else {
      $SBlogReadTime = 0;
    }
    G::$Cache->cache_value('staff_blog_read_'.G::$LoggedUser['ID'], $SBlogReadTime, 1209600);
  }
  if (!$LatestSBlogTime && ($LatestSBlogTime = G::$Cache->get_value('staff_blog_latest_time')) === false) {
    G::$DB->query("
      SELECT MAX(Time)
      FROM staff_blog");
    list($LatestSBlogTime) = G::$DB->next_record();
    if ($LatestSBlogTime) {
      $LatestSBlogTime = strtotime($LatestSBlogTime);
    } else {
      $LatestSBlogTime = 0;
    }
    G::$Cache->cache_value('staff_blog_latest_time', $LatestSBlogTime, 1209600);
  }
  if ($SBlogReadTime < $LatestSBlogTime) {
    $Alerts[] = '<a href="staffblog.php">New staff blog post!</a>';
  }
}

// Inbox
if ($NotificationsManager->is_traditional(NotificationsManager::INBOX)) {
  $NotificationsManager->load_inbox();
  $NewMessages = $NotificationsManager->get_notifications();
  if (isset($NewMessages[NotificationsManager::INBOX])) {
    $Alerts[] = NotificationsManagerView::format_traditional($NewMessages[NotificationsManager::INBOX]);
  }
  $NotificationsManager->clear_notifications_array();
}

if (G::$LoggedUser['RatioWatch']) {
  $Alerts[] = '<a href="rules.php?p=ratio">Ratio Watch</a>: You have '.time_diff(G::$LoggedUser['RatioWatchEnds'], 3).' to get your ratio over your required ratio or your leeching abilities will be disabled.';
} elseif (G::$LoggedUser['CanLeech'] != 1) {
  $Alerts[] = '<a href="rules.php?p=ratio">Ratio Watch</a>: Your downloading privileges are disabled until you meet your required ratio.';
}

// Torrents
if ($NotificationsManager->is_traditional(NotificationsManager::TORRENTS)) {
  $NotificationsManager->load_torrent_notifications();
  $NewTorrents = $NotificationsManager->get_notifications();
  if (isset($NewTorrents[NotificationsManager::TORRENTS])) {
    $Alerts[] = NotificationsManagerView::format_traditional($NewTorrents[NotificationsManager::TORRENTS]);
  }
  $NotificationsManager->clear_notifications_array();
}

// Contests
if ($ContestSettings = G::$Cache->get_value('contest_settings')) {
  if (time() > $ContestSettings['start'] && time() < $ContestSettings['end']) {
    $Alerts[] = '<a href="/contest.php">A Contest is Underway!</a>';
  }
}

if (check_perms('users_mod')) {
  $ModBar[] = '<a href="tools.php">Toolbox</a>';
}
if (check_perms('users_mod')) {
  $NumStaffPMs = G::$Cache->get_value('num_staff_pms_'.G::$LoggedUser['ID']);
  if ($NumStaffPMs === false) {
    if (check_perms('users_mod')) {

      $LevelCap = 1000;

      G::$DB->query("
        SELECT COUNT(ID)
        FROM staff_pm_conversations
        WHERE Status = 'Unanswered'
          AND (AssignedToUser = ".G::$LoggedUser['ID']."
            OR (LEAST('$LevelCap', Level) <= '".G::$LoggedUser['EffectiveClass']."'
              AND Level <= ".G::$LoggedUser['Class']."))");
    }
    if (G::$LoggedUser['PermissionID'] == FORUM_MOD) {
      G::$DB->query("
        SELECT COUNT(ID)
        FROM staff_pm_conversations
        WHERE Status='Unanswered'
          AND (AssignedToUser = ".G::$LoggedUser['ID']."
            OR Level = '". $Classes[FORUM_MOD]['Level'] . "')");
    }
    list($NumStaffPMs) = G::$DB->next_record();
    G::$Cache->cache_value('num_staff_pms_'.G::$LoggedUser['ID'], $NumStaffPMs , 1000);
  }

  if ($NumStaffPMs > 0) {
    $ModBar[] = '<a href="staffpm.php">'.$NumStaffPMs.' Staff PMs</a>';
  }
}
if (check_perms('admin_reports')) {
// Torrent reports code
  $NumTorrentReports = G::$Cache->get_value('num_torrent_reportsv2');
  if ($NumTorrentReports === false) {
    G::$DB->query("
      SELECT COUNT(ID)
      FROM reportsv2
      WHERE Status = 'New'");
    list($NumTorrentReports) = G::$DB->next_record();
    G::$Cache->cache_value('num_torrent_reportsv2', $NumTorrentReports, 0);
  }

  $ModBar[] = '<a href="reportsv2.php">'.$NumTorrentReports.(($NumTorrentReports == 1) ? ' Report' : ' Reports').'</a>';

// Other reports code
  $NumOtherReports = G::$Cache->get_value('num_other_reports');
  if ($NumOtherReports === false) {
    G::$DB->query("
      SELECT COUNT(ID)
      FROM reports
      WHERE Status = 'New'");
    list($NumOtherReports) = G::$DB->next_record();
    G::$Cache->cache_value('num_other_reports', $NumOtherReports, 0);
  }

  if ($NumOtherReports > 0) {
    $ModBar[] = '<a href="reports.php">'.$NumOtherReports.(($NumTorrentReports == 1) ? ' Other report' : ' Other reports').'</a>';
  }
} elseif (check_perms('project_team')) {
  $NumUpdateReports = G::$Cache->get_value('num_update_reports');
  if ($NumUpdateReports === false) {
    G::$DB->query("
      SELECT COUNT(ID)
      FROM reports
      WHERE Status = 'New'
        AND Type = 'request_update'");
    list($NumUpdateReports) = G::$DB->next_record();
    G::$Cache->cache_value('num_update_reports', $NumUpdateReports, 0);
  }

  if ($NumUpdateReports > 0) {
    $ModBar[] = '<a href="reports.php">Request update reports</a>';
  }
} elseif (check_perms('site_moderate_forums')) {
  $NumForumReports = G::$Cache->get_value('num_forum_reports');
  if ($NumForumReports === false) {
    G::$DB->query("
      SELECT COUNT(ID)
      FROM reports
      WHERE Status = 'New'
        AND Type IN('artist_comment', 'collages_comment', 'post', 'requests_comment', 'thread', 'torrents_comment')");
    list($NumForumReports) = G::$DB->next_record();
    G::$Cache->cache_value('num_forum_reports', $NumForumReports, 0);
  }

  if ($NumForumReports > 0) {
    $ModBar[] = '<a href="reports.php">'.$NumForumReports.(($NumForumReports == 1) ? ' Forum report' : ' Forum reports').'</a>';
  }
}

if (check_perms('users_mod')) {
  $NumEmailDeleteRequests = G::$Cache->get_value('num_email_delete_requests');
  if ($NumEmailDeleteRequests === false) {
    G::$DB->query("SELECT COUNT(*) FROM email_delete_requests");
    list($NumEmailDeleteRequests) = G::$DB->next_record();
    G::$Cache->cache_value('num_email_delete_requests', $NumEmailDeleteRequests);
  }
  if ($NumEmailDeleteRequests > 0) {
    $ModBar[] = '<a href="tools.php?action=delete_email">' . $NumEmailDeleteRequests . " Email deletion request(s)</a>";
  }
}

if (check_perms('users_mod') && FEATURE_EMAIL_REENABLE) {
  $NumEnableRequests = G::$Cache->get_value(AutoEnable::CACHE_KEY_NAME);
  if ($NumEnableRequests === false) {
    G::$DB->query("SELECT COUNT(1) FROM users_enable_requests WHERE Outcome IS NULL");
    list($NumEnableRequests) = G::$DB->next_record();
    G::$Cache->cache_value(AutoEnable::CACHE_KEY_NAME, $NumEnableRequests);
  }

  if ($NumEnableRequests > 0) {
    $ModBar[] = '<a href="tools.php?action=enable_requests">' . $NumEnableRequests . " Enable requests</a>";
  }
}

if (!empty($Alerts) || !empty($ModBar)) { ?>
      <div id="alerts">
<?  foreach ($Alerts as $Alert) { ?>
        <div class="alertbar"><?=$Alert?></div>
<?
  }
  if (!empty($ModBar)) { ?>
        <div class="alertbar blend">
          <?=implode(' | ', $ModBar); echo "\n"?>
        </div>
<?  }
if (check_perms('site_debug') && !apc_exists('DBKEY')) { ?>
        <div class="alertbar" style="color: white; background: #B53939;">
          Warning: <a href="tools.php?action=database_key">no DB key</a>
        </div>
<?  } ?>
      </div>
<?
}
//Done handling alertbars
?>

    <div id="content">
