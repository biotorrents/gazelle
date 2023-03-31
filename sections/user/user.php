<?php

declare(strict_types=1);


/**
 * user profile page
 */

$app = \Gazelle\App::go();

# https://github.com/paragonie/anti-csrf
Http::csrf();

# request vars
$get = Http::query("get");
$post = Http::query("post");

$get["id"] ??= null;
$userId = Esc::int($get["id"]);

$get["previewMode"] ??= null;
$previewMode = Esc::bool($get["previewMode"]);

# user data
$data = $app->userNew->readProfile($userId);
#!d($data);exit;

# own profile?
$isOwnProfile = false;
if ($userId === $app->userNew->core["id"]) {
    $isOwnProfile = true;
}

# preview mode
if ($isOwnProfile && $previewMode) {
    $isOwnProfile = false;
}

# is the user your friend?
$query = "select 1 from users_friends where userId = ? and friendId = ?";
$good = $app->dbNew->single($query, [$app->userNew->core["id"], $userId]);

$isFriend = false;
if ($good) {
    $isFriend = true;
}

# avatar = the last airbender
$avatar = User::displayAvatar($data["extra"]["Avatar"], $data["core"]["username"]);

# badges
if ($isOwnProfile) {
    $badges = Badges::getBadges($userId);
    $badgesDisplay = Badges::displayBadges(array_keys($badges), true);
} else {
    $badges = Badges::getDisplayedBadges($userId);
    $badgesDisplay = Badges::displayBadges($badges, true);
}


/** recent torrent activity */


$recentSnatches = $app->userNew->recentSnatches($userId);
#!d($recentSnatches);exit;

$recentUploads = $app->userNew->recentUploads($userId);
#!d($recentUploads);exit;

$recentRequests = $app->userNew->recentRequests($userId);
#!d($recentRequests);exit;

$recentCollages = $app->userNew->recentCollages($userId);
#!d($recentCollages);exit;


/** user stats */


$communityStats = $app->userNew->communityStats($userId);
#!d($communityStats);exit;

# build request stats
$requestStats = [];
foreach ($communityStats as $key => $value) {
    if (str_starts_with($key, "requests")) {
        $requestStats[$key] = $value;
        unset($communityStats[$key]);
    }
}

# unset comments
unset($communityStats["collageComments"]);
unset($communityStats["creatorComments"]);
unset($communityStats["requestComments"]);
unset($communityStats["torrentComments"]);

# unset misc
unset($communityStats["ircLines"]);


$torrentStats = $app->userNew->torrentStats($userId);
#!d($torrentStats);exit;

# unset misc
$ratio = $torrentStats["ratio"];
unset($torrentStats["ratio"]);

$torrentClients = $torrentStats["torrentClients"];
unset($torrentStats["torrentClients"]);

$percentileStats = $app->userNew->percentileStats($userId);
#!d($percentileStats);exit;


/** twig template */


$app->twig->display("user/profile/profile.twig", [
  "sidebar" => true,

  #"css" => [""],
  "js" => ["user", "requests", "wall", "vendor/chart.min"],

  "data" => $data,
  "siteOptions" => $data["extra"]["siteOptions"],

  #"error" => $error ?? null,

  "isOwnProfile" => $isOwnProfile,
  "previewMode" => $previewMode,
  "isFriend" => $isFriend,
  "avatar" => $avatar,
  "badges" => $badges,
  "badgesDisplay" => $badgesDisplay,

  # recent torrent activity
  "recentSnatches" => $recentSnatches,
  "recentUploads" => $recentUploads,
  "recentRequests" => $recentRequests,
  "recentCollages" => $recentCollages,

  # user stats
  "communityStats" => $communityStats,
  "requestStats" => $requestStats,
  "torrentStats" => $torrentStats,
  "ratio" => $ratio,
  "percentileStats" => $percentileStats,



 ]);

exit;








