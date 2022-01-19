<?php
#declare(strict_types=1);

$ENV = ENV::go();
$Twig = Twig::go();
$View = new View();

if ($ENV->DEV) {
    $Debug = Debug::go();
    $Render = $Debug->getJavascriptRenderer();
}
?>

<!doctype html>
<html>

<head>
  <?php if ($ENV->DEV) {
    echo $Render->renderHead();
} ?>

  <title>
    <?= esc($PageTitle) ?>
  </title>

  <?=
    $Twig->render(
        'header/meta-tags.html',
        [
        'ENV' => $ENV,
        'LoggedUser' => G::$LoggedUser,
        'title' => esc($PageTitle)
      ]
    );
  ?>

  <?php
# Load JS
$Scripts = array_filter(
    array_merge(
        [
          'vendor/jquery.min',
          #'vendor/jquery-ui.min',
          'vendor/highlight.min',
          'vendor/instantpage.min',
          'ajax.class',
          'menus',
          'global',
      ],
        explode(',', $JSIncludes)
    )
);

foreach ($Scripts as $Script) {
    echo $View->pushAsset(
        "$ENV->STATIC_SERVER/js/$Script.js",
        'script'
    );
}

# Load CSS
$Styles = array_filter(
    array_merge(
        [
          #'vendor/jquery-ui.min',
          'vendor/normalize.min',
          'vendor/skeleton.min',
          'vendor/highlight.min',
          'global',
        ],
        explode(',', $CSSIncludes)
    )
);

if ($ENV->DEV) {
    array_push($Styles, 'development');
}

foreach ($Styles as $Style) {
    echo $View->pushAsset(
        "$ENV->STATIC_SERVER/css/$Style.css",
        'style'
    );
}

# Fonts
/*
$Fonts = ['fa-brands-400', 'fa-regular-400', 'fa-solid-900'];
foreach ($Fonts as $Font) {
    echo $View->pushAsset(
        "$ENV->STATIC_SERVER/css/vendor/fa/webfonts/$Font.woff2",
        'font'
    );
}
*/

/**
 * User notification feeds
 * (generic feeds in HTML below)
 */
$ID = G::$LoggedUser['ID'];
$RssAuth = G::$LoggedUser['RSS_Auth'];
$PassKey = G::$LoggedUser['torrent_pass'];
$AuthKey = G::$LoggedUser['AuthKey'];

if (isset(G::$LoggedUser['Notify'])) {
    foreach (G::$LoggedUser['Notify'] as $Filter) {
        list($FilterID, $FilterName) = $Filter;
        $NameEsc = esc($FilterName);

        echo $HTML = <<<HTML
        <link rel="alternate" type="application/rss+xml"
          href="feeds.php?feed=torrents_notify_$FilterID_$PassKey&amp;user=$ID&amp;auth=$RssAuth&amp;passkey=$PassKey&amp;authkey=$AuthKey"
          title="$NameEsc $ENV->SEP $ENV->SITE_NAME" />
HTML;
    }
}

# New uploads in each categoty
foreach ($ENV->CATS as $Cat) {
    $name = urlencode(strtolower($Cat->Name));

    echo $HTML = <<<HTML
    <link rel="alternate" type="application/rss+xml"
      href="feeds.php?feed=torrents_$name&amp;user=$ID&amp;auth=$RssAuth&amp;passkey=$PassKey&amp;authkey=$AuthKey"
      title="New $Cat->Name Torrents $ENV->SEP $ENV->SITE_NAME" />
HTML;
}

# All torrents, news, and blog
echo $HTML = <<<HTML
<link rel="alternate" type="application/rss+xml"
  href="feeds.php?feed=torrents_all&amp;user=$ID&amp;auth=$RssAuth&amp;passkey=$PassKey&amp;authkey=$AuthKey"
  title="All New Torrents $ENV->SEP $ENV->SITE_NAME" />

<link rel="alternate" type="application/rss+xml"
  href="feeds.php?feed=feed_news&amp;user=$ID&amp;auth=$RssAuth&amp;passkey=$PassKey&amp;authkey=$AuthKey"
  title="News $ENV->SEP $ENV->SITE_NAME" />

<link rel="alternate" type="application/rss+xml"
  href="feeds.php?feed=feed_news&amp;user=$ID&amp;auth=$RssAuth&amp;passkey=$PassKey&amp;authkey=$AuthKey"
  title="Blog $ENV->SEP $ENV->SITE_NAME" />
HTML;

/**
 * User stylesheet
 */
