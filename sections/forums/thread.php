<?php
#declare(strict_types = 1);

// todo: Normalize thread_*_info don't need to waste all that ram on things that are already in other caches

/**********|| Page to show individual threads || ********************************\

Things to expect in $_GET:
  ThreadID: ID of the forum curently being browsed
  page: The page the user's on.
  page = 1 is the same as no page

********************************************************************************/

//---------- Things to sort out before it can start printing/generating content

// Check for lame SQL injection attempts
if (!isset($_GET['threadid']) || !is_number($_GET['threadid'])) {
    if (isset($_GET['topicid']) && is_number($_GET['topicid'])) {
        $ThreadID = $_GET['topicid'];
    } elseif (isset($_GET['postid']) && is_number($_GET['postid'])) {
        $db->prepared_query("
      SELECT TopicID
      FROM forums_posts
      WHERE ID = $_GET[postid]");
        list($ThreadID) = $db->next_record();
        if ($ThreadID) {
            Http::redirect("forums.php?action=viewthread&threadid=$ThreadID&postid=$_GET[postid]#post$_GET[postid]");
            error();
        } else {
            error(404);
        }
    } else {
        error(404);
    }
} else {
    $ThreadID = $_GET['threadid'];
}

if (isset($user['PostsPerPage'])) {
    $PerPage = $user['PostsPerPage'];
} else {
    $PerPage = POSTS_PER_PAGE;
}

//---------- Get some data to start processing

// Thread information, constant across all pages
$ThreadInfo = Forums::get_thread_info($ThreadID, true, true);
if ($ThreadInfo === null) {
    error(404);
}
$ForumID = $ThreadInfo['ForumID'];

$IsDonorForum = ($ForumID == DONOR_FORUM);

// Make sure they're allowed to look at the page
if (!Forums::check_forumperm($ForumID)) {
    error(403);
}
//Escape strings for later display
$ThreadTitle = esc($ThreadInfo['Title']);
$ForumName = esc($Forums[$ForumID]['Name']);

//Post links utilize the catalogue & key params to prevent issues with custom posts per page
if ($ThreadInfo['Posts'] > $PerPage) {
    if (isset($_GET['post']) && is_number($_GET['post'])) {
        $PostNum = $_GET['post'];
    } elseif (isset($_GET['postid']) && is_number($_GET['postid']) && $_GET['postid'] != $ThreadInfo['StickyPostID']) {
        $SQL = "
      SELECT COUNT(ID)
      FROM forums_posts
      WHERE TopicID = $ThreadID
        AND ID <= $_GET[postid]";
        if ($ThreadInfo['StickyPostID'] < $_GET['postid']) {
            $SQL .= " AND ID != $ThreadInfo[StickyPostID]";
        }
        $db->prepared_query($SQL);
        list($PostNum) = $db->next_record();
    } else {
        $PostNum = 1;
    }
} else {
    $PostNum = 1;
}
list($Page, $Limit) = Format::page_limit($PerPage, min($ThreadInfo['Posts'], $PostNum));
if (($Page - 1) * $PerPage > $ThreadInfo['Posts']) {
    $Page = ceil($ThreadInfo['Posts'] / $PerPage);
}
list($CatalogueID, $CatalogueLimit) = Format::catalogue_limit($Page, $PerPage, THREAD_CATALOGUE);

// Cache catalogue from which the page is selected, allows block caches and future ability to specify posts per page
if (!$Catalogue = $cache->get_value("thread_{$ThreadID}_catalogue_$CatalogueID")) {
    $db->prepared_query("
    SELECT
      p.ID,
      p.AuthorID,
      p.AddedTime,
      p.Body,
      p.EditedUserID,
      p.EditedTime,
      ed.Username
    FROM forums_posts AS p
      LEFT JOIN users_main AS ed ON ed.ID = p.EditedUserID
    WHERE p.TopicID = '$ThreadID'
      AND p.ID != '".$ThreadInfo['StickyPostID']."'
    LIMIT $CatalogueLimit");
    $Catalogue = $db->to_array(false, MYSQLI_ASSOC);
    if (!$ThreadInfo['IsLocked'] || $ThreadInfo['IsSticky']) {
        $cache->cache_value("thread_{$ThreadID}_catalogue_$CatalogueID", $Catalogue, 0);
    }
}
$Thread = Format::catalogue_select($Catalogue, $Page, $PerPage, THREAD_CATALOGUE);
$LastPost = end($Thread);
$LastPost = $LastPost['ID'];
$FirstPost = reset($Thread);
$FirstPost = $FirstPost['ID'];
if ($ThreadInfo['Posts'] <= $PerPage*$Page && $ThreadInfo['StickyPostID'] > $LastPost) {
    $LastPost = $ThreadInfo['StickyPostID'];
}

//Handle last read

//Why would we skip this on locked or stickied threads?
//if (!$ThreadInfo['IsLocked'] || $ThreadInfo['IsSticky']) {

  $db->prepared_query("
    SELECT PostID
    FROM forums_last_read_topics
    WHERE UserID = '$user[ID]'
      AND TopicID = '$ThreadID'");
  list($LastRead) = $db->next_record();
  if ($LastRead < $LastPost) {
      $db->prepared_query("
      INSERT INTO forums_last_read_topics
        (UserID, TopicID, PostID)
      VALUES
        ('$user[ID]', '$ThreadID', '".db_string($LastPost)."')
      ON DUPLICATE KEY UPDATE
        PostID = '$LastPost'");
  }
//}

//Handle subscriptions
$UserSubscriptions = Subscriptions::get_subscriptions();

if (empty($UserSubscriptions)) {
    $UserSubscriptions = [];
}

if (in_array($ThreadID, $UserSubscriptions)) {
    $cache->delete_value('subscriptions_user_new_'.$user['ID']);
}


$QuoteNotificationsCount = $cache->get_value('notify_quoted_' . $user['ID']);
if ($QuoteNotificationsCount === false || $QuoteNotificationsCount > 0) {
    $db->prepared_query("
    UPDATE users_notify_quoted
    SET UnRead = false
    WHERE UserID = '$user[ID]'
      AND Page = 'forums'
      AND PageID = '$ThreadID'
      AND PostID >= '$FirstPost'
      AND PostID <= '$LastPost'");
    $cache->delete_value('notify_quoted_' . $user['ID']);
}

// Start printing
View::header(
    $ThreadInfo['Title'].' &rsaquo; '.$Forums[$ForumID]['Name'].' &rsaquo; Forums',
    'comments,subscriptions,vendor/easymde.min',
    ($IsDonorForum ?? 'donor,').'vendor/easymde.min'
);
?>
<div class="header">
  <h2>
    <a href="forums.php">Forums</a> &rsaquo;
    <a
      href="forums.php?action=viewforum&amp;forumid=<?=$ThreadInfo['ForumID']?>"><?=$ForumName?></a> &rsaquo;
    <?=$ThreadTitle?>
  </h2>

  <div class="linkbox">
    <div class="center">
      <a href="reports.php?action=report&amp;type=thread&amp;id=<?=$ThreadID?>"
        class="brackets">Report thread</a>

      <a href="#" onclick="Subscribe(<?=$ThreadID?>);return false;"
        id="subscribelink<?=$ThreadID?>" class="brackets"><?=(in_array($ThreadID, $UserSubscriptions) ? 'Unsubscribe' : 'Subscribe')?></a>

      <a data-toggle-target="#searchthread" data-toggle-replace="Hide search" class="brackets">Search this thread</a>
    </div>

    <div id="searchthread" class="hidden center">
      <div style="display: inline-block;">
        <h3>
          Search This Thread
        </h3>

        <form class="search_form" name="forum_thread" action="forums.php" method="get">
          <input type="hidden" name="action" value="search" />

          <input type="hidden" name="threadid"
            value="<?=$ThreadID?>" />

          <table class="layout border">
            <tr>
              <td></td>

              <td>
                <input type="search" id="searchbox" name="search" size="70" placeholder="Search Terms" />
              </td>
            </tr>

            <tr>
              <td>

              </td>

              <td>
                <input type="search" id="username" name="user" size="70" placeholder="Posted By" />
              </td>
            </tr>

            <tr>
              <td colspan="2" style="text-align: center;">
                <input type="submit" name="submit" value="Search" />
              </td>
            </tr>
          </table>
        </form>
      </div>
    </div>
  </div>
</div>
<?php
$Pages = Format::get_pages($Page, $ThreadInfo['Posts'], $PerPage, 9);
echo $Pages;

if ($ThreadInfo['NoPoll'] == 0) {
    if (!list($Question, $Answers, $Votes, $Featured, $Closed) = $cache->get_value("polls_$ThreadID")) {
        $db->prepared_query("
      SELECT Question, Answers, Featured, Closed
      FROM forums_polls
      WHERE TopicID = '$ThreadID'");
        list($Question, $Answers, $Featured, $Closed) = $db->next_record(MYSQLI_NUM, array(1));
        $Answers = unserialize($Answers);
        $db->prepared_query("
      SELECT Vote, COUNT(UserID)
      FROM forums_polls_votes
      WHERE TopicID = '$ThreadID'
      GROUP BY Vote");
        $VoteArray = $db->to_array(false, MYSQLI_NUM);

        $Votes = [];
        foreach ($VoteArray as $VoteSet) {
            list($Key, $Value) = $VoteSet;
            $Votes[$Key] = $Value;
        }

        foreach (array_keys($Answers) as $i) {
            if (!isset($Votes[$i])) {
                $Votes[$i] = 0;
            }
        }
        $cache->cache_value("polls_$ThreadID", array($Question, $Answers, $Votes, $Featured, $Closed), 0);
    }

    if (!empty($Votes)) {
        $TotalVotes = array_sum($Votes);
        $MaxVotes = max($Votes);
    } else {
        $TotalVotes = 0;
        $MaxVotes = 0;
    }

    #$RevealVoters = in_array($ForumID, FORUMS_TO_REVEAL_VOTERS);

    // Polls lose the you voted arrow thingy
    $db->prepared_query("
    SELECT Vote
    FROM forums_polls_votes
    WHERE UserID = '".$user['ID']."'
      AND TopicID = '$ThreadID'");
    list($UserResponse) = $db->next_record(); ?>
<div class="box thin clear">
  <div class="head colhead_dark"><strong>Poll<?php if ($Closed) {
        echo ' [Closed]';
    } ?><?php if ($Featured) {
        echo ' [Featured]';
    } ?>
    </strong>
    <a href="#" onclick="$('#threadpoll').gtoggle(); log_hit(); return false;" class="brackets">View</a>
  </div>
  <div class="pad<?php if (/*$LastRead !== null || */$ThreadInfo['IsLocked']) {
        echo ' hidden';
    } ?>" id="threadpoll">
    <p><strong><?=esc($Question)?></strong></p>
    <?php if ($UserResponse !== null || $Closed || $ThreadInfo['IsLocked'] || !Forums::check_forumperm($ForumID)) { ?>
    <ul class="poll nobullet">
      <?php
    if (!$RevealVoters) {
        foreach ($Answers as $i => $Answer) {
            if (!empty($Votes[$i]) && $TotalVotes > 0) {
                $Ratio = $Votes[$i] / $MaxVotes;
                $Percent = $Votes[$i] / $TotalVotes;
            } else {
                $Ratio = 0;
                $Percent = 0;
            } ?>
      <li<?=((!empty($UserResponse)&&($UserResponse == $i))?' class="poll_your_answer"':'')?>><?=esc($Answer)?> (<?=Text::float($Percent * 100, 2)?>%)</li>
        <li class="graph">
          <span class="center_poll"
            style="width: <?=round($Ratio * 750)?>px;"></span>
        </li>
        <?php
        }
        if ($Votes[0] > 0) {
            ?>
        <li>
          <?= ($UserResponse == '0' ? '&raquo;&nbsp;' : '') ?>
          (Blank)
          (<?= Text::float((float) ($Votes[0] / $TotalVotes * 100), 2) ?>%)
        </li>

        <li class="graph">
          <span class="center_poll"
            style="width: <?=round(($Votes[0] / $MaxVotes) * 750)?>px;"></span>
          <span class="right_poll"></span>
        </li>
        <?php
        } ?>
    </ul>
    <br />

    <strong>Votes:</strong> <?=Text::float($TotalVotes)?><br /><br />
    <?php
    } else {
        //Staff forum, output voters, not percentages
        include(SERVER_ROOT.'/sections/staff/functions.php');
        $Staff = get_staff();

        $StaffNames = [];
        foreach ($Staff as $Staffer) {
            $StaffNames[] = $Staffer['Username'];
        }

        $db->prepared_query("
        SELECT
          fpv.Vote AS Vote,
          GROUP_CONCAT(um.Username SEPARATOR ', ')
        FROM users_main AS um
          LEFT JOIN forums_polls_votes AS fpv ON um.ID = fpv.UserID
        WHERE TopicID = $ThreadID
        GROUP BY fpv.Vote");

        $StaffVotesTmp = $db->to_array();
        $StaffCount = count($StaffNames);

        $StaffVotes = [];
        foreach ($StaffVotesTmp as $StaffVote) {
            list($Vote, $Names) = $StaffVote;
            $StaffVotes[$Vote] = $Names;
            $Names = explode(', ', $Names);
            $StaffNames = array_diff($StaffNames, $Names);
        } ?>
    <ul style="list-style: none;" id="poll_options">
      <?php
      foreach ($Answers as $i => $Answer) {
          ?>
      <li>
        <a
          href="forums.php?action=change_vote&amp;threadid=<?=$ThreadID?>&amp;auth=<?=$user['AuthKey']?>&amp;vote=<?=(int)$i?>"><?=esc($Answer == '' ? 'Blank' : $Answer)?></a>
        - <?=$StaffVotes[$i]?>&nbsp;(<?=Text::float(((float)$Votes[$i] / $TotalVotes) * 100, 2)?>%)
        <a href="forums.php?action=delete_poll_option&amp;threadid=<?=$ThreadID?>&amp;auth=<?=$user['AuthKey']?>&amp;vote=<?=(int)$i?>"
          class="brackets tooltip" title="Delete poll option">X</a>
      </li>
      <?php
      } ?>
      <li>
        <a
          href="forums.php?action=change_vote&amp;threadid=<?=$ThreadID?>&amp;auth=<?=$user['AuthKey']?>&amp;vote=0"><?=($UserResponse == '0' ? '&raquo;&nbsp;' : '')?>Blank</a>
        - <?=$StaffVotes[0]?>&nbsp;(<?=Text::float(((float)$Votes[0] / $TotalVotes) * 100, 2)?>%)
      </li>
    </ul>
    <?php
      if ($ForumID == STAFF_FORUM) {
          ?>
    <br />
    <strong>Votes:</strong> <?=Text::float($StaffCount - count($StaffNames))?> / <?=$StaffCount?> current staff, <?=Text::float($TotalVotes)?> total
    <br />
    <strong>Missing votes:</strong> <?=implode(", ", $StaffNames);
          echo "\n"; ?>
    <br /><br />
    <?php
      } ?>
    <a href="#"
      onclick="AddPollOption(<?=$ThreadID?>); return false;"
      class="brackets">+</a>
    <?php
    }

  } else {
      //User has not voted
?>
    <div id="poll_container">
      <form class="vote_form" name="poll" id="poll">
        <input type="hidden" name="action" value="poll" />
        <input type="hidden" name="auth"
          value="<?=$user['AuthKey']?>" />
        <input type="hidden" name="large" value="1" />
        <input type="hidden" name="topicid" value="<?=$ThreadID?>" />
        <ul style="list-style: none;" id="poll_options">
          <?php foreach ($Answers as $i => $Answer) { //for ($i = 1, $il = count($Answers); $i <= $il; $i++) {?>
          <li>
            <input type="radio" name="vote" id="answer_<?=$i?>"
              value="<?=$i?>" />
            <label for="answer_<?=$i?>"><?=esc($Answer)?></label>
          </li>
          <?php } ?>
          <li>
            <br />
            <input type="radio" name="vote" id="answer_0" value="0" /> <label
              for="answer_0">Blank&#8202;&mdash;&#8202;Show the results!</label><br />
          </li>
        </ul>
        <?php if ($ForumID == STAFF_FORUM) { ?>
        <a href="#"
          onclick="AddPollOption(<?=$ThreadID?>); return false;"
          class="brackets">+</a>
        <br />
        <br />
        <?php } ?>
        <input type="button" class="button-primary"
          onclick="ajax.post('index.php','poll',function(response) { $('#poll_container').raw().innerHTML = response});"
          value="Vote" />
      </form>
    </div>
    <?php
  }
    if (check_perms('forums_polls_moderate')) {
        #if (check_perms('forums_polls_moderate') && !$RevealVoters) {
        if (!$Featured) {
            ?>
    <form class="manage_form" name="poll" action="forums.php" method="post">
      <input type="hidden" name="action" value="poll_mod" />
      <input type="hidden" name="auth"
        value="<?=$user['AuthKey']?>" />
      <input type="hidden" name="topicid" value="<?=$ThreadID?>" />
      <input type="hidden" name="feature" value="1" />
      <input type="submit" onclick="return confirm('Are you sure you want to feature this poll?');" value="Feature" />
    </form>
    <?php
        } ?>
    <form class="manage_form" name="poll" action="forums.php" method="post">
      <input type="hidden" name="action" value="poll_mod" />
      <input type="hidden" name="auth"
        value="<?=$user['AuthKey']?>" />
      <input type="hidden" name="topicid" value="<?=$ThreadID?>" />
      <input type="hidden" name="close" value="1" />
      <input type="submit"
        value="<?=(!$Closed ? 'Close' : 'Open')?>" />
    </form>
    <?php
    } ?>
  </div>
</div>
<?php
} // End Polls

// Sqeeze in stickypost
if ($ThreadInfo['StickyPostID']) {
    if ($ThreadInfo['StickyPostID'] != $Thread[0]['ID']) {
        array_unshift($Thread, $ThreadInfo['StickyPost']);
    }
    if ($ThreadInfo['StickyPostID'] != $Thread[count($Thread) - 1]['ID']) {
        $Thread[] = $ThreadInfo['StickyPost'];
    }
}

foreach ($Thread as $Key => $Post) {
    list($PostID, $AuthorID, $AddedTime, $Body, $EditedUserID, $EditedTime, $EditedUsername) = array_values($Post);
    list($AuthorID, $Username, $PermissionID, $Paranoia, $Artist, $Donor, $Warned, $Avatar, $Enabled, $UserTitle) = array_values(Users::user_info($AuthorID)); ?>
<table class="forum_post wrap_overflow box vertical_margin<?php
  if ((
        (!$ThreadInfo['IsLocked'] || $ThreadInfo['IsSticky'])
      && $PostID > $LastRead
      && strtotime($AddedTime) > $user['CatchupTime']
    ) || (isset($RequestKey) && $Key == $RequestKey)
    ) {
      echo ' forum_unread';
  }
    if (!Users::has_avatars_enabled()) {
        echo ' noavatar';
    }
    if ($ThreadInfo['OP'] == $AuthorID) {
        echo ' important_user';
    }
    if ($PostID == $ThreadInfo['StickyPostID']) {
        echo ' sticky_post';
    }
    if (Permissions::is_mod($AuthorID)) {
        echo ' staff_post';
    } ?>" id="post<?=$PostID?>">
  <colgroup>
    <?php if (Users::has_avatars_enabled()) { ?>
    <col class="col_avatar" />
    <?php } ?>
    <col class="col_post_body" />
  </colgroup>
  <tr class="colhead_dark">
    <td colspan="<?=Users::has_avatars_enabled() ? 2 : 1?>">
      <div class="float_left"><a class="post_id"
          href="forums.php?action=viewthread&amp;threadid=<?=$ThreadID?>&amp;postid=<?=$PostID?>#post<?=$PostID?>">#<?=$PostID?></a>
        <?=Users::format_username($AuthorID, true, true, true, true, true);
    echo "\n"; ?>
        <?=time_diff($AddedTime, 2);
    echo "\n"; ?>
        - <a href="#quickpost" id="quote_<?=$PostID?>"
          onclick="Quote('<?=$PostID?>', '<?=$Username?>', true);"
          class="brackets">Quote</a>
        <?php if ((!$ThreadInfo['IsLocked'] && Forums::check_forumperm($ForumID, 'Write') && $AuthorID == $user['ID']) || check_perms('site_moderate_forums')) { ?>
        - <a href="#post<?=$PostID?>"
          onclick="Edit_Form('<?=$PostID?>', '<?=$Key?>');"
          class="brackets">Edit</a>
        <?php
  }
    if (check_perms('site_admin_forums') && $ThreadInfo['Posts'] > 1) { ?>
        - <a href="#post<?=$PostID?>"
          onclick="Delete('<?=$PostID?>');"
          class="brackets">Delete</a>
        <?php
  }
    if ($PostID == $ThreadInfo['StickyPostID']) { ?>
        <strong><span class="sticky_post_label brackets">Sticky</span></strong>
        <?php if (check_perms('site_moderate_forums')) { ?>
        - <a
          href="forums.php?action=sticky_post&amp;threadid=<?=$ThreadID?>&amp;postid=<?=$PostID?>&amp;remove=true&amp;auth=<?=$user['AuthKey']?>"
          title="Unsticky this post" class="brackets tooltip">X</a>
        <?php
    }
  } else {
      if (check_perms('site_moderate_forums')) {
          ?>
        - <a
          href="forums.php?action=sticky_post&amp;threadid=<?=$ThreadID?>&amp;postid=<?=$PostID?>&amp;auth=<?=$user['AuthKey']?>"
          title="Sticky this post" class="brackets tooltip">&#x21d5;</a>
        <?php
      }
  } ?>
      </div>
      <div id="bar<?=$PostID?>" class="float_right">
        <a href="reports.php?action=report&amp;type=post&amp;id=<?=$PostID?>"
          class="brackets">Report</a>
        <?php
  if (check_perms('users_warn') && $AuthorID != $user['ID']) {
      $AuthorInfo = Users::user_info($AuthorID);
      if ($user['Class'] >= $AuthorInfo['Class']) {
          ?>
        <form class="manage_form hidden" name="user"
          id="warn<?=$PostID?>" method="post">
          <input type="hidden" name="action" value="warn" />
          <input type="hidden" name="postid" value="<?=$PostID?>" />
          <input type="hidden" name="userid"
            value="<?=$AuthorID?>" />
          <input type="hidden" name="key" value="<?=$Key?>" />
        </form>
        - <a href="#"
          onclick="$('#warn<?=$PostID?>').raw().submit(); return false;"
          class="brackets">Warn</a>
        <?php
      }
  } ?>
      </div>
    </td>
  </tr>
  <tr>
    <?php if (Users::has_avatars_enabled()) { ?>
    <td class="avatar valign_top">
      <?=Users::show_avatar($Avatar, $AuthorID, $Username, $HeavyInfo['DisableAvatars'], 150, true)?>
    </td>
    <?php } ?>
    <td class="body valign_top" <?php if (!Users::has_avatars_enabled()) {
      echo ' colspan="2"';
  } ?>>
      <div id="content<?=$PostID?>">
        <?=Text::parse($Body) ?>
        <?php if ($EditedUserID) { ?>
        <br />
        <br />
        <div class="last_edited">
          <?php if (check_perms('site_admin_forums')) { ?>
          <a href="#content<?=$PostID?>"
            onclick="LoadEdit('forums', <?=$PostID?>, 1); return false;">&laquo;</a>
          <?php } ?>
          Last edited by
          <?=Users::format_username($EditedUserID, false, false, false, false, false) ?>
          <?=time_diff($EditedTime, 2, true, true)?>
        </div>
        <?php } ?>
      </div>
    </td>
  </tr>
</table>
<?php
} ?>

<div class="breadcrumbs">
  <p>
    <a href="forums.php">Forums</a> &rsaquo;
    <a
      href="forums.php?action=viewforum&amp;forumid=<?=$ThreadInfo['ForumID']?>"><?=$ForumName?></a> &rsaquo;
    <?=$ThreadTitle?>
  </p>
</div>

<div class="linkbox">
  <?= $Pages ?>
</div>

<?php
if (!$ThreadInfo['IsLocked'] || check_perms('site_moderate_forums')) {
      if (Forums::check_forumperm($ForumID, 'Write') && !$user['DisablePosting']) {
          View::parse('generic/reply/quickreply.php', array(
      'InputTitle' => 'Reply',
      'InputName' => 'thread',
      'InputID' => $ThreadID,
      'ForumID' => $ForumID,
      'TextareaCols' => 90
    ));
      }
  }

if (check_perms('site_moderate_forums')) {
    G::$db->prepared_query("
      SELECT ID, AuthorID, AddedTime, Body
      FROM forums_topic_notes
      WHERE TopicID = $ThreadID
      ORDER BY ID ASC");
    $Notes = G::$db->to_array(); ?>
<br />
<h3 id="thread_notes">Notes</h3> <a data-toggle-target="#thread_notes_table" class="brackets">Toggle</a>
<form action="forums.php" method="post">
  <input type="hidden" name="action" value="take_topic_notes" />
  <input type="hidden" name="auth"
    value="<?=$user['AuthKey']?>" />
  <input type="hidden" name="topicid" value="<?=$ThreadID?>" />
  <table class="layout border hidden" id="thread_notes_table">
    <?php
  foreach ($Notes as $Note) {
      ?>
    <tr>
      <td><?=Users::format_username($Note['AuthorID'])?>
        (<?=time_diff($Note['AddedTime'], 2, true, true)?>)
      </td>
      <td><?=Text::parse($Note['Body'])?>
      </td>
    </tr>
    <?php
  } ?>
    <tr>
      <td>
        <div class="textarea_wrap">
          <?php
      View::textarea(
          id: 'topic_notes',
          name: 'body',
      ); ?>
        </div>
        <input type="submit" class="button-primary" value="Save" />
      </td>
    </tr>
  </table>
</form>
<br />
<h3>Edit</h3>
<form class="edit_form" name="forum_thread" action="forums.php" method="post">
  <div>
    <input type="hidden" name="action" value="mod_thread" />
    <input type="hidden" name="auth"
      value="<?=$user['AuthKey']?>" />
    <input type="hidden" name="threadid" value="<?=$ThreadID?>" />
    <input type="hidden" name="page" value="<?=$Page?>" />
  </div>
  <table class="layout border slight_margin">
    <tr>
      <td class="label"><label for="sticky_thread_checkbox">Sticky</label></td>
      <td>
        <input type="checkbox" id="sticky_thread_checkbox" data-toggle-target="#ranking_row" name="sticky" <?php if ($ThreadInfo['IsSticky']) {
          echo ' checked="checked"';
      } ?>
        tabindex="2" />
      </td>
    </tr>
    <tr id="ranking_row" <?=!$ThreadInfo['IsSticky'] ? ' class="hidden"' : ''?>>
      <td class="label"><label for="thread_ranking_textbox">Ranking</label></td>
      <td>
        <input type="text" id="thread_ranking_textbox" name="ranking"
          value="<?=$ThreadInfo['Ranking']?>"
          tabindex="2" />
      </td>
    </tr>
    <tr>
      <td class="label"><label for="locked_thread_checkbox">Locked</label></td>
      <td>
        <input type="checkbox" id="locked_thread_checkbox" name="locked" <?php if ($ThreadInfo['IsLocked']) {
          echo ' checked="checked"';
      } ?>
        tabindex="2" />
      </td>
    </tr>
    <tr>
      <td class="label"><label for="thread_title_textbox">Title</label></td>
      <td>
        <input type="text" id="thread_title_textbox" name="title" style="width: 75%;"
          value="<?=esc($ThreadInfo['Title'])?>"
          tabindex="2" />
      </td>
    </tr>
    <tr>
      <td class="label"><label for="move_thread_selector">Move</label></td>
      <td>
        <select name="forumid" id="move_thread_selector" tabindex="2">
          <?php
  $OpenGroup = false;
    $LastCategoryID = -1;

    foreach ($Forums as $Forum) {
        if ($Forum['MinClassRead'] > $user['Class']) {
            continue;
        }

        if ($Forum['CategoryID'] != $LastCategoryID) {
            $LastCategoryID = $Forum['CategoryID'];
            if ($OpenGroup) { ?>
          </optgroup>
          <?php } ?>
          <optgroup
            label="<?=$ForumCats[$Forum['CategoryID']]?>">
            <?php $OpenGroup = true;
        } ?>
            <option value="<?=$Forum['ID']?>" <i>
              </i>
              <!-- %pcs-comment-start#<?php if ($ThreadInfo['ForumID'] == $Forum['ID']) {
            echo ' selected="selected"';
        } ?>><?=esc($Forum['Name'])?>
            </option>
            <?php
    } ?>
          </optgroup>
        </select>
      </td>
    </tr>
    <?php if (check_perms('site_admin_forums')) { ?>
    <tr>
      <td class="label"><label for="delete_thread_checkbox">Delete</label></td>
      <td>
        <input type="checkbox" id="delete_thread_checkbox" name="delete" tabindex="2" />
      </td>
    </tr>
    <?php } ?>
    <tr>
      <td colspan="2" class="center">
        <input type="submit" value="Edit" tabindex="2" />
        <span class="float_right">
          <input type="submit" name="trash" value="Trash" tabindex="2" />
        </span>
      </td>
    </tr>

  </table>
</form>
<?php
} // If user is moderator
?>
</div>
<?php View::footer();
