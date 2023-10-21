<?php

declare(strict_types=1);


/**
 * private homepage
 */

$app = \Gazelle\App::go();


# get the news
# todo: use discourse
$query = "select * from news order by time desc";
$news = $app->dbNew->row($query);

/*
if ($app->user->extra['LastReadNews'] !== $News[0][0] && count($News) > 0) {
    $app->cacheOld->begin_transaction("user_info_heavy_{$app->userId}");
    $app->cacheOld->update_row(false, array('LastReadNews' => $News[0][0]));
    $app->cacheOld->commit_transaction(0);
    $app->dbOld->query("
        UPDATE users_info SET LastReadNews = '".$News[0][0]."' WHERE UserID = $app->userId
    ");
    $app->user->extra['LastReadNews'] = $News[0][0];
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
$freeleeches = $app->cache->get("shop_freeleech_list");
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
    $app->cache->set("shop_freeleech_list", $freeleeches, 3600 * 24 * 30);
}


# sidebar stats
$stats = new \Gazelle\Stats();
$activeUsers = $stats->activeUsers();
$torrentAggregates = $stats->torrentAggregates();
$trackerAggregates = $stats->trackerAggregates();


/** twig template */


$app->twig->display("index/private.twig", [
    "sidebar" => true,
    "news" => $news,

    # stats
    "activeUsers" => $activeUsers,
    "torrentAggregates" => $torrentAggregates,
    "trackerAggregates" => $trackerAggregates,



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
if (($TopicID = $app->cache->get('polls_featured')) === false) {
    $app->dbOld->query("
    SELECT TopicID
    FROM forums_polls
    ORDER BY Featured DESC
    LIMIT 1");
    list($TopicID) = $app->dbOld->next_record();
    $app->cache->set('polls_featured', $TopicID, 0);
}
if ($TopicID) {
    if (($Poll = $app->cache->get("polls_$TopicID")) === false) {
        $app->dbOld->query("
      SELECT Question, Answers, Featured, Closed
      FROM forums_polls
      WHERE TopicID = '$TopicID'");
        list($Question, $Answers, $Featured, $Closed) = $app->dbOld->next_record(MYSQLI_NUM, array(1));
        $Answers = unserialize($Answers);
        $app->dbOld->query("
      SELECT Vote, COUNT(UserID)
      FROM forums_polls_votes
      WHERE TopicID = '$TopicID'
        AND Vote != '0'
      GROUP BY Vote");
        $VoteArray = $app->dbOld->to_array(false, MYSQLI_NUM);

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
        $app->cache->set("polls_$TopicID", array($Question, $Answers, $Votes, $Featured, $Closed), 0);
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

    $app->dbOld->query("
    SELECT Vote
    FROM forums_polls_votes
    WHERE UserID = '".$app->user->core['id']."'
      AND TopicID = '$TopicID'");
    list($UserResponse) = $app->dbOld->next_record(); ?>

      <div class="box">
        <div class="head colhead_dark"><strong>Poll<?php if ($Closed) {
        echo ' [Closed]';
    } ?>
          </strong>
        </div>
        <div class="pad">
          <p><strong><?=\Gazelle\Text::esc($Question)?></strong></p>
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
            <li<?=((!empty($UserResponse) && ($UserResponse == $i))?' class="poll_your_answer"':'')?>><?=\Gazelle\Text::esc($Answers[$i])?> (<?=\Gazelle\Text::float($Percent * 100, 2)?>%)</li>
              <li class="graph">
                <span class="center_poll"
                  style="width: <?=round($Ratio * 140)?>px;"></span>
                <br>
              </li>
              <?php
    } ?>
          </ul>
          <strong>Votes:</strong> <?=\Gazelle\Text::float($TotalVotes)?><br>
          <?php } else { ?>
          <div id="poll_container">
            <form class="vote_form" name="poll" id="poll" action="">
              <input type="hidden" name="action" value="poll">
              <input type="hidden" name="auth"
                value="<?=$app->user->extra['AuthKey']?>" />
              <input type="hidden" name="topicid"
                value="<?=$TopicID?>" />
              <?php foreach ($Answers as $i => $Answer) { ?>
              <input type="radio" name="vote" id="answer_<?=$i?>"
                value="<?=$i?>" />
              <label for="answer_<?=$i?>"><?=\Gazelle\Text::esc($Answers[$i])?></label><br>
              <?php } ?>
              <br><input type="radio" name="vote" id="answer_0" value="0"> <label
                for="answer_0">Blank&#8202;&mdash;&#8202;Show the results!</label><br><br>
              <input type="button"
                onclick="ajax.post('index.php', 'poll', function(response) { $('#poll_container').raw().innerHTML = response } );"
                value="Vote" />
            </form>
          </div>
          <?php } ?>
          <br><strong>Topic:</strong> <a
            href="forums.php?action=viewthread&amp;threadid=<?=$TopicID?>">Visit</a>
        </div>
      </div>
      <?php
}
// polls();
 */
