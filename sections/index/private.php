<?php
#declare(strict_types=1);

$ENV = ENV::go();
Text::$TOC = true;
$NewsCount = 1;

if (!$News = $Cache->get_value('news')) {
    $DB->query("
    SELECT
      `ID`,
      `Title`,
      `Body`,
      `Time`
    FROM `news`
    ORDER BY `Time` DESC
    LIMIT $NewsCount
    ");
    
    $News = $DB->to_array(false, MYSQLI_NUM, false);
    $Cache->cache_value('news', $News, 3600 * 24 * 30);
    $Cache->cache_value('news_latest_id', $News[0][0], 0);
    $Cache->cache_value('news_latest_title', $News[0][1], 0);
}

if ($LoggedUser['LastReadNews'] !== $News[0][0] && count($News) > 0) {
    $Cache->begin_transaction("user_info_heavy_$UserID");
    $Cache->update_row(false, array('LastReadNews' => $News[0][0]));
    $Cache->commit_transaction(0);
    $DB->query("
    UPDATE users_info
    SET LastReadNews = '".$News[0][0]."'
    WHERE UserID = $UserID");
    $LoggedUser['LastReadNews'] = $News[0][0];
}

View::show_header('News', 'bbcode,news_ajax');
?>
<div>
  <div class="sidebar">
    <?php #include 'connect.php'; ?>

    <?php
    # Staff blog
if (check_perms('users_mod')) { ?>
    <div class="box">
      <div class="head colhead_dark">
        <strong><a href="staffblog.php">Latest staff blog posts</a></strong>
      </div>
      <?php
if (($Blog = $Cache->get_value('staff_blog')) === false) {
        $DB->query("
    SELECT
      b.ID,
      um.Username,
      b.Title,
      b.Body,
      b.Time
    FROM staff_blog AS b
      LEFT JOIN users_main AS um ON b.UserID = um.ID
    ORDER BY Time DESC");
        $Blog = $DB->to_array(false, MYSQLI_NUM);
        $Cache->cache_value('staff_blog', $Blog, 1209600);
    }
    if (($SBlogReadTime = $Cache->get_value('staff_blog_read_'.$LoggedUser['ID'])) === false) {
        $DB->query("
    SELECT Time
    FROM staff_blog_visits
    WHERE UserID = ".$LoggedUser['ID']);
        if (list($SBlogReadTime) = $DB->next_record()) {
            $SBlogReadTime = strtotime($SBlogReadTime);
        } else {
            $SBlogReadTime = 0;
        }
        $Cache->cache_value('staff_blog_read_'.$LoggedUser['ID'], $SBlogReadTime, 1209600);
    } ?>
      <ul class="stats nobullet">
        <?php
$End = min(count($Blog), 5);
    for ($i = 0; $i < $End; $i++) {
        list($BlogID, $Author, $Title, $Body, $BlogTime) = $Blog[$i];
        $BlogTime = strtotime($BlogTime); ?>
        <li>
          <?=$SBlogReadTime < $BlogTime ? '<strong>' : ''?><?=($i + 1)?>.
          <a href="staffblog.php#blog<?=$BlogID?>"><?=$Title?></a>
          <?=$SBlogReadTime < $BlogTime ? '</strong>' : ''?>
        </li>
        <?php
    } ?>
      </ul>
    </div>
    <?php
} ?>
    <div class="box">
      <div class="head colhead_dark"><strong><a href="blog.php">Latest blog posts</a></strong></div>
      <?php
if (($Blog = $Cache->get_value('blog')) === false) {
        $DB->query("
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
        $Blog = $DB->to_array();
        $Cache->cache_value('blog', $Blog, 1209600);
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
if (($Freeleeches = $Cache->get_value('shop_freeleech_list')) === false) {
    $DB->query("
    SELECT
      TorrentID,
      UNIX_TIMESTAMP(ExpiryTime),
      COALESCE(NULLIF(Name,''), NULLIF(Title2,''), NameJP) AS Name,
      WikiImage
    FROM shop_freeleeches AS sf
    LEFT JOIN torrents AS t on sf.TorrentID=t.ID
    LEFT JOIN torrents_group AS tg ON tg.ID=t.GroupID
    ORDER BY ExpiryTime ASC
    LIMIT 10");
    $Freeleeches = $DB->to_array();
    $Cache->cache_value('shop_freeleech_list', $Freeleeches, 1209600);
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
      if (!isset($LoggedUser['CoverArt']) || $LoggedUser['CoverArt']) {
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
        <li>Maximum users: <?=number_format(USER_LIMIT) ?>
        </li>
        <?php
}

if (($UserCount = $Cache->get_value('stats_user_count')) === false) {
    $DB->query("
    SELECT COUNT(ID)
    FROM users_main
    WHERE Enabled = '1'");
    list($UserCount) = $DB->next_record();
    $Cache->cache_value('stats_user_count', $UserCount, 86400);
}
$UserCount = (int)$UserCount;
?>
        <li>
          Enabled users: <?=number_format($UserCount)?>
          <a href="stats.php?action=users" class="brackets">Details</a>
        </li>
        <?php

if (($UserStats = $Cache->get_value('stats_users')) === false) {
    $DB->query("
    SELECT COUNT(ID)
    FROM users_main
    WHERE Enabled = '1'
      AND LastAccess > '".time_minus(3600 * 24)."'");
    list($UserStats['Day']) = $DB->next_record();

    $DB->query("
    SELECT COUNT(ID)
    FROM users_main
    WHERE Enabled = '1'
      AND LastAccess > '".time_minus(3600 * 24 * 7)."'");
    list($UserStats['Week']) = $DB->next_record();

    $DB->query("
    SELECT COUNT(ID)
    FROM users_main
    WHERE Enabled = '1'
      AND LastAccess > '".time_minus(3600 * 24 * 30)."'");
    list($UserStats['Month']) = $DB->next_record();

    $Cache->cache_value('stats_users', $UserStats, 0);
}
?>
        <li>Users active today: <?=number_format($UserStats['Day'])?> (<?=number_format($UserStats['Day'] / $UserCount * 100, 2)?>%)
        </li>
        <li>Users active this week: <?=number_format($UserStats['Week'])?>
          (<?=number_format($UserStats['Week'] / $UserCount * 100, 2)?>%)
        </li>
        <li>Users active this month: <?=number_format($UserStats['Month'])?>
          (<?=number_format($UserStats['Month'] / $UserCount * 100, 2)?>%)
        </li>
        <?php

if (($TorrentCount = $Cache->get_value('stats_torrent_count')) === false) {
    $DB->query("
    SELECT COUNT(ID)
    FROM torrents");
    list($TorrentCount) = $DB->next_record();
    $Cache->cache_value('stats_torrent_count', $TorrentCount, 86400); // 1 day cache
}

if (($GroupCount = $Cache->get_value('stats_group_count')) === false) {
    $DB->query("
    SELECT COUNT(ID)
    FROM torrents_group");
    list($GroupCount) = $DB->next_record();
    $Cache->cache_value('stats_group_count', $GroupCount, 86400); // 1 day cache
}

if (($TorrentSizeTotal = $Cache->get_value('stats_torrent_size_total')) === false) {
    $DB->query("
    SELECT SUM(Size)
    FROM torrents");
    list($TorrentSizeTotal) = $DB->next_record();
    $Cache->cache_value('stats_torrent_size_total', $TorrentSizeTotal, 86400); // 1 day cache
}
?>
        <li>
          Total size of torrents:
          <?=Format::get_size($TorrentSizeTotal)?>
        </li>

        <?php
if (($ArtistCount = $Cache->get_value('stats_artist_count')) === false) {
    $DB->query("
    SELECT COUNT(ArtistID)
    FROM artists_group");
    list($ArtistCount) = $DB->next_record();
    $Cache->cache_value('stats_artist_count', $ArtistCount, 86400); // 1 day cache
}

?>
        <li>
          Torrents:
          <?=number_format($TorrentCount)?>
          <a href="stats.php?action=torrents" class="brackets">Details</a>
        </li>

        <li>Torrent Groups: <?=number_format($GroupCount)?>
        </li>
        <li>Artists: <?=number_format($ArtistCount)?>
        </li>
        <?php
// End Torrent Stats

if (($RequestStats = $Cache->get_value('stats_requests')) === false) {
    $DB->query("
    SELECT COUNT(ID)
    FROM requests");
    list($RequestCount) = $DB->next_record();
    $DB->query("
    SELECT COUNT(ID)
    FROM requests
    WHERE FillerID > 0");
    list($FilledCount) = $DB->next_record();
    $Cache->cache_value('stats_requests', array($RequestCount, $FilledCount), 11280);
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
        <li>Requests: <?=number_format($RequestCount)?> (<?=number_format($RequestsFilledPercent, 2)?>% filled)</li>
        <?php

if ($SnatchStats = $Cache->get_value('stats_snatches')) {
    ?>
        <li>Snatches: <?=number_format($SnatchStats)?>
        </li>
        <?php
}

if (($PeerStats = $Cache->get_value('stats_peers')) === false) {
    // Cache lock!
    $PeerStatsLocked = $Cache->get_value('stats_peers_lock');
    if (!$PeerStatsLocked) {
        $Cache->cache_value('stats_peers_lock', 1, 30);
        $DB->query("
      SELECT IF(remaining=0,'Seeding','Leeching') AS Type, COUNT(uid)
      FROM xbt_files_users
      WHERE active = 1
      GROUP BY Type");
        $PeerCount = $DB->to_array(0, MYSQLI_NUM, false);
        $SeederCount = $PeerCount['Seeding'][1] ?: 0;
        $LeecherCount = $PeerCount['Leeching'][1] ?: 0;
        $Cache->cache_value('stats_peers', array($LeecherCount, $SeederCount), 604800); // 1 week cache
        $Cache->delete_value('stats_peers_lock');
    }
} else {
    $PeerStatsLocked = false;
    list($LeecherCount, $SeederCount) = $PeerStats;
}

if (!$PeerStatsLocked) {
    $Ratio = Format::get_ratio_html($SeederCount, $LeecherCount);
    $PeerCount = number_format($SeederCount + $LeecherCount);
    $SeederCount = number_format($SeederCount);
    $LeecherCount = number_format($LeecherCount);
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

    <!-- Polls -->
    <?php
if (($TopicID = $Cache->get_value('polls_featured')) === false) {
    $DB->query("
    SELECT TopicID
    FROM forums_polls
    ORDER BY Featured DESC
    LIMIT 1");
    list($TopicID) = $DB->next_record();
    $Cache->cache_value('polls_featured', $TopicID, 0);
}
if ($TopicID) {
    if (($Poll = $Cache->get_value("polls_$TopicID")) === false) {
        $DB->query("
      SELECT Question, Answers, Featured, Closed
      FROM forums_polls
      WHERE TopicID = '$TopicID'");
        list($Question, $Answers, $Featured, $Closed) = $DB->next_record(MYSQLI_NUM, array(1));
        $Answers = unserialize($Answers);
        $DB->query("
      SELECT Vote, COUNT(UserID)
      FROM forums_polls_votes
      WHERE TopicID = '$TopicID'
        AND Vote != '0'
      GROUP BY Vote");
        $VoteArray = $DB->to_array(false, MYSQLI_NUM);

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
        $Cache->cache_value("polls_$TopicID", array($Question, $Answers, $Votes, $Featured, $Closed), 0);
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

    $DB->query("
    SELECT Vote
    FROM forums_polls_votes
    WHERE UserID = '".$LoggedUser['ID']."'
      AND TopicID = '$TopicID'");
    list($UserResponse) = $DB->next_record(); ?>

    <div class="box">
      <div class="head colhead_dark"><strong>Poll<?php if ($Closed) {
        echo ' [Closed]';
    } ?>
        </strong>
      </div>
      <div class="pad">
        <p><strong><?=display_str($Question)?></strong></p>
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
          <li<?=((!empty($UserResponse) && ($UserResponse == $i))?' class="poll_your_answer"':'')?>><?=display_str($Answers[$i])?> (<?=number_format($Percent * 100, 2)?>%)</li>
            <li class="graph">
              <span class="center_poll"
                style="width: <?=round($Ratio * 140)?>px;"></span>
              <br />
            </li>
            <?php
    } ?>
        </ul>
        <strong>Votes:</strong> <?=number_format($TotalVotes)?><br />
        <?php } else { ?>
        <div id="poll_container">
          <form class="vote_form" name="poll" id="poll" action="">
            <input type="hidden" name="action" value="poll" />
            <input type="hidden" name="auth"
              value="<?=$LoggedUser['AuthKey']?>" />
            <input type="hidden" name="topicid"
              value="<?=$TopicID?>" />
            <?php foreach ($Answers as $i => $Answer) { ?>
            <input type="radio" name="vote" id="answer_<?=$i?>"
              value="<?=$i?>" />
            <label for="answer_<?=$i?>"><?=display_str($Answers[$i])?></label><br />
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
  <div class="main_column">
    <?php

$Recommend = $Cache->get_value('recommend');
$Recommend_artists = $Cache->get_value('recommend_artists');

if (!is_array($Recommend) || !is_array($Recommend_artists)) {
    $DB->query("
    SELECT
      tr.GroupID,
      tr.UserID,
      u.Username,
      tg.Name,
      tg.TagList
    FROM torrents_recommended AS tr
      JOIN torrents_group AS tg ON tg.ID = tr.GroupID
      LEFT JOIN users_main AS u ON u.ID = tr.UserID
    ORDER BY tr.Time DESC
    LIMIT 10");
    $Recommend = $DB->to_array();
    $Cache->cache_value('recommend', $Recommend, 1209600);

    $Recommend_artists = Artists::get_artists($DB->collect('GroupID'));
    $Cache->cache_value('recommend_artists', $Recommend_artists, 1209600);
}

if (count($Recommend) >= 4) {
    $Cache->increment('usage_index'); ?>
    <div class="box" id="recommended">
      <div class="head colhead_dark">
        <strong>Latest Vanity House additions</strong>
        <a data-toggle-target="#vanityhouse" , data-toggle-replace="Hide" class="brackets">Show</a>
      </div>

      <table class="torrent_table hidden" id="vanityhouse">
        <?php
  foreach ($Recommend as $Recommendations) {
      list($GroupID, $UserID, $Username, $GroupName, $TagList) = $Recommendations;
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
            <a href="torrents.php?id=<?=$GroupID?>"><?=$GroupName?></a> (by <?=Users::format_username($UserID, false, false, false)?>)
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

        <span class="float_right">
          <a data-toggle-target="#newsbody<?=$NewsID?>"
            data-toggle-replace="Show" class="brackets">Hide</a>
        </span>
      </div>

      <div id="newsbody<?=$NewsID?>" class="pad">
        <?=Text::full_format($Body)?>
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
View::show_footer(array('disclaimer'=>true));

function contest()
{
    global $DB, $Cache, $LoggedUser;

    list($Contest, $TotalPoints) = $Cache->get_value('contest');
    if (!$Contest) {
        $DB->query("
      SELECT
        UserID,
        SUM(Points),
        Username
      FROM users_points AS up
        JOIN users_main AS um ON um.ID = up.UserID
      GROUP BY UserID
      ORDER BY SUM(Points) DESC
      LIMIT 20");
        $Contest = $DB->to_array();

        $DB->query("
      SELECT SUM(Points)
      FROM users_points");
        list($TotalPoints) = $DB->next_record();

        $Cache->cache_value('contest', array($Contest, $TotalPoints), 600);
    } ?>
<!-- Contest Section -->
<div class="box box_contest">
  <div class="head colhead_dark"><strong>Quality time scoreboard</strong></div>
  <div class="pad">
    <ol style="padding-left: 5px;">
      <?php
  foreach ($Contest as $User) {
      list($UserID, $Points, $Username) = $User; ?>
      <li><?=Users::format_username($UserID, false, false, false)?>
        (<?=number_format($Points)?>)</li>
      <?php
  } ?>
    </ol>
    Total uploads: <?=$TotalPoints?><br />
    <a href="index.php?action=scoreboard">Full scoreboard</a>
  </div>
</div>
<!-- END contest Section -->
<?php
} // contest()
