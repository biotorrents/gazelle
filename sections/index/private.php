<?php
declare(strict_types=1);


$app = App::go();

# get the news
# todo: use discourse
$newsCount = 2;
$news = $app->cacheOld->get_value("news");

if (!$news) {
    $query = "select * from news order by time desc limit ?";
    $news = $app->dbNew->multi($query, [$newsCount]);
    $app->cacheOld->cache_value('news', $news, 3600 * 24 * 30);
}
#!d($news);exit;

/*
if ($user['LastReadNews'] !== $News[0][0] && count($News) > 0) {
    $cache->begin_transaction("user_info_heavy_{$userId}");
    $cache->update_row(false, array('LastReadNews' => $News[0][0]));
    $cache->commit_transaction(0);
    $db->query("
        UPDATE users_info SET LastReadNews = '".$News[0][0]."' WHERE UserID = $userId
    ");
    $user['LastReadNews'] = $News[0][0];
}
*/


# get the blog
# todo: use discourse
$blog = $app->cacheOld->get_value("blog");
if (!$blog) {
    $query = "
        SELECT b.ID, um.Username, b.UserID, b.Title, b.Body, b.Time, b.ThreadID FROM blog AS b
        LEFT JOIN users_main AS um ON b.UserID = um.ID ORDER BY Time DESC LIMIT ?
    ";
    $blog = $app->dbNew->multi($query, [5]);
    $app->cacheOld->cache_value("blog", $blog, 3600 * 24 * 30);
}
#!d($blog);exit;

/*
if (count($blog) < 5) {
    $limit = count($blog);
} else {
    $limit = 5;
}
*/


# get the freeleeches
# what the fuck
$freeleeches = $app->cacheOld->get_value("shop_freeleech_list");
if (!$freeleeches) {
    $query = "
        SELECT `TorrentID`, UNIX_TIMESTAMP(`ExpiryTime`),
        COALESCE(
            NULLIF(`title`, ''),
            NULLIF(`subject`, ''),
            `object`
        ) AS `Name`, `picture`
        FROM `shop_freeleeches` AS sf
        LEFT JOIN `torrents` AS t ON sf.`TorrentID` = t.`ID`
        LEFT JOIN `torrents_group` AS tg ON tg.`id` = t.`GroupID`
        ORDER BY `ExpiryTime` ASC LIMIT 10
    ";

    $freeleeches = $app->dbNew->multi($query);
    $app->cacheOld->cache_value("shop_freeleech_list", $freeleeches, 3600 * 24 * 30);
}


/** twig template */


$app->twig->display(
    "index/private.twig",
    [
    "sidebar" => true,

    /*
      "breadcrumbs" => true,
      "sidebar" => true,
      "title" => $category["name"],
      "category" => $category,
      "topics" => $topics,
      */
  ]
);


exit;





$ENV = ENV::go();

# fix for flight
$cache = new Cache($ENV->getPriv('MEMCACHED_SERVERS'));
$user = G::$user;
$db = new DB;


$userId = intval($user['ID']);
#!d($user);exit;


View::header('News', 'news_ajax');
?>

<div class="sidebar one-third column">
  <?php /*

  <div class="box">
    <div class="head colhead_dark"><strong><a href="blog.php">Latest blog posts</a></strong></div>
    <ul class="stats nobullet">
      <?php
for ($i = 0; $i < $limit; $i++) {
    list($BlogID, $Author, $AuthorID, $Title, $Body, $BlogTime, $ThreadID) = $blog[$i]; ?>
      <li>
        <?=($i + 1)?>. <a
          href="blog.php#blog<?=$BlogID?>"><?=$Title?></a>
      </li>
      <?php
}
?>
    </ul>
  </div>
  */ ?>





  <?php /*
if (count($Freeleeches)) {
    ?>
  <div class="box">
    <div class="head colhead_dark"><strong><a
          href="torrents.php?freetorrent=1&order_by=seeders&order_way=asc">Freeleeches</a></strong></div>
    <ul class="stats nobullet">
      <?php
  for ($i = 0; $i < count($freeleeches); $i++) {
      list($ID, $ExpiryTime, $Name, $Image) = $freeleeches[$i];
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
*/ ?>

  <!-- Polls -->
  <?php /*
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
 */?>
</div>
</div>
<div class="main_column two-thirds column">
  <?php
/*
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
*/


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
