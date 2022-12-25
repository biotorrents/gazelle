<?php
declare(strict_types=1);


$app = App::go();

# get the news
# todo: use discourse
$query = "select * from news order by time desc";
$news = $app->dbNew->row($query);

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
$query = "
    select *, users_main.username from blog
    left join users_main on blog.userId = users_main.id
    order by time desc limit ?
";
$blog = $app->dbNew->multi($query, [3]);

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


$app->twig->display("index/private.twig", [
    "sidebar" => true,
    "news" => $news,

    /*
      "breadcrumbs" => true,
      "sidebar" => true,
      "title" => $category["name"],
      "category" => $category,
      "topics" => $topics,
      */
]);


exit;





?>

<div class="sidebar one-third column">


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
