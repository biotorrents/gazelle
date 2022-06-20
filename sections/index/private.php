<?php
#declare(strict_types=1);

$ENV = ENV::go();
$NewsCount = 2;

# fix for flight
$cache = new Cache($ENV->getPriv('MEMCACHED_SERVERS'));
$user = G::$user;
$db = new DB;

if (!$News = $cache->get_value('news')) {
    $db->query("
    SELECT
      `ID`,
      `Title`,
      `Body`,
      `Time`
    FROM `news`
    ORDER BY `Time` DESC
    LIMIT $NewsCount
    ");
    
    $News = $db->to_array(false, MYSQLI_NUM, false);
    $cache->cache_value('news', $News, 3600 * 24 * 30);
    $cache->cache_value('news_latest_id', $News[0][0], 0);
    $cache->cache_value('news_latest_title', $News[0][1], 0);
}

$userId = intval($user['ID']);
#!d($user);exit;

if ($user['LastReadNews'] !== $News[0][0] && count($News) > 0) {
    $cache->begin_transaction("user_info_heavy_{$userId}");
    $cache->update_row(false, array('LastReadNews' => $News[0][0]));
    $cache->commit_transaction(0);
    $db->query("
    UPDATE users_info
    SET LastReadNews = '".$News[0][0]."'
    WHERE UserID = $userId");
    $user['LastReadNews'] = $News[0][0];
}

View::header('News', 'news_ajax');
?>

<div class="sidebar one-third column">
  <?php #include 'connect.php';?>

  <div class="box">
    <div class="head colhead_dark"><strong><a href="blog.php">Latest blog posts</a></strong></div>
    <?php
if (($Blog = $cache->get_value('blog')) === false) {
    $db->query("
    SELECT
      b.ID,
      um.Username,
      b.UserID,
      b.Title,
      b.Body,
      b.Time,
      b.ThreadID
    FROM blog AS b
      LEFT JOIN users_main AS um ON b.UserID = um.ID
    ORDER BY Time DESC
    LIMIT 20");
    $Blog = $db->to_array();
    $cache->cache_value('blog', $Blog, 1209600);
}
?>
    <ul class="stats nobullet">
      <?php
if (count($Blog) < 5) {
    $Limit = count($Blog);
} else {
    $Limit = 5;
}
for ($i = 0; $i < $Limit; $i++) {
    list($BlogID, $Author, $AuthorID, $Title, $Body, $BlogTime, $ThreadID) = $Blog[$i]; ?>
      <li>
        <?=($i + 1)?>. <a
          href="blog.php#blog<?=$BlogID?>"><?=$Title?></a>
      </li>
      <?php
}
?>
    </ul>
  </div>
  <?php
if (($Freeleeches = $cache->get_value('shop_freeleech_list')) === false) {
    $db->query("
    SELECT
      `TorrentID`,
      UNIX_TIMESTAMP(`ExpiryTime`),
      COALESCE(
        NULLIF(`title`, ''),
        NULLIF(`subject`, ''),
        `object`
      ) AS `Name`,
      `picture`
    FROM
      `shop_freeleeches` AS sf
    LEFT JOIN `torrents` AS t
    ON
      sf.`TorrentID` = t.`ID`
    LEFT JOIN `torrents_group` AS tg
    ON
      tg.`id` = t.`GroupID`
    ORDER BY
      `ExpiryTime` ASC
    LIMIT 10
    ");
    $Freeleeches = $db->to_array();
    $cache->cache_value('shop_freeleech_list', $Freeleeches, 1209600);
}
if (count($Freeleeches)) {
    ?>
  <div class="box">
    <div class="head colhead_dark"><strong><a
          href="torrents.php?freetorrent=1&order_by=seeders&order_way=asc">Freeleeches</a></strong></div>
    <ul class="stats nobullet">
      <?php
  for ($i = 0; $i < count($Freeleeches); $i++) {
      list($ID, $ExpiryTime, $Name, $Image) = $Freeleeches[$i];
      if ($ExpiryTime < time()) {
          continue;
      }
      $DisplayTime = '('.str_replace(['year','month','week','day','hour','min','Just now','s',' '], ['y','M','w','d','h','m','0m'], time_diff($ExpiryTime, 1, false)).') ';
      $DisplayName = '<a href="torrents.php?torrentid='.$ID.'"';
      if (!isset($user['CoverArt']) || $user['CoverArt']) {
          $DisplayName .= ' data-cover="'.ImageTools::process($Image, 'thumb').'"';
      }
      $DisplayName .= '>'.$Name.'</a>'; ?>
      <li>
        <strong class="fl_time"><?=$DisplayTime?></strong>
        <?=$DisplayName?>
      </li>
      <?php
  } ?>
    </ul>
  </div>
  <?php
}
?>

  <!-- Stats -->
  <div class="box">
    <div class="head colhead_dark"><strong>Stats</strong></div>
    <ul class="stats nobullet">
      <?php if (USER_LIMIT > 0) { ?>
      <li>Maximum users: <?=Text::float(USER_LIMIT) ?>
      </li>
      <?php
}

if (($UserCount = $cache->get_value('stats_user_count')) === false) {
    $db->query("
    SELECT COUNT(ID)
    FROM users_main
    WHERE Enabled = '1'");
    list($UserCount) = $db->next_record();
    $cache->cache_value('stats_user_count', $UserCount, 86400);
}
$UserCount = (int)$UserCount;
?>
      <?php /*
      <li>
        Enabled users: <?=Text::float($UserCount)?>
      <a href="/stats/users" class="brackets">Details</a>
      </li>
      <?php

if (($UserStats = $cache->get_value('stats_users')) === false) {
    $db->query("
    SELECT COUNT(ID)
    FROM users_main
    WHERE Enabled = '1'
      AND LastAccess > '".time_minus(3600 * 24)."'");
    list($UserStats['Day']) = $db->next_record();

    $db->query("
    SELECT COUNT(ID)
    FROM users_main
    WHERE Enabled = '1'
      AND LastAccess > '".time_minus(3600 * 24 * 7)."'");
    list($UserStats['Week']) = $db->next_record();

    $db->query("
    SELECT COUNT(ID)
    FROM users_main
    WHERE Enabled = '1'
      AND LastAccess > '".time_minus(3600 * 24 * 30)."'");
    list($UserStats['Month']) = $db->next_record();

    $cache->cache_value('stats_users', $UserStats, 0);
}
?>
      <li>Users active today: <?=Text::float($UserStats['Day'])?>
        (<?=Text::float($UserStats['Day'] / $UserCount * 100, 2)?>%)
      </li>
      <li>Users active this week: <?=Text::float($UserStats['Week'])?>
        (<?=Text::float($UserStats['Week'] / $UserCount * 100, 2)?>%)
      </li>
      <li>Users active this month: <?=Text::float($UserStats['Month'])?>
        (<?=Text::float($UserStats['Month'] / $UserCount * 100, 2)?>%)
      </li>
      <?php

if (($TorrentCount = $cache->get_value('stats_torrent_count')) === false) {
    $db->query("
    SELECT COUNT(ID)
    FROM torrents");
    list($TorrentCount) = $db->next_record();
    $cache->cache_value('stats_torrent_count', $TorrentCount, 86400); // 1 day cache
}

if (($GroupCount = $cache->get_value('stats_group_count')) === false) {
    $db->query("
    SELECT COUNT(ID)
    FROM torrents_group");
    list($GroupCount) = $db->next_record();
    $cache->cache_value('stats_group_count', $GroupCount, 86400); // 1 day cache
}

if (($TorrentSizeTotal = $cache->get_value('stats_torrent_size_total')) === false) {
    $db->query("
    SELECT SUM(Size)
    FROM torrents");
    list($TorrentSizeTotal) = $db->next_record();
    $cache->cache_value('stats_torrent_size_total', $TorrentSizeTotal, 86400); // 1 day cache
}
?>
      <li>
        Total size of torrents:
        <?=Format::get_size($TorrentSizeTotal)?>
      </li>

      <?php
if (($ArtistCount = $cache->get_value('stats_artist_count')) === false) {
    $db->query("
    SELECT COUNT(ArtistID)
    FROM artists_group");
    list($ArtistCount) = $db->next_record();
    $cache->cache_value('stats_artist_count', $ArtistCount, 86400); // 1 day cache
}

?>
      <li>
        Torrents:
        <?=Text::float($TorrentCount)?>
        <a href="/stats/torrents" class="brackets">Details</a>
      </li>

      <li>Torrent Groups: <?=Text::float($GroupCount)?>
      </li>
      <li>Artists: <?=Text::float($ArtistCount)?>
      </li>
      <?php
// End Torrent Stats

if (($RequestStats = $cache->get_value('stats_requests')) === false) {
    $db->query("
    SELECT COUNT(ID)
    FROM requests");
    list($RequestCount) = $db->next_record();
    $db->query("
    SELECT COUNT(ID)
    FROM requests
    WHERE FillerID > 0");
    list($FilledCount) = $db->next_record();
    $cache->cache_value('stats_requests', array($RequestCount, $FilledCount), 11280);
} else {
    list($RequestCount, $FilledCount) = $RequestStats;
}

// Do not divide by zero
if ($RequestCount > 0) {
    $RequestsFilledPercent = $FilledCount / $RequestCount * 100;
} else {
    $RequestsFilledPercent = 0;
}

?>
      <li>Requests: <?=Text::float($RequestCount)?> (<?=Text::float($RequestsFilledPercent, 2)?>% filled)</li>
      <?php

if ($SnatchStats = $cache->get_value('stats_snatches')) {
    ?>
      <li>Snatches: <?=Text::float($SnatchStats)?>
      </li>
      <?php
}

if (($PeerStats = $cache->get_value('stats_peers')) === false) {
    // Cache lock!
    $PeerStatsLocked = $cache->get_value('stats_peers_lock');
    if (!$PeerStatsLocked) {
        $cache->cache_value('stats_peers_lock', 1, 30);
        $db->query("
      SELECT IF(remaining=0,'Seeding','Leeching') AS Type, COUNT(uid)
      FROM xbt_files_users
      WHERE active = 1
      GROUP BY Type");
        $PeerCount = $db->to_array(0, MYSQLI_NUM, false);
        $SeederCount = $PeerCount['Seeding'][1] ?: 0;
        $LeecherCount = $PeerCount['Leeching'][1] ?: 0;
        $cache->cache_value('stats_peers', array($LeecherCount, $SeederCount), 604800); // 1 week cache
        $cache->delete_value('stats_peers_lock');
    }
} else {
    $PeerStatsLocked = false;
    list($LeecherCount, $SeederCount) = $PeerStats;
}

if (!$PeerStatsLocked) {
    $Ratio = Format::get_ratio_html($SeederCount, $LeecherCount);
    $PeerCount = Text::float($SeederCount + $LeecherCount);
    $SeederCount = Text::float($SeederCount);
    $LeecherCount = Text::float($LeecherCount);
} else {
    $PeerCount = $SeederCount = $LeecherCount = $Ratio = 'Server busy';
}
?>
      <li>Peers: <?=$PeerCount?>
      </li>
      <li>Seeders: <?=$SeederCount?>
      </li>
      <li>Leechers: <?=$LeecherCount?>
      </li>
      <li>Seeder/leecher ratio: <?=$Ratio?>
      </li>
    </ul>
  </div>
  */?>
  <!-- Polls -->
  <?php
if (($TopicID = $cache->get_value('polls_featured')) === false) {
    $db->query("
    SELECT TopicID
    FROM forums_polls
    ORDER BY Featured DESC
    LIMIT 1");
    list($TopicID) = $db->next_record();
    $cache->cache_value('polls_featured', $TopicID, 0);
}
if ($TopicID) {
    if (($Poll = $cache->get_value("polls_$TopicID")) === false) {
        $db->query("
      SELECT Question, Answers, Featured, Closed
      FROM forums_polls
      WHERE TopicID = '$TopicID'");
        list($Question, $Answers, $Featured, $Closed) = $db->next_record(MYSQLI_NUM, array(1));
        $Answers = unserialize($Answers);
        $db->query("
      SELECT Vote, COUNT(UserID)
      FROM forums_polls_votes
      WHERE TopicID = '$TopicID'
        AND Vote != '0'
      GROUP BY Vote");
        $VoteArray = $db->to_array(false, MYSQLI_NUM);

        $Votes = [];
        foreach ($VoteArray as $VoteSet) {
            list($Key, $Value) = $VoteSet;
            $Votes[$Key] = $Value;
        }

        for ($i = 1, $il = count($Answers); $i <= $il; ++$i) {
            if (!isset($Votes[$i])) {
                $Votes[$i] = 0;
            }
        }
        $cache->cache_value("polls_$TopicID", array($Question, $Answers, $Votes, $Featured, $Closed), 0);
    } else {
        list($Question, $Answers, $Votes, $Featured, $Closed) = $Poll;
    }

    if (!empty($Votes)) {
        $TotalVotes = array_sum($Votes);
        $MaxVotes = max($Votes);
    } else {
        $TotalVotes = 0;
        $MaxVotes = 0;
    }

    $db->query("
    SELECT Vote
    FROM forums_polls_votes
    WHERE UserID = '".$user['ID']."'
      AND TopicID = '$TopicID'");
    list($UserResponse) = $db->next_record(); ?>

  <div class="box">
    <div class="head colhead_dark"><strong>Poll<?php if ($Closed) {
        echo ' [Closed]';
    } ?>
      </strong>
    </div>
    <div class="pad">
      <p><strong><?=Text::esc($Question)?></strong></p>
      <?php if ($UserResponse !== null || $Closed) { ?>
      <ul class="poll nobullet">
        <?php foreach ($Answers as $i => $Answer) {
        if ($TotalVotes > 0) {
            $Ratio = $Votes[$i] / $MaxVotes;
            $Percent = $Votes[$i] / $TotalVotes;
        } else {
            $Ratio = 0;
            $Percent = 0;
        } ?>
        <li<?=((!empty($UserResponse) && ($UserResponse == $i))?' class="poll_your_answer"':'')?>><?=Text::esc($Answers[$i])?> (<?=Text::float($Percent * 100, 2)?>%)</li>
          <li class="graph">
            <span class="center_poll"
              style="width: <?=round($Ratio * 140)?>px;"></span>
            <br />
          </li>
          <?php
    } ?>
      </ul>
      <strong>Votes:</strong> <?=Text::float($TotalVotes)?><br />
      <?php } else { ?>
      <div id="poll_container">
        <form class="vote_form" name="poll" id="poll" action="">
          <input type="hidden" name="action" value="poll" />
          <input type="hidden" name="auth"
            value="<?=$user['AuthKey']?>" />
          <input type="hidden" name="topicid"
            value="<?=$TopicID?>" />
          <?php foreach ($Answers as $i => $Answer) { ?>
          <input type="radio" name="vote" id="answer_<?=$i?>"
            value="<?=$i?>" />
          <label for="answer_<?=$i?>"><?=Text::esc($Answers[$i])?></label><br />
          <?php } ?>
          <br /><input type="radio" name="vote" id="answer_0" value="0" /> <label
            for="answer_0">Blank&#8202;&mdash;&#8202;Show the results!</label><br /><br />
          <input type="button"
            onclick="ajax.post('index.php', 'poll', function(response) { $('#poll_container').raw().innerHTML = response } );"
            value="Vote" />
        </form>
      </div>
      <?php } ?>
      <br /><strong>Topic:</strong> <a
        href="forums.php?action=viewthread&amp;threadid=<?=$TopicID?>">Visit</a>
    </div>
  </div>
  <?php
}
// polls();
?>
</div>
</div>
<div class="main_column two-thirds column">
  <?php

$Recommend = $cache->get_value('recommend');
$Recommend_artists = $cache->get_value('recommend_artists');

if (!is_array($Recommend) || !is_array($Recommend_artists)) {
    $db->query("
    SELECT
      tr.`GroupID`,
      tr.`UserID`,
      u.`Username`,
      tg.`title`,
      tg.`tag_list`
    FROM
      `torrents_recommended` AS tr
    JOIN `torrents_group` AS tg
    ON
      tg.`id` = tr.`GroupID`
    LEFT JOIN `users_main` AS u
    ON
      u.`ID` = tr.`UserID`
    ORDER BY
      tr.`Time`
    DESC
    LIMIT 10
    ");
    
    $Recommend = $db->to_array();
    $cache->cache_value('recommend', $Recommend, 1209600);

    $Recommend_artists = Artists::get_artists($db->collect('GroupID'));
    $cache->cache_value('recommend_artists', $Recommend_artists, 1209600);
}

if (count($Recommend) >= 4) {
    $cache->increment('usage_index'); ?>
  <div class="box" id="recommended">
    <div class="head colhead_dark">
      <strong>Latest Vanity House additions</strong>
      <a data-toggle-target="#vanityhouse" , data-toggle-replace="Hide" class="brackets">Show</a>
    </div>

    <table class="torrent_table hidden" id="vanityhouse">
      <?php
  foreach ($Recommend as $Recommendations) {
      list($GroupID, $userId, $Username, $GroupName, $TagList) = $Recommendations;
      $TagsStr = '';
      if ($TagList) {
          // No vanity.house tag.
          $Tags = explode(' ', str_replace('_', '.', $TagList));
          $TagLinks = [];
          foreach ($Tags as $Tag) {
              if ($Tag == 'vanity.house') {
                  continue;
              }
              $TagLinks[] = "<a href=\"torrents.php?action=basic&amp;taglist=$Tag\">$Tag</a> ";
          }
          $TagStr = "<br />\n<div class=\"tags\">".implode(', ', $TagLinks).'</div>';
      } ?>
      <tr>
        <td>
          <?=Artists::display_artists($Recommend_artists[$GroupID]) ?>
          <a href="torrents.php?id=<?=$GroupID?>"><?=$GroupName?></a> (by <?=Users::format_username($userId, false, false, false)?>)
          <?=$TagStr?>
        </td>
      </tr>
      <?php
  } ?>
    </table>
  </div>
  <!-- END recommendations section -->
  <?php
}

$Count = 0;
foreach ($News as $NewsItem) {
    list($NewsID, $Title, $Body, $NewsTime) = $NewsItem;
    if (strtotime($NewsTime) > time()) {
        continue;
    } ?>
  <div id="news<?=$NewsID?>" class="box news_post">
    <div class="head">
      <strong>
        <?=$Title?>
      </strong>

      <?=time_diff($NewsTime)?>

      <?php if (check_perms('admin_manage_news')) { ?>
      &ndash;
      <a href="tools.php?action=editnews&amp;id=<?=$NewsID?>"
        class="brackets">Edit</a>
      <?php } ?>

      <span class="u-pull-right">
        <a data-toggle-target="#newsbody<?=$NewsID?>"
          data-toggle-replace="Show" class="brackets">Hide</a>
      </span>
    </div>

    <div id="newsbody<?=$NewsID?>" class="pad">
      <?=Text::parse($Body)?>
    </div>
  </div>

  <?php
  if (++$Count > ($NewsCount - 1)) {
      break;
  }
}
?>
  <div id="more_news" class="box">
    <div class="head">
      <em><span><a href="#"
            onclick="news_ajax(event, 3, <?=$NewsCount?>, <?=check_perms('admin_manage_news') ? 1 : 0; ?>); return false;">Click
            to load more news</a>.</span> To browse old news posts, <a
          href="forums.php?action=viewforum&amp;forumid=<?=$ENV->ANNOUNCEMENT_FORUM?>">click
          here</a>.</em>
    </div>
  </div>
</div>
</div>
<?php
View::footer(array('disclaimer'=>true));