$app->dbOld->query("
  SELECT SUM(t.Size)
  FROM xbt_files_users AS xfu
  JOIN torrents AS t on t.ID = xfu.fid
  WHERE
    xfu.uid = '$userId'
    AND xfu.active = 1
    AND xfu.Remaining = 0");
if ($app->dbOld->has_results()) {
    list($TotalSeeding) = $app->dbOld->next_record(MYSQLI_NUM, false);
}


// Image proxy CTs
$DisplayCustomTitle = $CustomTitle;
if (check_perms('site_proxy_images') && !empty($CustomTitle)) {
    $DisplayCustomTitle = preg_replace_callback(
        '~src=("?)(http.+?)(["\s>])~',
        function ($Matches) {
            return 'src=' . $Matches[1] . ImageTools::process($Matches[2]) . $Matches[3];
        },
        $CustomTitle
    );
}

?>
    <div class="box box_info box_userinfo_stats">
      <div class="head colhead_dark">Statistics</div>
      <ul class="stats nobullet">
        <li>Joined: <?=$JoinedDate?>
        </li>
        <?php if (($Override = check_paranoia_here('lastseen'))) { ?>
        <li<?=($Override === 2 ? ' class="paranoia_override"' : '')?>>Last
          seen: <?=$LastAccess?>
          </li>
          <?php
        }
  if (($Override = check_paranoia_here('uploaded'))) {
      ?>
          <li
            class="tooltip<?=($Override === 2 ? ' paranoia_override' : '')?>"
            title="<?=Format::get_size($Uploaded, 5)?>">Uploaded:
            <?=Format::get_size($Uploaded)?>
          </li>
          <?php
  }
  if (($Override = check_paranoia_here('downloaded'))) {
      ?>
          <li
            class="tooltip<?=($Override === 2 ? ' paranoia_override' : '')?>"
            title="<?=Format::get_size($Downloaded, 5)?>">Downloaded:
            <?=Format::get_size($Downloaded)?>
          </li>
          <?php
  }
  if (($Override = check_paranoia_here('ratio'))) {
      ?>
          <li<?=($Override === 2 ? ' class="paranoia_override"' : '')?>>Ratio:
            <?=Format::get_ratio_html($Uploaded, $Downloaded)?>
            </li>
            <?php
  }
  if (($Override = check_paranoia_here('requiredratio')) && isset($RequiredRatio)) {
      ?>
            <li<?=($Override === 2 ? ' class="paranoia_override"' : '')?>>Required
              Ratio: <span class="tooltip"
                title="<?=Text::float((float)$RequiredRatio, 5)?>"><?=Text::float((float)$RequiredRatio, 2)?></span></li>
              <?php
  }
  if (($Override = check_paranoia_here('downloaded'))) {
      ?>
              <li<?=($Override === 2 ? ' class="paranoia_override"' : '')?>>Total
                Seeding: <span class="tooltip"
                  title="<?=Format::get_size($TotalSeeding)?>"><?=Format::get_size($TotalSeeding)?>
                  </li>
                  <?php
  }
  if ($isOwnProfile || ($Override = check_paranoia_here(false)) || check_perms('users_mod')) {
      ?>
                  <li<?=($Override === 2 ? ' class="paranoia_override"' : '')?>><a
                      href="userhistory.php?action=token_history&amp;userid=<?=$userId?>">Tokens</a>:
                    <?=Text::float($FLTokens)?>
                    </li>
                    <?php
  }
  if (($isOwnProfile || check_perms('users_mod')) && $Warned) {
      ?>
                    <li<?=($Override === 2 ? ' class="paranoia_override"' : '')?>>Warning
                      expires in: <?=time_diff((date('Y-m-d H:i', strtotime($Warned))))?>
                      </li>
                      <?php
  } ?>
      </ul>
    </div>
    <?php

if (check_paranoia_here('requestsfilled_count') || check_paranoia_here('requestsfilled_bounty')) {
    $app->dbOld->query("
    SELECT
      COUNT(DISTINCT r.ID),
      SUM(rv.Bounty)
    FROM requests AS r
      LEFT JOIN requests_votes AS rv ON r.ID = rv.RequestID
    WHERE r.FillerID = $userId");
    list($RequestsFilled, $TotalBounty) = $app->dbOld->next_record();
} else {
    $RequestsFilled = $TotalBounty = 0;
}

if (check_paranoia_here('requestsvoted_count') || check_paranoia_here('requestsvoted_bounty')) {
    $app->dbOld->query("
    SELECT COUNT(RequestID), SUM(Bounty)
    FROM requests_votes
    WHERE UserID = $userId");
    list($RequestsVoted, $TotalSpent) = $app->dbOld->next_record();
    $app->dbOld->query("
    SELECT COUNT(r.ID), SUM(rv.Bounty)
    FROM requests AS r
      LEFT JOIN requests_votes AS rv ON rv.RequestID = r.ID AND rv.UserID = r.UserID
    WHERE r.UserID = $userId");
    list($RequestsCreated, $RequestsCreatedSpent) = $app->dbOld->next_record();
} else {
    $RequestsVoted = $TotalSpent = $RequestsCreated = $RequestsCreatedSpent = 0;
}

if (check_paranoia_here('uploads+')) {
    $app->dbOld->query("
    SELECT COUNT(ID)
    FROM torrents
    WHERE UserID = '$userId'");
    list($Uploads) = $app->dbOld->next_record();
} else {
    $Uploads = 0;
}

if (check_paranoia_here('artistsadded')) {
    $app->dbOld->query("
    SELECT COUNT(DISTINCT ArtistID)
    FROM torrents_artists
    WHERE UserID = $userId");
    list($ArtistsAdded) = $app->dbOld->next_record();
} else {
    $ArtistsAdded = 0;
}

//Do the ranks
$UploadedRank = UserRank::get_rank('uploaded', $Uploaded);
$DownloadedRank = UserRank::get_rank('downloaded', $Downloaded);
$UploadsRank = UserRank::get_rank('uploads', $Uploads);
$RequestRank = UserRank::get_rank('requests', $RequestsFilled);
$PostRank = UserRank::get_rank('posts', $ForumPosts);
$BountyRank = UserRank::get_rank('bounty', $TotalSpent);
$ArtistsRank = UserRank::get_rank('artists', $ArtistsAdded);

if ($Downloaded == 0) {
    $Ratio = 1;
} elseif ($Uploaded == 0) {
    $Ratio = 0.5;
} else {
    $Ratio = round($Uploaded / $Downloaded, 2);
}
$OverallRank = UserRank::overall_score($UploadedRank, $DownloadedRank, $UploadsRank, $RequestRank, $PostRank, $BountyRank, $ArtistsRank, $Ratio);

?>
    <div class="box box_info box_userinfo_percentile">
      <div class="head colhead_dark">Percentile Rankings (hover for values)</div>
      <ul class="stats nobullet">
        <?php if (($Override = check_paranoia_here('uploaded'))) { ?>
        <li
          class="tooltip<?=($Override === 2 ? ' paranoia_override' : '')?>"
          title="<?=Format::get_size($Uploaded)?>">Data uploaded:
          <?=$UploadedRank === false ? 'Server busy' : Text::float($UploadedRank)?>
        </li>
        <?php
        }
  if (($Override = check_paranoia_here('downloaded'))) { ?>
        <li
          class="tooltip<?=($Override === 2 ? ' paranoia_override' : '')?>"
          title="<?=Format::get_size($Downloaded)?>">Data downloaded:
          <?=$DownloadedRank === false ? 'Server busy' : Text::float($DownloadedRank)?>
        </li>
        <?php
  }
  if (($Override = check_paranoia_here('uploads+'))) { ?>
        <li
          class="tooltip<?=($Override === 2 ? ' paranoia_override' : '')?>"
          title="<?=Text::float($Uploads)?>">Torrents uploaded:
          <?=$UploadsRank === false ? 'Server busy' : Text::float($UploadsRank)?>
        </li>
        <?php
  }
  if (($Override = check_paranoia_here('requestsfilled_count'))) { ?>
        <li
          class="tooltip<?=($Override === 2 ? ' paranoia_override' : '')?>"
          title="<?=Text::float($RequestsFilled)?>">Requests
          filled: <?=$RequestRank === false ? 'Server busy' : Text::float($RequestRank)?>
        </li>
        <?php
  }
  if (($Override = check_paranoia_here('requestsvoted_bounty'))) { ?>
        <li
          class="tooltip<?=($Override === 2 ? ' paranoia_override' : '')?>"
          title="<?=Format::get_size($TotalSpent)?>">Bounty spent:
          <?=$BountyRank === false ? 'Server busy' : Text::float($BountyRank)?>
        </li>
        <?php } ?>
        <li class="tooltip" title="<?=Text::float($ForumPosts)?>">
          Posts made: <?=$PostRank === false ? 'Server busy' : Text::float($PostRank)?>
        </li>
        <?php if (($Override = check_paranoia_here('artistsadded'))) { ?>
        <li
          class="tooltip<?=($Override === 2 ? ' paranoia_override' : '')?>"
          title="<?=Text::float($ArtistsAdded)?>">Artists added:
          <?=$ArtistsRank === false ? 'Server busy' : Text::float($ArtistsRank)?>
        </li>
        <?php
        }
  if (check_paranoia_here(array('uploaded', 'downloaded', 'uploads+', 'requestsfilled_count', 'requestsvoted_bounty', 'artistsadded'))) { ?>
        <li><strong>Overall rank: <?=$OverallRank === false ? 'Server busy' : Text::float($OverallRank)?></strong>
        </li>
        <?php } ?>
      </ul>
    </div>
    <?php
           if (check_perms('users_view_ips', $Class)) {
               $app->dbOld->query("
        SELECT COUNT(DISTINCT IP)
        FROM xbt_snatched
        WHERE uid = '$userId'
          AND IP != ''");
               list($TrackerIPs) = $app->dbOld->next_record();
           }
?>
    <div class="box box_info box_userinfo_history">
      <div class="head colhead_dark">History</div>
      <ul class="stats nobullet">
        <?php
if (check_perms('users_view_ips', $Class)) {
    ?>
        <?php if (check_perms('users_view_ips', $Class) && check_perms('users_mod', $Class)) { ?>
        <li>Tracker IPs: <?=Text::float($TrackerIPs)?> <a
            href="userhistory.php?action=tracker_ips&amp;userid=<?=$userId?>"
            class="brackets">View</a></li>
        <?php
        }
}
     if (check_perms('users_mod', $Class)) {
         ?>
        <li>Stats: N/A <a
            href="userhistory.php?action=stats&amp;userid=<?=$userId?>"
            class="brackets">View</a></li>
        <?php
     } ?>
      </ul>
    </div>

    <div class="box box_info box_userinfo_personal">
      <div class="head colhead_dark">Personal</div>
      <ul class="stats nobullet">
        <li>Class: <?=$ClassLevels[$Class]['Name']?>
        </li>
        <?php
$UserInfo = User::user_info($userId);
if (!empty($UserInfo['ExtraClasses'])) {
    ?>
        <li>
          <ul class="stats">
            <?php
  foreach ($UserInfo['ExtraClasses'] as $PermID => $Val) {
      ?>
            <li><?=$Classes[$PermID]['Name']?>
            </li>
            <?php
  } ?>
          </ul>
        </li>
        <?php
}
// An easy way for people to measure the paranoia of a user, for e.g. contest eligibility
if ($ParanoiaLevel == 0) {
    $ParanoiaLevelText = 'Off';
} elseif ($ParanoiaLevel == 1) {
    $ParanoiaLevelText = 'Very Low';
} elseif ($ParanoiaLevel <= 5) {
    $ParanoiaLevelText = 'Low';
} elseif ($ParanoiaLevel <= 20) {
    $ParanoiaLevelText = 'High';
} else {
    $ParanoiaLevelText = 'Very high';
}
?>
        <li>Paranoia level: <span class="tooltip"
            title="<?=$ParanoiaLevel?>"><?=$ParanoiaLevelText?></span></li>
        <?php if (check_perms('users_view_email', $Class) || $isOwnProfile) { ?>
        <li>Email: <a href="mailto:<?=Text::esc($Email)?>"><?=Text::esc($Email)?></a>
        </li>
        <?php }

        if (check_perms('users_view_ips', $Class)) {
            $IP = apcu_exists('DBKEY') ? Crypto::decrypt($IP) : '[Encrypted]'; ?>
        <li>IP: <?=Text::esc($IP)?>
        </li>
        <?php
        }

if (check_perms('users_view_keys', $Class) || $isOwnProfile) {
    ?>
        <li>Passkey: <a href="#" id="passkey"
            onclick="togglePassKey('<?=Text::esc($torrent_pass)?>'); return false;"
            class="brackets">View</a></li>
        <?php
}
if (check_perms('users_view_invites')) {
    if (!$InviterID) {
        $Invited = '<span style="font-style: italic;">Nobody</span>';
    } else {
        $Invited = "<a href=\"user.php?id=$InviterID\">$InviterName</a>";
    } ?>
        <li>Invited by: <?=$Invited?>
        </li>
        <li>Invites:
          <?php
        $app->dbOld->query("
          SELECT COUNT(InviterID)
          FROM invites
          WHERE InviterID = '$userId'");
    list($Pending) = $app->dbOld->next_record();
    if ($DisableInvites) {
        echo 'X';
    } else {
        echo Text::float($Invites);
    }
    echo " ($Pending)"
    ?>
        </li>
        <?php
}

if (!isset($SupportFor)) {
    $app->dbOld->query('
    SELECT SupportFor
    FROM users_info
    WHERE UserID = '.$user['ID']);
    list($SupportFor) = $app->dbOld->next_record();
}
if ($Override = check_perms('users_mod') || $isOwnProfile || !empty($SupportFor)) {
    ?>
        <li<?=(($Override === 2 || $SupportFor) ? ' class="paranoia_override"' : '')?>>Clients:
          <?php
    $app->dbOld->query("
      SELECT DISTINCT useragent
      FROM xbt_files_users
      WHERE uid = $userId");
    $Clients = $app->dbOld->collect(0);
    echo implode('; ', $Clients); ?>
          </li>
          <?php
}
?>
      </ul>
    </div>
    <?php
include(serverRoot.'/sections/user/community_stats.php');
?>
  </div>
  <div class="main_column two-thirds column">
    <?php
if ($RatioWatchEnds && (time() < strtotime($RatioWatchEnds)) && ($Downloaded * $RequiredRatio) > $Uploaded) {
    ?>
    <div class="box">
      <div class="head">Ratio watch</div>
      <div class="pad">This user is currently on ratio watch and must upload <?=Format::get_size(($Downloaded * $RequiredRatio) - $Uploaded)?> in
        the next <?=time_diff($RatioWatchEnds)?>, or their leeching
        privileges will be revoked. Amount downloaded while on ratio watch: <?=Format::get_size($Downloaded - $RatioWatchDownload)?>
      </div>
    </div>
    <?php
}
?>
    <div class="box">
      <div class="head">
        <?=!empty($InfoTitle) ? $InfoTitle : 'Profile';?>
        <span class="u-pull-right"><a data-toggle-target="#profilediv" data-toggle-replace="Show"
            class="brackets">Hide</a></span>&nbsp;
      </div>
      <div class="pad profileinfo" id="profilediv">
        <?php
if (!$Info) {
    ?>
        This profile is currently empty.
        <?php
} else {
    echo Text::parse($Info);
}
?>
      </div>
    </div>
    <?php

if (check_paranoia_here('snatched')) {
    $RecentSnatches = $app->cacheOld->get_value("recent_snatches_$userId");
    if ($RecentSnatches === false) {
        $app->dbOld->prepared_query("
        SELECT
          g.`id`,
          g.`title`,
          g.`subject`,
          g.`object`,
          g.`picture`
        FROM
          `xbt_snatched` AS s
        INNER JOIN `torrents` AS t
        ON
          t.`ID` = s.`fid`
        INNER JOIN `torrents_group` AS g
        ON
          t.`GroupID` = g.`id`
        WHERE
          s.`uid` = '$userId' AND g.`picture` != ''
        GROUP BY
          g.`id`,
          s.`tstamp`
        ORDER BY
          s.`tstamp`
        DESC
        LIMIT 5
        ");

        $RecentSnatches = $app->dbOld->to_array();

        $Artists = Artists::get_artists($app->dbOld->collect('ID'));
        foreach ($RecentSnatches as $Key => $SnatchInfo) {
            $RecentSnatches[$Key]['Artist'] = Artists::display_artists($Artists[$SnatchInfo['ID']], false, true);
        }

        $app->cacheOld->cache_value("recent_snatches_$userId", $RecentSnatches, 0); //inf cache
    }

    if (!empty($RecentSnatches)) {
        ?>
    <div class="box" id="recent_snatches">
      <div class="head">
        Recent Snatches
        <span class="u-pull-right"><a
            onclick="$('#recent_snatches_images').gtoggle(); this.innerHTML = (this.innerHTML == 'Hide' ? 'Show' : 'Hide'); wall('#recent_snatches_images', '.collage_image', [2,3]); return false;"
            class="brackets">Show</a></span>&nbsp;
      </div>
      <div id="recent_snatches_images" class="collage_images hidden">
        <?php foreach ($RecentSnatches as $RS) {
            $RSName = empty($RS['Name']) ? (empty($RS['Title2']) ? $RS['NameJP'] : $RS['Title2']) : $RS['Name']; ?>
        <div style='width: 100px;' class='collage_image'>
          <a
            href="torrents.php?id=<?=$RS['ID']?>">
            <img class="tooltip"
              title="<?=Text::esc($RS['Artist'])?><?=Text::esc($RSName)?>"
              src="<?=ImageTools::process($RS['WikiImage'], 'thumb')?>"
              alt="<?=Text::esc($RS['Artist'])?><?=Text::esc($RSName)?>"
              width="100%" />
          </a>
        </div>
        <?php
        } ?>
      </div>
    </div>
    <?php
    }
}

if (check_paranoia_here('uploads')) {
    $RecentUploads = $app->cacheOld->get_value("recent_uploads_$userId");
    if ($RecentUploads === false) {
        $app->dbOld->prepared_query("
        SELECT
          g.`id`,
          g.`title`,
          g.`subject`,
          g.`object`,
          g.`picture`
        FROM
          `torrents_group` AS g
        INNER JOIN `torrents` AS t
        ON
          t.`GroupID` = g.`id`
        WHERE
          t.`UserID` = '$userId' AND g.`picture` != ''
        GROUP BY
          g.`id`,
          t.`Time`
        ORDER BY
          t.`Time`
        DESC
        LIMIT 5
        ");

        $RecentUploads = $app->dbOld->to_array();

        $Artists = Artists::get_artists($app->dbOld->collect('ID'));
        foreach ($RecentUploads as $Key => $UploadInfo) {
            $RecentUploads[$Key]['Artist'] = Artists::display_artists($Artists[$UploadInfo['ID']], false, true);
        }

        $app->cacheOld->cache_value("recent_uploads_$userId", $RecentUploads, 0); // inf cache
    }

    if (!empty($RecentUploads)) {
        ?>
    <div class="box" id="recent_uploads">
      <div class="head">
        Recent Uploads
        <span class="u-pull-right"><a
            onclick="$('#recent_uploads_images').gtoggle(); this.innerHTML = (this.innerHTML == 'Hide' ? 'Show' : 'Hide'); wall('#recent_uploads_images', '.collage_image', [2,3]); return false;"
            class="brackets">Show</a></span>&nbsp;
      </div>
      <div id="recent_uploads_images" class="collage_images hidden">
        <?php foreach ($RecentUploads as $RU) {
            $RUName = empty($RU['Name']) ? (empty($RU['Title2']) ? $RU['NameJP'] : $RU['Title2']) : $RU['Name']; ?>
        <div style='width: 100px;' class='collage_image'>
          <a
            href="torrents.php?id=<?=$RU['ID']?>">
            <img class="tooltip"
              title="<?=$RU['Artist']?><?=$RUName?>"
              src="<?=ImageTools::process($RU['WikiImage'], 'thumb')?>"
              alt="<?=$RU['Artist']?><?=$RUName?>"
              width="100%" />
          </a>
        </div>
        <?php
        } ?>
      </div>
    </div>
    <?php
    }
}

$app->dbOld->query("
  SELECT ID, Name
  FROM collages
  WHERE UserID = '$userId'
    AND CategoryID = '0'
    AND Deleted = '0'
  ORDER BY Featured DESC,
    Name ASC");
$Collages = $app->dbOld->to_array(false, MYSQLI_NUM, false);
foreach ($Collages as $CollageInfo) {
    list($CollageID, $CName) = $CollageInfo;

    $app->dbOld->prepared_query("
    SELECT
      ct.GroupID,
      tg.`picture`,
      tg.`category_id`
    FROM
      collages_torrents AS ct
    JOIN torrents_group AS tg
    ON
      tg.`id` = ct.GroupID
    WHERE
      ct.CollageID = '$CollageID'
    ORDER BY
      ct.Sort
    LIMIT 5
    ");


    $Collage = $app->dbOld->to_array(false, MYSQLI_ASSOC, false); ?>
    <div class="box" id="collage<?=$CollageID?>_box">
      <div class="head">
        <?=Text::esc($CName)?> - <a
          href="collages.php?id=<?=$CollageID?>" class="brackets">See
          full</a>
        <span class="u-pull-right">
          <a data-toggle-target="#collage<?=$CollageID?>_box .collage_images"
            data-toggle-replace="Show" class="brackets">Hide</a>
        </span>
      </div>
      <div id="user_collage_images" class="collage_images" data-wall-child=".collage_image" data-wall-size="5">
        <?php foreach ($Collage as $C) {
            $Group = Torrents::get_groups(array($C['GroupID']), true, true, false);
            extract(Torrents::array_group($Group[$C['GroupID']]));

            if (!$C['WikiImage']) {
                $C['WikiImage'] = staticServer.'common/noartwork.png';
            }

            $Name = '';
            $Name .= Artists::display_artists($Artists, false, true);
            $Name .= $GroupName; ?>
        <div class="collage_image">
          <a href="torrents.php?id=<?=$GroupID?>">
            <img class="tooltip" title="<?=$Name?>"
              src="<?=ImageTools::process($C['WikiImage'], 'thumb')?>"
              alt="<?=$Name?>" width="100%" />
          </a>
        </div>
        <?php
        } ?>
      </div>
    </div>
    <?php
}
?>
    <!-- for the "jump to staff tools" button -->
    <a id="staff_tools"></a>
    <?php

// Linked accounts
if (check_perms('users_mod')) {
    include(serverRoot.'/sections/user/linkedfunctions.php');
    user_dupes_table($userId);
}

if ((check_perms('users_view_invites')) && $Invited > 0) {
    include(serverRoot.'/classes/invite_tree.class.php');
    $Tree = new INVITE_TREE($userId, array('visible' => false)); ?>
    <div class="box" id="invitetree_box">
      <div class="head">
        Invite Tree <span class="u-pull-right"><a data-toggle-target="#invitetree" class="brackets">Toggle</a></span>
      </div>
      <div id="invitetree" class="hidden">
        <?php $Tree->make_tree(); ?>
      </div>
    </div>
  </div>
  <?php
}


// Requests
if (empty($user['DisableRequests']) && check_paranoia_here('requestsvoted_list')) {
    $SphQL = new SphinxqlQuery();
    $SphQLResult = $SphQL->select('id, votes, bounty')
    ->from('requests, requests_delta')
    ->where('userid', $userId)
    ->where('torrentid', 0)
    ->order_by('votes', 'desc')
    ->order_by('bounty', 'desc')
    ->limit(0, 100, 100) // Limit to 100 requests
    ->query();
    if ($SphQLResult->has_results()) {
        $SphRequests = $SphQLResult->to_array('id', MYSQLI_ASSOC); ?>
  <div class="box" id="requests_box">
    <div class="head">
      Requests <span class="u-pull-right"><a data-toggle-target="#requests" class="brackets">Show</a></span>
    </div>
    <div id="requests" class="hidden">
      <table cellpadding="6" cellspacing="1" border="0" width="100%">
        <tr class="colhead_dark">
          <td style="width: 48%;">
            <strong>Request Name</strong>
          </td>
          <td>
            <strong>Vote</strong>
          </td>
          <td>
            <strong>Bounty</strong>
          </td>
          <td>
            <strong>Added</strong>
          </td>
        </tr>
        <?php
    $Requests = Requests::get_requests(array_keys($SphRequests));
        foreach ($SphRequests as $RequestID => $SphRequest) {
            $Request = $Requests[$RequestID];
            $VotesCount = $SphRequest['votes'];
            $Bounty = $SphRequest['bounty'] * 1024; // Sphinx stores bounty in kB
            $CategoryName = $Categories[$Request['CategoryID'] - 1];

            if ($CategoryName == 'Music') {
                $ArtistForm = Requests::get_artists($RequestID);
                $ArtistLink = Artists::display_artists($ArtistForm, true, true);
                $FullName = "$ArtistLink<a href=\"requests.php?action=view&amp;id=$RequestID\">$Request[Title] [$Request[Year]]</a>";
            } elseif ($CategoryName == 'Audiobooks' || $CategoryName == 'Comedy') {
                $FullName = "<a href=\"requests.php?action=view&amp;id=$RequestID\">$Request[Title] [$Request[Year]]</a>";
            } else {
                if (!$Request['Title']) {
                    $Request['Title'] = $Request['Title2'];
                }
                if (!$Request['Title']) {
                    $Request['Title'] = $Request['TitleJP'];
                }
                $FullName = "<a href=\"requests.php?action=view&amp;id=$RequestID\">$Request[Title]</a>";
            } ?>
        <tr class="row">
          <td>
            <?=$FullName ?>
            <div class="tags">
              <?php
      $Tags = $Request['Tags'];
            $TagList = [];
            foreach ($Tags as $TagID => $TagName) {
                $TagList[] = "<a href=\"requests.php?tags=$TagName\">".Text::esc($TagName).'</a>';
            }
            $TagList = implode(', ', $TagList); ?>
              <?=$TagList?>
            </div>
          </td>
          <td>
            <span id="vote_count_<?=$RequestID?>"><?=$VotesCount?></span>
            <?php if (check_perms('site_vote')) { ?>
            &nbsp;&nbsp; <a
              href="javascript:Vote(0, <?=$RequestID?>)"
              class="brackets">+</a>
            <?php } ?>
          </td>
          <td>
            <span id="bounty_<?=$RequestID?>"><?=Format::get_size($Bounty)?></span>
          </td>
          <td>
            <?=time_diff($Request['TimeAdded']) ?>
          </td>
        </tr>
        <?php
        } ?>
      </table>
    </div>
  </div>
  <?php
    }
}

$IsFLS = isset($user['ExtraClasses'][FLS_TEAM]);
if (check_perms('users_mod', $Class) || $IsFLS) {
    $UserLevel = $user['EffectiveClass'];
    $app->dbOld->query("
    SELECT
      SQL_CALC_FOUND_ROWS
      ID,
      Subject,
      Status,
      Level,
      AssignedToUser,
      Date,
      ResolverID
    FROM staff_pm_conversations
    WHERE UserID = $userId
      AND (Level <= $UserLevel OR AssignedToUser = '".$user['ID']."')
    ORDER BY Date DESC");
    if ($app->dbOld->has_results()) {
        $StaffPMs = $app->dbOld->to_array(); ?>
  <div class="box" id="staffpms_box">
    <div class="head">
      Staff PMs <a data-toggle-target="#staffpms" class="brackets u-pull-right">Toggle</a>
    </div>
    <table width="100%" class="message_table hidden" id="staffpms">
      <tr class="colhead">
        <td>Subject</td>
        <td>Date</td>
        <td>Assigned to</td>
        <td>Resolved by</td>
      </tr>
      <?php
    foreach ($StaffPMs as $StaffPM) {
        list($ID, $Subject, $Status, $Level, $AssignedToUser, $Date, $ResolverID) = $StaffPM;
        // Get assigned
        if ($AssignedToUser == '') {
            // Assigned to class
            $Assigned = ($Level == 0) ? 'First Line Support' : $ClassLevels[$Level]['Name'];
            // No + on Sysops
            if ($Assigned != 'Sysop') {
                $Assigned .= '+';
            }
        } else {
            // Assigned to user
            $Assigned = User::format_username($userId, true, true, true, true);
        }

        if ($ResolverID) {
            $Resolver = User::format_username($ResolverID, true, true, true, true);
        } else {
            $Resolver = '(unresolved)';
        } ?>
      <tr>
        <td><a
            href="staffpm.php?action=viewconv&amp;id=<?=$ID?>"><?=Text::esc($Subject)?></a></td>
        <td><?=time_diff($Date, 2, true)?>
        </td>
        <td><?=$Assigned?>
        </td>
        <td><?=$Resolver?>
        </td>
      </tr>
      <?php
    } ?>
    </table>
  </div>
  <?php
    }
}

// Displays a table of forum warnings viewable only to Forum Moderators
if ($user['Class'] == 650 && check_perms('users_warn', $Class)) {
    $app->dbOld->query("
    SELECT Comment
    FROM users_warnings_forums
    WHERE UserID = '$userId'");
    list($ForumWarnings) = $app->dbOld->next_record();
    if ($app->dbOld->has_results()) {
        ?>
  <div class="box">
    <div class="head">Forum warnings</div>
    <div class="pad">
      <div id="forumwarningslinks" class="AdminComment" style="width: 98%;"><?=Text::parse($ForumWarnings)?>
      </div>
    </div>
  </div>
  <?php
    }
}
if (check_perms('users_mod', $Class)) { ?>
  <form class="manage_form" name="user" id="form" action="user.php" method="post">
    <input type="hidden" name="action" value="moderate" />
    <input type="hidden" name="userid" value="<?=$userId?>" />
    <input type="hidden" name="auth"
      value="<?=$user['AuthKey']?>" />

    <div class="box" id="staff_notes_box">
      <div class="head">
        Staff Notes
        <a href="#" name="admincommentbutton" class="brackets">Edit</a>
        <span class="u-pull-right">
          <a data-toggle-target="#staffnotes" class="brackets">Toggle</a>
        </span>
      </div>
      <div id="staffnotes" class="pad">
        <input type="hidden" name="comment_hash"
          value="<?=$CommentHash?>" />
        <div id="admincommentlinks" class="AdminComment" style="width: 98%;"><?=Text::parse($AdminComment)?>
        </div>
        <textarea id="admincomment" onkeyup="resize('admincomment');" class="AdminComment hidden" name="AdminComment"
          cols="65" rows="26"
          style="width: 98%;"><?=Text::esc($AdminComment)?></textarea>
        <a href="#" name="admincommentbutton" class="brackets">Toggle
          edit</a>
        <script type="text/javascript">
          resize('admincomment');
        </script>
      </div>
    </div>

    <table class="box skeletonFix" id="user_info_box">
      <tr class="colhead">
        <td colspan="2">
          User Information
        </td>
      </tr>
      <?php if (check_perms('users_edit_usernames', $Class)) { ?>
      <tr>
        <td class="label">Username:</td>
        <td><input type="text" size="20" name="Username"
            value="<?=Text::esc($Username)?>" /></td>
      </tr>
      <?php
      }
  if (check_perms('users_edit_titles')) {
      ?>
      <tr>
        <td class="label">Custom title:</td>
        <td><input type="text" class="wide_input_text" name="Title"
            value="<?=Text::esc($CustomTitle)?>" /></td>
      </tr>
      <?php
  }

  if (check_perms('users_promote_below', $Class) || check_perms('users_promote_to', $Class - 1)) {
      ?>
      <tr>
        <td class="label">Primary class:</td>
        <td>
          <select name="Class">
            <?php
    foreach ($ClassLevels as $CurClass) {
        if (check_perms('users_promote_below', $Class) && $CurClass['ID'] >= $user['EffectiveClass']) {
            break;
        }

        if ($CurClass['ID'] > $user['EffectiveClass']) {
            break;
        }

        if ($CurClass['Secondary']) {
            continue;
        }

        if ($Class == $CurClass['Level']) {
            $Selected = ' selected="selected"';
        } else {
            $Selected = '';
        } ?>

            <!--
                pcs-comment-start bug
                php-cs-fixer misinterpretation
              -->
            <option value="<?=$CurClass['ID']?>"
              <?=$Selected?>><?=$CurClass['Name'].' ('.$CurClass['Level'].')'?>
            </option>
            <?php
    } ?>
          </select>
        </td>
      </tr>
      <?php
  }

  if (check_perms('users_give_donor')) {
      ?>
      <tr>
        <td class="label">Donor:</td>
        <td><input type="checkbox" name="Donor" <?php if ($Donor==1) { ?> checked="checked"
          <?php } ?> />
        </td>
      </tr>
      <?php
  }
  if (check_perms('users_promote_below') || check_perms('users_promote_to')) { ?>
      <tr>
        <td class="label">Secondary classes:</td>
        <td>
          <?php
    $app->dbOld->query("
      SELECT p.ID, p.Name, l.UserID
      FROM permissions AS p
        LEFT JOIN users_levels AS l ON l.PermissionID = p.ID AND l.UserID = '$userId'
      WHERE p.Secondary = 1
      ORDER BY p.Name");
      $i = 0;
      while (list($PermID, $PermName, $IsSet) = $app->dbOld->next_record()) {
          $i++; ?>
          <input type="checkbox" id="perm_<?=$PermID?>"
            name="secondary_classes[]" value="<?=$PermID?>" <?php if ($IsSet) { ?> checked="checked"
          <?php } ?> />&nbsp;<label
            for="perm_<?=$PermID?>"
            style="margin-right: 10px;"><?=$PermName?></label>
          <?php if ($i % 3 == 0) {
              echo "\t\t\t\t<br />\n";
          }
      } ?>
        </td>
      </tr>
      <?php }
  if (check_perms('users_make_invisible')) {
      ?>
      <tr>
        <td class="label">Visible in peer lists:</td>
        <td><input type="checkbox" name="Visible" <?php if ($Visible==1) { ?> checked="checked"
          <?php } ?> />
        </td>
      </tr>
      <?php
  }

  if (check_perms('users_edit_ratio', $Class) || (check_perms('users_edit_own_ratio') && $userId == $user['ID'])) {
      ?>
      <tr>
        <td class="label tooltip" title="Upload amount in bytes. Also accepts e.g. +20GB or -35.6364MB on the end.">
          Uploaded:</td>
        <td>
          <input type="hidden" name="OldUploaded"
            value="<?=$Uploaded?>" />
          <input type="text" size="20" name="Uploaded"
            value="<?=$Uploaded?>" />
        </td>
      </tr>
      <tr>
        <td class="label tooltip" title="Download amount in bytes. Also accepts e.g. +20GB or -35.6364MB on the end.">
          Downloaded:</td>
        <td>
          <input type="hidden" name="OldDownloaded"
            value="<?=$Downloaded?>" />
          <input type="text" size="20" name="Downloaded"
            value="<?=$Downloaded?>" />
        </td>
      </tr>
      <tr>
        <td class="label"><?=bonusPoints?>:</td>
        <td>
          <input type="text" size="20" name="BonusPoints"
            value="<?=$BonusPoints?>" />
          <?php
if (!$DisablePoints) {
    $PointsRate = 0;
    $getTorrents = $app->dbOld->query("
    SELECT COUNT(DISTINCT x.fid) AS Torrents,
           SUM(t.Size) AS Size,
           SUM(xs.seedtime) AS Seedtime,
           SUM(t.Seeders) AS Seeders
    FROM users_main AS um
    LEFT JOIN users_info AS i on um.ID = i.UserID
    LEFT JOIN xbt_files_users AS x ON um.ID=x.uid
    LEFT JOIN torrents AS t ON t.ID=x.fid
    LEFT JOIN xbt_snatched AS xs ON x.uid=xs.uid AND x.fid=xs.fid
    WHERE
      um.ID = $userId
      AND um.Enabled = '1'
      AND x.active = 1
      AND x.completed = 0
      AND x.Remaining = 0
    GROUP BY um.ID");
    if ($app->dbOld->has_results()) {
        list($NumTorr, $TSize, $TTime, $TSeeds) = $app->dbOld->next_record();

        $ENV = ENV::go();
        $PointsRate = ($ENV->bonusPointsCoefficient + (0.55*($NumTorr * (sqrt(($TSize/$NumTorr)/1073741824) * pow(1.5, ($TTime/$NumTorr)/(24*365))))) / (max(1, sqrt(($TSeeds/$NumTorr)+4)/3)))**0.95;
    }

    $PointsRate = intval(max(min($PointsRate, ($PointsRate * 2) - ($BonusPoints/1440)), 0));
    $PointsPerHour = Text::float($PointsRate)." ".bonusPoints."/hour";
    $PointsPerDay = Text::float($PointsRate*24)." ".bonusPoints."/day";
} else {
    $PointsPerHour = "0 ".bonusPoints."/hour";
    $PointsPerDay = bonusPoints." disabled";
} ?>
          <?=$PointsPerHour?> (<?=$PointsPerDay?>)
        </td>
      </tr>
      <tr>
        <td class="label tooltip" title="Enter a username.">Merge stats <strong>from:</strong></td>
        <td>
          <input type="text" size="40" name="MergeStatsFrom" />
        </td>
      </tr>
      <tr>
        <td class="label">Freeleech tokens:</td>
        <td>
          <input type="text" size="5" name="FLTokens"
            value="<?=$FLTokens?>" />
        </td>
      </tr>
      <?php
  }

  if (check_perms('users_edit_invites')) {
      ?>
      <tr>
        <td class="label tooltip" title="Number of invites">Invites:</td>
        <td><input type="text" size="5" name="Invites"
            value="<?=$Invites?>" /></td>
      </tr>
      <?php
  }

  if (check_perms('admin_manage_fls') || (check_perms('users_mod') && $isOwnProfile)) {
      ?>
      <tr>
        <td class="label tooltip" title="This is the message shown in the right-hand column on /staff.php">FLS/Staff
          remark:</td>
        <td><input type="text" class="wide_input_text" name="SupportFor"
            value="<?=Text::esc($SupportFor)?>" /></td>
      </tr>
      <?php
  }

  if (check_perms('users_edit_reset_keys')) {
      ?>
      <tr>
        <td class="label">Reset:</td>
        <td>
          <input type="checkbox" name="ResetRatioWatch" id="ResetRatioWatch" /> <label for="ResetRatioWatch">Ratio
            watch</label> |
          <input type="checkbox" name="ResetPasskey" id="ResetPasskey" /> <label for="ResetPasskey">Passkey</label> |
          <input type="checkbox" name="ResetAuthkey" id="ResetAuthkey" /> <label for="ResetAuthkey">Authkey</label> |
          <br />
          <input type="checkbox" name="ResetSnatchList" id="ResetSnatchList" /> <label for="ResetSnatchList">Snatch
            list</label> |
          <input type="checkbox" name="ResetDownloadList" id="ResetDownloadList" /> <label
            for="ResetDownloadList">Download list</label>
        </td>
      </tr>
      <?php
  }

  if (check_perms('users_edit_password')) {
      ?>
      <tr>
        <td class="label">New password:</td>
        <td>
          <textarea id="password_display" name="password_display" rows="2" cols="50" onclick="this.select();"
            readonly></textarea>
          <button type="button" id="password_create" onclick="pwgen('password_display');">Generate</button>
        </td>
      </tr>
      <?php
  }

    if (check_perms('users_edit_badges')) {
        ?>
      <tr id="user_badge_edit_tr">
        <td class="label">Badges Owned:</td>
        <td>
          <?php
    $AllBadges = Badges::getAllBadges();
        $UserBadgeIDs = [];
        foreach (array_keys(Badges::getBadges($userId)) as $b) {
            $UserBadgeIDs[] = $b;
        }
        $i = 0;
        foreach (array_keys($AllBadges) as $BadgeID) {
            ?><input type="checkbox" name="badges[]" class="badge_checkbox"
            value="<?=$BadgeID?>" <?=(in_array($BadgeID, $UserBadgeIDs)) ? " checked" : ""?>/><?=Badges::displayBadge($BadgeID, true)?>
          <?php $i++;
            if ($i % 8 == 0) {
                echo "<br />";
            }
        } ?>
        </td>
      </tr>
      <?php
    } ?>
    </table>

    <?php if (check_perms('users_warn')) { ?>
    <table class="box skeletonFix" id="warn_user_box">
      <tr class="colhead">
        <td colspan="2">
          Warnings
        </td>
      </tr>
      <tr>
        <td class="label">Warned:</td>
        <td>
          <input type="checkbox" name="Warned" <?php if ($Warned) { ?> checked="checked"
          <?php } ?> />
        </td>
      </tr>
      <?php if (!$Warned) { ?>
      <tr>
        <td class="label">Expiration:</td>
        <td>
          <select name="WarnLength">
            <option value="">---</option>
            <option value="1">1 week</option>
            <option value="2">2 weeks</option>
            <option value="4">4 weeks</option>
            <option value="8">8 weeks</option>
          </select>
        </td>
      </tr>
      <?php } else { ?>
      <tr>
        <td class="label">Extension:</td>
        <td>
          <select name="ExtendWarning" onchange="ToggleWarningAdjust(this);">
            <option>---</option>
            <option value="1">1 week</option>
            <option value="2">2 weeks</option>
            <option value="4">4 weeks</option>
            <option value="8">8 weeks</option>
          </select>
        </td>
      </tr>
      <tr id="ReduceWarningTR">
        <td class="label">Reduction:</td>
        <td>
          <select name="ReduceWarning">
            <option>---</option>
            <option value="1">1 week</option>
            <option value="2">2 weeks</option>
            <option value="4">4 weeks</option>
            <option value="8">8 weeks</option>
          </select>
        </td>
      </tr>
      <?php } ?>
      <tr>
        <td class="label tooltip" title="This message *will* be sent to the user in the warning PM!">Warning reason:
        </td>
        <td>
          <input type="text" class="wide_input_text" name="WarnReason" />
        </td>
      </tr>
      <?php } ?>
    </table>
    <?php if (check_perms('users_disable_any')) { ?>
    <table class="box skeletonFix" id="user_lock_account">
      <tr class="colhead">
        <td colspan="2">
          Lock Account
        </td>
      </tr>
      <tr>
        <td class="label">Lock Account:</td>
        <td>
          <input type="checkbox" name="LockAccount" id="LockAccount" <?php if ($LockedAccount) { ?> checked="checked"
          <?php } ?>/>
        </td>
      </tr>
      <tr>
        <td class="label">Reason:</td>
        <td>
          <select name="LockReason">
            <option value="---">---</option>
            <option value="<?=STAFF_LOCKED?>" <?php if ($LockedAccount==STAFF_LOCKED) { ?> selected
              <?php } ?>>Staff Lock
            </option>
          </select>
        </td>
      </tr>
    </table>
    <?php }  ?>
    <table class="box skeletonFix" id="user_privs_box">
      <tr class="colhead">
        <td colspan="2">
          User Privileges
        </td>
      </tr>
      <?php if (check_perms('users_disable_posts') || check_perms('users_disable_any')) {
          ?>
      <tr>
        <td class="label">Disable:</td>
        <td>
          <input type="checkbox" name="DisablePosting" id="DisablePosting" <?php if ($DisablePosting==1) { ?>
          checked="checked"
          <?php } ?> /> <label for="DisablePosting">Posting</label>
          <?php if (check_perms('users_disable_any')) { ?>
          |
          <input type="checkbox" name="DisableAvatar" id="DisableAvatar" <?php if ($DisableAvatar==1) { ?>
          checked="checked"
          <?php } ?> /> <label for="DisableAvatar">Avatar</label> |
          <input type="checkbox" name="DisableForums" id="DisableForums" <?php if ($DisableForums==1) { ?>
          checked="checked"
          <?php } ?> /> <label for="DisableForums">Forums</label> |
          <input type="checkbox" name="DisableIRC" id="DisableIRC" <?php if ($DisableIRC==1) { ?> checked="checked"
          <?php } ?> /> <label for="DisableIRC">IRC</label> |
          <input type="checkbox" name="DisablePM" id="DisablePM" <?php if ($DisablePM==1) { ?> checked="checked"
          <?php } ?> /> <label for="DisablePM">PM</label> |
          <br /><br />

          <input type="checkbox" name="DisableLeech" id="DisableLeech" <?php if ($DisableLeech==0) { ?> checked="checked"
          <?php } ?> /> <label for="DisableLeech">Leech</label> |
          <input type="checkbox" name="DisableRequests" id="DisableRequests" <?php if ($DisableRequests==1) { ?>
          checked="checked"
          <?php } ?> /> <label for="DisableRequests">Requests</label>
          |
          <input type="checkbox" name="DisableUpload" id="DisableUpload" <?php if ($DisableUpload==1) { ?>
          checked="checked"
          <?php } ?> /> <label for="DisableUpload">Torrent
            upload</label> |
          <input type="checkbox" name="DisablePoints" id="DisablePoints" <?php if ($DisablePoints==1) { ?>
          checked="checked"
          <?php } ?> /> <label for="DisablePoints"><?=bonusPoints?></label>
          <br /><br />

          <input type="checkbox" name="DisableTagging" id="DisableTagging" <?php if ($DisableTagging==1) { ?>
          checked="checked"
          <?php } ?> /> <label for="DisableTagging" class="tooltip"
            title="This only disables a user's ability to delete tags.">Tagging</label> |
          <input type="checkbox" name="DisableWiki" id="DisableWiki" <?php if ($DisableWiki==1) { ?> checked="checked"
          <?php } ?> /> <label for="DisableWiki">Wiki</label> |
          <input type="checkbox" name="DisablePromotion" id="DisablePromotion" <?php if ($DisablePromotion==1) { ?>
          checked="checked"
          <?php } ?> /> <label
            for="DisablePromotion">Promotions</label> |
          <input type="checkbox" name="DisableInvites" id="DisableInvites" <?php if ($DisableInvites==1) { ?>
          checked="checked"
          <?php } ?> /> <label for="DisableInvites">Invites</label>
        </td>
      </tr>
      <tr>
        <td class="label">Hacked:</td>
        <td>
          <input type="checkbox" name="SendHackedMail" id="SendHackedMail" />
          <label for="SendHackedMail">Send hacked account email</label>
        </td>
      </tr>

      <?php
          }
      }

  if (check_perms('users_disable_any')) {
      ?>
      <tr>
        <td class="label">Account:</td>
        <td>
          <select name="UserStatus">
            <option value="0" <?php if ($Enabled=='0') { ?>
              selected="selected"
              <?php } ?>>Unconfirmed
            </option>
            <option value="1" <?php if ($Enabled=='1') { ?>
              selected="selected"
              <?php } ?>>Enabled
            </option>
            <option value="2" <?php if ($Enabled=='2') { ?>
              selected="selected"
              <?php } ?>>Disabled
            </option>
            <?php if (check_perms('users_delete_users')) { ?>
            <optgroup label="-- WARNING --">
              <option value="delete">Delete account</option>
            </optgroup>
            <?php } ?>
          </select>
        </td>
      </tr>
      <tr>
        <td class="label">User reason:</td>
        <td>
          <input type="text" class="wide_input_text" name="UserReason" />
        </td>
      </tr>
      <tr>
        <td class="label tooltip" title="Enter a comma-delimited list of forum IDs.">Restricted forums:</td>
        <td>
          <input type="text" class="wide_input_text" name="RestrictedForums"
            value="<?=Text::esc($RestrictedForums)?>" />
        </td>
      </tr>
      <tr>
        <td class="label tooltip" title="Enter a comma-delimited list of forum IDs.">Extra forums:</td>
        <td>
          <input type="text" class="wide_input_text" name="PermittedForums"
            value="<?=Text::esc($PermittedForums)?>" />
        </td>
      </tr>

      <?php
  } ?>
    </table>
    <?php if (check_perms('users_logout')) { ?>
    <table class="box skeletonFix" id="session_box">
      <tr class="colhead">
        <td colspan="2">
          Session
        </td>
      </tr>
      <tr>
        <td class="label">Reset session:</td>
        <td><input type="checkbox" name="ResetSession" id="ResetSession" /></td>
      </tr>
      <tr>
        <td class="label">Log out:</td>
        <td><input type="checkbox" name="LogOut" id="LogOut" /></td>
      </tr>
    </table>
    <?php
    }
    ?>
    <table class="box skeletonFix" id="submit_box">
      <tr class="colhead">
        <td colspan="2">
          Submit
        </td>
      </tr>
      <tr>
        <td class="label tooltip" title="This message will be entered into staff notes only.">Reason:</td>
        <td>
          <textarea rows="2" class="wide_input_text" name="Reason" id="Reason" onkeyup="resize('Reason');"></textarea>
        </td>
      </tr>

      <tr>
        <td align="right" colspan="2">
          <input type="submit" class="button-primary" value="Save changes" />
        </td>
      </tr>
    </table>
  </form>
  <?php
}
?>
</div>
</div>
<?php View::footer();