if (empty(G::$LoggedUser['StyleURL'])) {
    if (($StyleColors = G::$Cache->get_value('stylesheet_colors')) === false) {
        G::$DB->query('SELECT LOWER(REPLACE(Name, " ", "_")) AS Name, Color FROM stylesheets WHERE COLOR IS NOT NULL');

        while (list($StyleName, $StyleColor) = G::$DB->next_record()) {
            $StyleColors[$StyleName] = $StyleColor;
        }
        G::$Cache->cache_value('stylesheet_colors', $StyleColors, 0);
    }

    if (isset($StyleColors[G::$LoggedUser['StyleName']])) { ?>
  <meta name="theme-color"
    content="<?=$StyleColors[G::$LoggedUser['StyleName']]?>">
  <?php }

    $userStyle = "$ENV->STATIC_SERVER/css/" . G::$LoggedUser['StyleName'] . ".css";
    echo $View->pushAsset(
        $userStyle,
        'style'
    );
} else {
    $StyleURLInfo = parse_url(G::$LoggedUser['StyleURL']);
    if (substr(G::$LoggedUser['StyleURL'], -4) === '.css'
        && empty($StyleURLInfo['query']) && empty($StyleURLInfo['fragment'])
        && ($StyleURLInfo['host'] === SITE_DOMAIN)
        && file_exists(SERVER_ROOT.$StyleURLInfo['path'])) {
        $StyleURL = G::$LoggedUser['StyleURL'].'?v='.filemtime(SERVER_ROOT.$StyleURLInfo['path']);
    } else {
        $StyleURL = G::$LoggedUser['StyleURL'];
    } ?>
  <link rel="stylesheet" type="text/css" media="screen"
    href="<?=$StyleURL?>" title="External CSS">
  <?php
}

$ExtraCSS = explode(',', $CSSIncludes);
foreach ($ExtraCSS as $CSS) {
    if (trim($CSS) === '') {
        continue;
    } ?>

  <?php
}

global $ClassLevels;
// Get notifications early to change menu items if needed
global $NotificationSpans;

$NotificationsManager = new NotificationsManager(G::$LoggedUser['ID']);
$Notifications = $NotificationsManager->get_notifications();
$UseNoty = $NotificationsManager->use_noty();
$NewSubscriptions = false;
$NotificationSpans = [];

