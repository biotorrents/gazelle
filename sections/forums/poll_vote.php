<?php
#declare(strict_types=1);

if (!isset($_POST['topicid']) || !is_number($_POST['topicid'])) {
  error(0, true);
}
$TopicID = $_POST['topicid'];

if (!empty($_POST['large'])) {
  $Size = 750;
} else {
  $Size = 140;
}

if (!$ThreadInfo = $cache->get_value("thread_$TopicID".'_info')) {
  $db->query("
    SELECT
      t.Title,
      t.ForumID,
      t.IsLocked,
      t.IsSticky,
      COUNT(fp.id) AS Posts,
      t.LastPostAuthorID,
      ISNULL(p.TopicID) AS NoPoll
    FROM forums_topics AS t
      JOIN forums_posts AS fp ON fp.TopicID = t.ID
      LEFT JOIN forums_polls AS p ON p.TopicID = t.ID
    WHERE t.ID = '$TopicID'
    GROUP BY fp.TopicID");
  if (!$db->has_results()) {
    error();
  }
  $ThreadInfo = $db->next_record(MYSQLI_ASSOC);
  if (!$ThreadInfo['IsLocked'] || $ThreadInfo['IsSticky']) {
    $cache->cache_value("thread_$TopicID".'_info', $ThreadInfo, 0);
  }
}
$ForumID = $ThreadInfo['ForumID'];

if (!list($Question, $Answers, $Votes, $Featured, $Closed) = $cache->get_value("polls_$TopicID")) {
  $db->query("
    SELECT
      Question,
      Answers,
      Featured,
      Closed
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
    list($Key,$Value) = $VoteSet;
    $Votes[$Key] = $Value;
  }

  for ($i = 1, $il = count($Answers); $i <= $il; ++$i) {
    if (!isset($Votes[$i])) {
      $Votes[$i] = 0;
    }
  }
  $cache->cache_value("polls_$TopicID", array($Question, $Answers, $Votes, $Featured, $Closed), 0);
}


if ($Closed) {
  error(403,true);
}

if (!empty($Votes)) {
  $TotalVotes = array_sum($Votes);
  $MaxVotes = max($Votes);
} else {
  $TotalVotes = 0;
  $MaxVotes = 0;
}

if (!isset($_POST['vote']) || !is_number($_POST['vote'])) {
?>
<span class="error">Please select an option.</span><br />
<form class="vote_form" name="poll" id="poll" action="">
  <input type="hidden" name="action" value="poll" />
  <input type="hidden" name="auth" value="<?=$user['AuthKey']?>" />
  <input type="hidden" name="large" value="<?=esc($_POST['large'])?>" />
  <input type="hidden" name="topicid" value="<?=$TopicID?>" />
<?php for ($i = 1, $il = count($Answers); $i <= $il; $i++) { ?>
  <input type="radio" name="vote" id="answer_<?=$i?>" value="<?=$i?>" />
  <label for="answer_<?=$i?>"><?=esc($Answers[$i])?></label><br />
<?php } ?>
  <br /><input type="radio" name="vote" id="answer_0" value="0" /> <label for="answer_0">Blank&#8202;&mdash;&#8202;Show the results!</label><br /><br />
  <input type="button" onclick="ajax.post('index.php', 'poll', function(response) { $('#poll_container').raw().innerHTML = response });" value="Vote" />
</form>
<?php
} else {
  authorize();
  $Vote = $_POST['vote'];
  if (!isset($Answers[$Vote]) && $Vote != 0) {
    error(0,true);
  }

  //Add our vote
  $db->query("
    INSERT IGNORE INTO forums_polls_votes
      (TopicID, UserID, Vote)
    VALUES
      ($TopicID, " . $user['ID'] . ", $Vote)");
  if ($db->affected_rows() == 1 && $Vote != 0) {
    $cache->begin_transaction("polls_$TopicID");
    $cache->update_row(2, array($Vote => '+1'));
    $cache->commit_transaction(0);
    $Votes[$Vote]++;
    $TotalVotes++;
    $MaxVotes++;
  }

  if ($Vote != 0) {
    $Answers[$Vote] = '=> '.$Answers[$Vote];
  }

?>
    <ul class="poll nobullet">
<?php
    if ($ForumID != STAFF_FORUM) {
      for ($i = 1, $il = count($Answers); $i <= $il; $i++) {
        if (!empty($Votes[$i]) && $TotalVotes > 0) {
          $Ratio = $Votes[$i] / $MaxVotes;
          $Percent = $Votes[$i] / $TotalVotes;
        } else {
          $Ratio = 0;
          $Percent = 0;
        }
?>
          <li><?=esc($Answers[$i])?> (<?=Text::float($Percent * 100, 2)?>%)</li>
          <li class="graph">
            <span class="center_poll" style="width: <?=round($Ratio * $Size)?>px;"></span>
          </li>
<?php
      }
    } else {
      //Staff forum, output voters, not percentages
      $db->query("
        SELECT GROUP_CONCAT(um.Username SEPARATOR ', '),
          fpv.Vote
        FROM users_main AS um
          JOIN forums_polls_votes AS fpv ON um.ID = fpv.UserID
        WHERE TopicID = $TopicID
        GROUP BY fpv.Vote");

      $StaffVotes = $db->to_array();
      foreach ($StaffVotes as $StaffVote) {
        list($StaffString, $StaffVoted) = $StaffVote;
?>
        <li><a href="forums.php?action=change_vote&amp;threadid=<?=$TopicID?>&amp;auth=<?=$user['AuthKey']?>&amp;vote=<?=(int)$StaffVoted?>"><?=esc(empty($Answers[$StaffVoted]) ? 'Blank' : $Answers[$StaffVoted])?></a> - <?=$StaffString?></li>
<?php
      }
    }
?>
    </ul>
    <br /><strong>Votes:</strong> <?=Text::float($TotalVotes)?>
<?php
}
