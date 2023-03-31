<?php
#declare(strict_types=1);


/**
 * THIS WHOLE FILE IS GOING AWAY
 */

$app = \Gazelle\App::go();
#!d($app->userNew);exit;

$ENV = ENV::go();
$twig = Twig::go();
$View = new View();

if ($ENV->dev) {
    $debug = Debug::go();
    $Render = $debug->getJavascriptRenderer();
}
?>

<!doctype html>
<html>

<head>
  <?php if ($ENV->dev) {
      echo $Render->renderHead();
  } ?>

  <title>
    <?= Text::esc($PageTitle) ?>
  </title>
  <script defer data-domain="<?= $ENV->siteDomain ?>"
    src="https://stats.torrents.bio/js/plausible.js"></script>

  <?=
      $twig->render(
          '_base/metaTags.twig',
          [
          'title' => Text::esc($PageTitle)
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
          #'vendor/instantpage.min',
          'ajax.class',
          #'menus',
          'global',
      ],
        explode(',', $JSIncludes)
    )
);

foreach ($Scripts as $Script) {
    echo $View->pushAsset(
        "$ENV->staticServer/js/$Script.js",
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

if ($ENV->dev) {
    array_push($Styles, 'development');
}

foreach ($Styles as $Style) {
    echo $View->pushAsset(
        "$ENV->staticServer/css/$Style.css",
        'style'
    );
}

# Fonts
/*
$Fonts = ['fa-brands-400', 'fa-regular-400', 'fa-solid-900'];
foreach ($Fonts as $Font) {
    echo $View->pushAsset(
        "$ENV->staticServer/css/vendor/fa/webfonts/$Font.woff2",
        'font'
    );
}
*/

/**
 * User notification feeds
 * (generic feeds in HTML below)
 */
$ID = $app->userNew->core['id'];
$RssAuth = $app->userNew->extra['RSS_Auth'] ?? "";
$PassKey = $app->userNew->extra['torrent_pass'] ?? "";
$AuthKey = $app->userNew->extra['AuthKey'] ?? "";

if (isset($app->userNew->extra['Notify'])) {
    foreach ($app->userNew->extra['Notify'] as $Filter) {
        list($FilterID, $FilterName) = $Filter;
        $NameEsc = Text::esc($FilterName);

        /* @todo temporary, fix
        echo $HTML = <<<HTML
        <link rel="alternate" type="application/rss+xml"
          href="feeds.php?feed=torrents_notify_$FilterID_$PassKey&amp;user=$ID&amp;auth=$RssAuth&amp;passkey=$PassKey&amp;authkey=$AuthKey"
          title="$NameEsc $ENV->separator $ENV->siteName" />
HTML;
        */
    }
}

# New uploads in each categoty
foreach ($ENV->CATS as $Cat) {
    $name = urlencode(strtolower($Cat->Name));

    echo $HTML = <<<HTML
    <link rel="alternate" type="application/rss+xml"
      href="feeds.php?feed=torrents_$name&amp;user=$ID&amp;auth=$RssAuth&amp;passkey=$PassKey&amp;authkey=$AuthKey"
      title="New $Cat->Name Torrents $ENV->separator $ENV->siteName" />
HTML;
}

# All torrents, news, and blog
echo $HTML = <<<HTML
<link rel="alternate" type="application/rss+xml"
  href="feeds.php?feed=torrents_all&amp;user=$ID&amp;auth=$RssAuth&amp;passkey=$PassKey&amp;authkey=$AuthKey"
  title="All New Torrents $ENV->separator $ENV->siteName" />

<link rel="alternate" type="application/rss+xml"
  href="feeds.php?feed=feed_news&amp;user=$ID&amp;auth=$RssAuth&amp;passkey=$PassKey&amp;authkey=$AuthKey"
  title="News $ENV->separator $ENV->siteName" />

<link rel="alternate" type="application/rss+xml"
  href="feeds.php?feed=feed_news&amp;user=$ID&amp;auth=$RssAuth&amp;passkey=$PassKey&amp;authkey=$AuthKey"
  title="Blog $ENV->separator $ENV->siteName" />
HTML;

/**
 * User stylesheet
 */

if (empty($app->userNew->extra['StyleURL'])) {
    /*
      $StyleColors = $app->cacheNew->get('stylesheet_colors') ?? [];
      if (empty($StyleColors)) {
          $app->dbOld->query('SELECT LOWER(REPLACE(Name, " ", "_")) AS Name, Color FROM stylesheets WHERE COLOR IS NOT NULL');

          while (list($StyleName, $StyleColor) = $app->dbOld->next_record()) {
              #!d($StyleColors, $StyleName);exit;
              $StyleName = $StyleName ?? "";
              $StyleColors[$StyleName] = $StyleColor ?? null;
          }
          $app->cacheNew->set('stylesheet_colors', $StyleColors, 0);
      }

      if (isset($StyleColors[$app->userNew->extra['StyleName']])) { ?>
    <meta name="theme-color"
      content="<?=$StyleColors[$app->userNew->extra['StyleName']]?>">
    <?php }
    */

    /*
    $userStyle = "$ENV->staticServer/css/" . ($app->userNew->extra['StyleName'] ??  "bookish"). ".css";
    echo $View->pushAsset(
        $userStyle,
        'style'
    );
    */
    $userStyle = "$ENV->staticServer/css/" . "bookish". ".css";
    echo $View->pushAsset(
        $userStyle,
        'style'
    );
} else {
    $StyleURLInfo = parse_url($app->userNew->extra['StyleURL']);
    if (substr($app->userNew->extra['StyleURL'], -4) === '.css'
        && empty($StyleURLInfo['query']) && empty($StyleURLInfo['fragment'])
        && ($StyleURLInfo['host'] === siteDomain)
        && file_exists(serverRoot.$StyleURLInfo['path'])) {
        $StyleURL = $app->userNew->extra['StyleURL'].'?v='.filemtime(serverRoot.$StyleURLInfo['path']);
    } else {
        $StyleURL = $app->userNew->extra['StyleURL'];
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

$NotificationsManager = new NotificationsManager($app->userNew->core['id']);
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
  if (!empty($app->userNew->extra['StyleAdditions'])) {
      $BodyStyles = 'style_'.implode(' style_', $app->userNew->extra['StyleAdditions']);
  }
?>

<body
  id="<?=$Document === 'collages' ? 'collage' : $Document?>"
  class="<?=($BodyStyles??'')?>">
  <div id="wrapper">
    <h1 class="hidden">
      <?= $ENV->siteName ?>
    </h1>

    <?= $twig->render(
    '_base/mainMenu.twig',
    [
              'inbox' => Inbox::get_inbox_link(),
              'notify' => check_perms('site_torrents_notify'),
            ]
);
?>

<?= $twig->render(
    '_base/userInfo.twig',
    [
          'inbox' => Inbox::get_inbox_link(),
          'notify' => check_perms('site_torrents_notify'),
        ]
);
?>

    <?php
if (isset($app->userNew->extra['SearchType']) && $app->userNew->extra['SearchType']) { // Advanced search
    $UseAdvancedSearch = true;
} else {
    $UseAdvancedSearch = false;
}
?>

<!-- OLD USER INFO
    <div id="userinfo">
      <ul id="userinfo_major">

        <li id="nav_upload">
          <a href="upload.php">Upload</a>
        </li>
-->

        <?php
        /* OLD USER INFO
if (check_perms('site_send_unlimited_invites')) {
    $Invites = ' (∞)';
} elseif ($app->userNew->extra['Invites'] > 0) {
    $Invites = ' ('.$app->userNew->extra['Invites'].')';
} else {
    $Invites = '';
}
*/
?>
<!-- OLD USER INFO
        <li id="nav_invite">
          <a href="user.php?action=invite">Invite<?=null#$Invites?></a>
        </li>
-->
        <?php if ($ENV->enableDonations) { ?>
<!-- OLD USER INFO
        <li id="nav_donate">
          <a href="donate.php">Donate</a>
        </li>
-->
        <?php } ?>

<!-- OLD USER INFO
        <li id="nav_staff">
          <a href="staff.php">Staff</a>
        </li>
      </ul>

      <ul id="userinfo_stats">
        <li id="stats_seeding">
          <a
            href="torrents.php?type=seeding&amp;userid=<?=null#$app->userNew->core["id"]?>">Up</a>:
          <span class="stat tooltip"
            title="<?=null#Format::get_size($app->userNew->extra['Uploaded'], 5)?>"><?=null#Format::get_size($app->userNew->extra['Uploaded'])?></span>
        </li>

        <li id="stats_leeching">
          <a
            href="torrents.php?type=leeching&amp;userid=<?=null#$app->userNew->core["id"]?>">Down</a>:
          <span class="stat tooltip"
            title="<?=null#Format::get_size($app->userNew->extra['Downloaded'], 5)?>"><?=null#Format::get_size($app->userNew->extra['Downloaded'])?></span>
        </li>

        <li id="stats_ratio">
          Ratio: <span class="stat"><?=null#Format::get_ratio_html($app->userNew->extra['Uploaded'], $app->userNew->extra['Downloaded'])?></span>
        </li>
-->
        <?php if (!empty($app->userNew->extra['RequiredRatio']) && $app->userNew->extra['RequiredRatio'] > 0) { ?>
<!-- OLD USER INFO
        <li id="stats_required">
          <a href="/rules/ratio">Required</a>:
          <span class="stat tooltip"
            title="<?=null#Text::float($app->userNew->extra['RequiredRatio'], 5)?>"><?=null#Text::float($app->userNew->extra['RequiredRatio'], 2)?></span>
        </li>
        <?php } ?>
      </ul>
-->


<!-- OLD USER INFO
      <ul id="userinfo_extra">

        <?php if ($app->userNew->extra['FLTokens'] > 0) { ?>
        <li id="fl_tokens">
          <a href="wiki.php?action=article&amp;name=tokens">Tokens</a>:
          <span class="stat">
            <a
              href="userhistory.php?action=token_history&amp;userid=<?=null#$app->userNew->core["id"]?>"><?=null#$app->userNew->extra['FLTokens']?></a>
          </span>
        </li>
        <?php } ?>

        <li id="bonus_points">
          <a href="wiki.php?action=article&amp;name=bonuspoints"><?=null#bonusPoints?></a>:
          <span class="stat">
            <a href="store.php"><?=null#Text::float($app->userNew->extra['BonusPoints'])?></a>
          </span>
        </li>

        <?php if ($app->userNew->extra['HnR'] > 0) { ?>
        <li id="hnr">
          <a href="snatchlist.php">HnRs</a>:
          <span class="stat">
            <a><?=null#$app->userNew->extra['HnR']?></a>
          </span>
        </li>
        <?php } ?>
      </ul>
    </div>
-->

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

/*
# todo: bring this back
if ($app->userNew->extra['RatioWatch']) {
    $Alerts[] = '<a href="/rules/ratio">Ratio Watch</a>: You have '.time_diff($app->userNew->extra['RatioWatchEnds'], 3).' to get your ratio over your required ratio or your leeching abilities will be disabled.';
} elseif ((int) $app->userNew->extra['CanLeech'] !== 1) {
    $Alerts[] = '<a href="/rules/ratio">Ratio Watch</a>: Your downloading privileges are disabled until you meet your required ratio.';
}
*/

// Torrents
if ($NotificationsManager->is_traditional(NotificationsManager::TORRENTS)) {
    $NotificationsManager->load_torrent_notifications();
    $NewTorrents = $NotificationsManager->get_notifications();

    if (isset($NewTorrents[NotificationsManager::TORRENTS])) {
        $Alerts[] = NotificationsManagerView::format_traditional($NewTorrents[NotificationsManager::TORRENTS]);
    }
    $NotificationsManager->clear_notifications_array();
}

if (check_perms('users_mod')) {
    $ModBar[] = '<a href="tools.php">Toolbox</a>';
}

/** Buggy af rn 2022-01-12
if (check_perms('users_mod')) {
    $NumStaffPMs = $app->cacheNew->get('num_staff_pms_'.$app->userNew->core["id"]);
    if ($NumStaffPMs === false) {
        if (check_perms('users_mod')) {
            $LevelCap = 1000;
            $app->dbOld->query("
              SELECT COUNT(ID)
              FROM staff_pm_conversations
              WHERE Status = 'Unanswered'
                AND (AssignedToUser = ".$app->userNew->core["id"]."
                  OR (LEAST('$LevelCap', Level) <= '".$app->userNew->extra['EffectiveClass']."'
                    AND Level <= ".$app->userNew->extra['Class']."))");
        }

        if ($app->userNew->extra['PermissionID'] === FORUM_MOD) {
            $app->dbOld->query("
              SELECT COUNT(ID)
              FROM staff_pm_conversations
              WHERE Status='Unanswered'
                AND (AssignedToUser = ".$app->userNew->core["id"]."
                  OR Level = '". $Classes[FORUM_MOD]['Level'] . "')");
        }

        list($NumStaffPMs) = $app->dbOld->next_record();
        $app->cacheNew->set('num_staff_pms_'.$app->userNew->core["id"], $NumStaffPMs, 1000);
    }

    if ($NumStaffPMs > 0) {
        $ModBar[] = '<a href="staffpm.php">'.$NumStaffPMs.' Staff PMs</a>';
    }
}
*/

if (check_perms('admin_reports')) {
    // Torrent reports code
    $NumTorrentReports = $app->cacheNew->get('num_torrent_reportsv2');
    if ($NumTorrentReports === false) {
        $app->dbOld->query("
          SELECT COUNT(ID)
          FROM reportsv2
          WHERE Status = 'New'");

        list($NumTorrentReports) = $app->dbOld->next_record();
        $app->cacheNew->set('num_torrent_reportsv2', $NumTorrentReports, 0);
    }

    $ModBar[] = '<a href="reportsv2.php">'.$NumTorrentReports.(($NumTorrentReports === 1) ? ' Report' : ' Reports').'</a>';

    // Other reports code
    $NumOtherReports = $app->cacheNew->get('num_other_reports');
    if ($NumOtherReports === false) {
        $app->dbOld->query("
          SELECT COUNT(ID)
          FROM reportsv2
          WHERE Status = 'New'");

        list($NumOtherReports) = $app->dbOld->next_record();
        $app->cacheNew->set('num_other_reports', $NumOtherReports, 0);
    }

    if ($NumOtherReports > 0) {
        $ModBar[] = '<a href="reports.php">'.$NumOtherReports.(($NumTorrentReports === 1) ? ' Other report' : ' Other reports').'</a>';
    }
} elseif (check_perms('project_team')) {
    $NumUpdateReports = $app->cacheNew->get('num_update_reports');
    if ($NumUpdateReports === false) {
        $app->dbOld->query("
          SELECT COUNT(ID)
          FROM reportsv2
          WHERE Status = 'New'
            AND Type = 'request_update'");

        list($NumUpdateReports) = $app->dbOld->next_record();
        $app->cacheNew->set('num_update_reports', $NumUpdateReports, 0);
    }

    if ($NumUpdateReports > 0) {
        $ModBar[] = '<a href="reports.php">Request update reports</a>';
    }
} elseif (check_perms('site_moderate_forums')) {
    $NumForumReports = $app->cacheNew->get('num_forum_reports');
    if ($NumForumReports === false) {
        $app->dbOld->query("
          SELECT COUNT(ID)
          FROM reportsv2
          WHERE Status = 'New'
            AND Type IN('artist_comment', 'collages_comment', 'post', 'requests_comment', 'thread', 'torrents_comment')");

        list($NumForumReports) = $app->dbOld->next_record();
        $app->cacheNew->set('num_forum_reports', $NumForumReports, 0);
    }

    if ($NumForumReports > 0) {
        $ModBar[] = '<a href="reports.php">'.$NumForumReports.(($NumForumReports === 1) ? ' Forum report' : ' Forum reports').'</a>';
    }
}

if (check_perms('users_mod') && FEATURE_EMAIL_REENABLE) {
    $NumEnableRequests = $app->cacheNew->get(AutoEnable::CACHE_KEY_NAME);
    if ($NumEnableRequests === false) {
        $app->dbOld->query("SELECT COUNT(1) FROM users_enable_requests WHERE Outcome IS NULL");
        list($NumEnableRequests) = $app->dbOld->next_record();
        $app->cacheNew->set(AutoEnable::CACHE_KEY_NAME, $NumEnableRequests);
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
      <?=implode(' ', $ModBar);
      echo "\n"?>
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