foreach ($Notifications as $Type => $Notification) {
    if ($Type === NotificationsManager::SUBSCRIPTIONS) {
        $NewSubscriptions = true;
    }

    if ($UseNoty) {
        $NotificationSpans[] = "<span class='noty-notification' style='display: none;' data-noty-type='$Type' data-noty-id='$Notification[id]' data-noty-importance='$Notification[importance]' data-noty-url='$Notification[url]'>$Notification[message]</span>";
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

<?php
  if (!empty(G::$LoggedUser['StyleAdditions'])) {
      $BodyStyles = 'style_'.implode(' style_', G::$LoggedUser['StyleAdditions']);
  }
?>

<body
  id="<?=$Document === 'collages' ? 'collage' : $Document?>"
  class="<?=($BodyStyles??'')?>">
  <div id="wrapper">
    <h1 class="hidden">
      <?= $ENV->SITE_NAME ?>
    </h1>

    <?= $Twig->render(
    'header/main-menu.html',
    [
          'ENV' => $ENV,
          'LoggedUser' => G::$LoggedUser,
          'inbox' => Inbox::get_inbox_link(),
          'notify' => check_perms('site_torrents_notify'),
        ]
);
        ?>

    <?php
if (isset(G::$LoggedUser['SearchType']) && G::$LoggedUser['SearchType']) { // Advanced search
            $UseAdvancedSearch = true;
        } else {
            $UseAdvancedSearch = false;
        }
?>

    <div id="userinfo">
      <ul id="userinfo_major">

        <li id="nav_upload">
          <a href="upload.php">Upload</a>
        </li>

        <?php
if (check_perms('site_send_unlimited_invites')) {
    $Invites = ' (∞)';
} elseif (G::$LoggedUser['Invites'] > 0) {
    $Invites = ' ('.G::$LoggedUser['Invites'].')';
} else {
    $Invites = '';
}
?>

        <li id="nav_invite">
          <a href="user.php?action=invite">Invite<?=$Invites?></a>
        </li>

        <?php if ($ENV->FEATURE_DONATE) { ?>
        <li id="nav_donate">
          <a href="donate.php">Donate</a>
        </li>
        <?php } ?>

        <li id="nav_staff">
          <a href="staff.php">Staff</a>
        </li>
      </ul>

      <ul id="userinfo_stats">
        <li id="stats_seeding">
          <a
            href="torrents.php?type=seeding&amp;userid=<?=G::$LoggedUser['ID']?>">Up</a>:
          <span class="stat tooltip"
            title="<?=Format::get_size(G::$LoggedUser['BytesUploaded'], 5)?>"><?=Format::get_size(G::$LoggedUser['BytesUploaded'])?></span>
        </li>

        <li id="stats_leeching">
          <a
            href="torrents.php?type=leeching&amp;userid=<?=G::$LoggedUser['ID']?>">Down</a>:
          <span class="stat tooltip"
            title="<?=Format::get_size(G::$LoggedUser['BytesDownloaded'], 5)?>"><?=Format::get_size(G::$LoggedUser['BytesDownloaded'])?></span>
        </li>

        <li id="stats_ratio">
          Ratio: <span class="stat"><?=Format::get_ratio_html(G::$LoggedUser['BytesUploaded'], G::$LoggedUser['BytesDownloaded'])?></span>
        </li>
        <?php if (!empty(G::$LoggedUser['RequiredRatio']) && G::$LoggedUser['RequiredRatio'] > 0) { ?>
        <li id="stats_required">
          <a href="rules.php?p=ratio">Required</a>:
          <span class="stat tooltip"
            title="<?=number_format(G::$LoggedUser['RequiredRatio'], 5)?>"><?=number_format(G::$LoggedUser['RequiredRatio'], 2)?></span>
        </li>
        <?php } ?>
      </ul>


      <ul id="userinfo_extra">

        <?php if (G::$LoggedUser['FLTokens'] > 0) { ?>
        <li id="fl_tokens">
          <a href="wiki.php?action=article&amp;name=tokens">Tokens</a>:
          <span class="stat">
            <a
              href="userhistory.php?action=token_history&amp;userid=<?=G::$LoggedUser['ID']?>"><?=G::$LoggedUser['FLTokens']?></a>
          </span>
        </li>
        <?php } ?>

        <li id="bonus_points">
          <a href="wiki.php?action=article&amp;name=bonuspoints"><?=BONUS_POINTS?></a>:
          <span class="stat">
            <a href="store.php"><?=number_format(G::$LoggedUser['BonusPoints'])?></a>
          </span>
        </li>

        <?php if (G::$LoggedUser['HnR'] > 0) { ?>
        <li id="hnr">
          <a href="snatchlist.php">HnRs</a>:
          <span class="stat">
            <a><?=G::$LoggedUser['HnR']?></a>
          </span>
        </li>
        <?php } ?>
      </ul>
    </div>

    <?php if (!apcu_exists('DBKEY')) { ?>
    <a id="dbcrypt" class="tooltip" href="wiki.php?action=article&amp;name=databaseencryption"
      title="Database is not fully decrypted. Site functionality will be reduced until staff can provide the decryption key. Click to learn more."></a>
    <?php } ?>
  </div>

  <?php
// Start handling alert bars
$Alerts = [];
$ModBar = [];

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
} elseif ((int) G::$LoggedUser['CanLeech'] !== 1) {
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

/** Buggy af rn 2022-01-12
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

        if (G::$LoggedUser['PermissionID'] === FORUM_MOD) {
            G::$DB->query("
              SELECT COUNT(ID)
              FROM staff_pm_conversations
              WHERE Status='Unanswered'
                AND (AssignedToUser = ".G::$LoggedUser['ID']."
                  OR Level = '". $Classes[FORUM_MOD]['Level'] . "')");
        }

        list($NumStaffPMs) = G::$DB->next_record();
        G::$Cache->cache_value('num_staff_pms_'.G::$LoggedUser['ID'], $NumStaffPMs, 1000);
    }

    if ($NumStaffPMs > 0) {
        $ModBar[] = '<a href="staffpm.php">'.$NumStaffPMs.' Staff PMs</a>';
    }
}
*/

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

    $ModBar[] = '<a href="reportsv2.php">'.$NumTorrentReports.(($NumTorrentReports === 1) ? ' Report' : ' Reports').'</a>';

    // Other reports code
    $NumOtherReports = G::$Cache->get_value('num_other_reports');
    if ($NumOtherReports === false) {
        G::$DB->query("
          SELECT COUNT(ID)
          FROM reportsv2
          WHERE Status = 'New'");

        list($NumOtherReports) = G::$DB->next_record();
        G::$Cache->cache_value('num_other_reports', $NumOtherReports, 0);
    }

    if ($NumOtherReports > 0) {
        $ModBar[] = '<a href="reports.php">'.$NumOtherReports.(($NumTorrentReports === 1) ? ' Other report' : ' Other reports').'</a>';
    }
} elseif (check_perms('project_team')) {
    $NumUpdateReports = G::$Cache->get_value('num_update_reports');
    if ($NumUpdateReports === false) {
        G::$DB->query("
          SELECT COUNT(ID)
          FROM reportsv2
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
          FROM reportsv2
          WHERE Status = 'New'
            AND Type IN('artist_comment', 'collages_comment', 'post', 'requests_comment', 'thread', 'torrents_comment')");

        list($NumForumReports) = G::$DB->next_record();
        G::$Cache->cache_value('num_forum_reports', $NumForumReports, 0);
    }

    if ($NumForumReports > 0) {
        $ModBar[] = '<a href="reports.php">'.$NumForumReports.(($NumForumReports === 1) ? ' Forum report' : ' Forum reports').'</a>';
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
    <?php foreach ($Alerts as $Alert) { ?>
    <div class="alertbar warning">
      <?=$Alert?>
    </div>
    <?php
  }

  if (!empty($ModBar)) { ?>
    <div class="alertbar modbar">
      <?=implode(' ', $ModBar); echo "\n"?>
    </div>
    <?php }

if (check_perms('site_debug') && !apcu_exists('DBKEY')) { ?>
    <div class="alertbar error">
      Warning: <a href="tools.php?action=database_key">no DB key</a>
    </div>
    <?php } ?>
  </div>
  <?php
    // Done handling alertbars
}

# #content is Gazelle, .container is Skeleton
echo '<main id="content" class="container">';
