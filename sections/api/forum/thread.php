<?php

#declare(strict_types=1);

$app = \Gazelle\App::go();

# todo: Go through line by line
// todo: Normalize thread_*_info don't need to waste all that ram on things that are already in other caches

/**********|| Page to show individual threads || ********************************\

Things to expect in $_GET:
  ThreadID: ID of the forum curently being browsed
  page: The page the user's on.
  page = 1 is the same as no page

********************************************************************************/

//---------- Things to sort out before it can start printing/generating content

// Check for lame SQL injection attempts
if (!isset($_GET['threadid']) || !is_numeric($_GET['threadid'])) {
    if (isset($_GET['topicid']) && is_numeric($_GET['topicid'])) {
        $ThreadID = $_GET['topicid'];
    } elseif (isset($_GET['postid']) && is_numeric($_GET['postid'])) {
        $app->dbOld->query("
        SELECT TopicID
        FROM forums_posts
          WHERE ID = $_GET[postid]");
        list($ThreadID) = $app->dbOld->next_record();

        if ($ThreadID) {
            // Redirect postid to threadid when necessary
            Http::redirect("api.php?action=forum&type=viewthread&threadid=$ThreadID&postid=$_GET[postid]");
            error();
        } else {
            echo json_encode(array('status' => 'failure'));
            error();
        }
    } else {
        echo json_encode(array('status' => 'failure'));
        error();
    }
} else {
    $ThreadID = $_GET['threadid'];
}

if (isset($_GET['pp'])) {
    $PerPage = $_GET['pp'];
} elseif (isset($app->user->extra['PostsPerPage'])) {
    $PerPage = $app->user->extra['PostsPerPage'];
} else {
    $PerPage = POSTS_PER_PAGE;
}

//---------- Get some data to start processing

// Thread information, constant across all pages
$ThreadInfo = Forums::get_thread_info($ThreadID, true, true);
if ($ThreadInfo === null) {
    json_die('failure', 'no such thread exists');
}
$ForumID = $ThreadInfo['ForumID'];

// Make sure they're allowed to look at the page
if (!Forums::check_forumperm($ForumID)) {
    echo json_encode(array('status' => 'failure'));
    error();
}

// Post links utilize the catalogue & key params to prevent issues with custom posts per page
if ($ThreadInfo['Posts'] > $PerPage) {
    if (isset($_GET['post']) && is_numeric($_GET['post'])) {
        $PostNum = $_GET['post'];
    } elseif (isset($_GET['postid']) && is_numeric($_GET['postid'])) {
        $app->dbOld->query("
        SELECT COUNT(ID)
        FROM forums_posts
          WHERE TopicID = $ThreadID
          AND ID <= $_GET[postid]");
        list($PostNum) = $app->dbOld->next_record();
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
if (!$Catalogue = $app->cache->get("thread_$ThreadID"."_catalogue_$CatalogueID")) {
    $app->dbOld->query("
    SELECT
      p.ID,
      p.AuthorID,
      p.AddedTime,
      p.Body,
      p.EditedUserID,
      p.EditedTime
    FROM forums_posts AS p
    WHERE p.TopicID = '$ThreadID'
      AND p.ID != '".$ThreadInfo['StickyPostID']."'
    LIMIT $CatalogueLimit");
    $Catalogue = $app->dbOld->to_array(false, MYSQLI_ASSOC);
    if (!$ThreadInfo['IsLocked'] || $ThreadInfo['IsSticky']) {
        $app->cache->set("thread_$ThreadID"."_catalogue_$CatalogueID", $Catalogue, 0);
    }
}
$Thread = Format::catalogue_select($Catalogue, $Page, $PerPage, THREAD_CATALOGUE);

if ($_GET['updatelastread'] !== '0') {
    $LastPost = end($Thread);
    $LastPost = $LastPost['ID'];
    reset($Thread);
    if ($ThreadInfo['Posts'] <= $PerPage * $Page && $ThreadInfo['StickyPostID'] > $LastPost) {
        $LastPost = $ThreadInfo['StickyPostID'];
    }
    // Handle last read
    if (!$ThreadInfo['IsLocked'] || $ThreadInfo['IsSticky']) {
        $app->dbOld->query("
        SELECT PostID
        FROM forums_last_read_topics
          WHERE UserID = '{$app->user->core['id']}'
          AND TopicID = '$ThreadID'");
        list($LastRead) = $app->dbOld->next_record();

        if ($LastRead < $LastPost) {
            $app->dbOld->query("
            INSERT INTO forums_last_read_topics
              (UserID, TopicID, PostID)
            VALUES
              ('{$app->user->core['id']}', '$ThreadID', '".db_string($LastPost)."')
            ON DUPLICATE KEY UPDATE
              PostID = '$LastPost'");
        }
    }
}

// Handle subscriptions
$UserSubscriptions = Subscriptions::get_subscriptions();

if (empty($UserSubscriptions)) {
    $UserSubscriptions = [];
}

if (in_array($ThreadID, $UserSubscriptions)) {
    $app->cache->delete('subscriptions_user_new_'.$app->user->core['id']);
}

$JsonPoll = [];
if ($ThreadInfo['NoPoll'] === 0) {
    if (!list($Question, $Answers, $Votes, $Featured, $Closed) = $app->cache->get("polls_$ThreadID")) {
        $app->dbOld->query("
        SELECT Question, Answers, Featured, Closed
        FROM forums_polls
          WHERE TopicID = '$ThreadID'");
        list($Question, $Answers, $Featured, $Closed) = $app->dbOld->next_record(MYSQLI_NUM, array(1));
        $Answers = unserialize($Answers);
        $app->dbOld->query("
        SELECT Vote, COUNT(UserID)
        FROM forums_polls_votes
          WHERE TopicID = '$ThreadID'
        GROUP BY Vote");
        $VoteArray = $app->dbOld->to_array(false, MYSQLI_NUM);

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
        $app->cache->set("polls_$ThreadID", array($Question, $Answers, $Votes, $Featured, $Closed), 0);
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
    $app->dbOld->query("
    SELECT Vote
    FROM forums_polls_votes
      WHERE UserID = '".$app->user->core['id']."'
      AND TopicID = '$ThreadID'");
    list($UserResponse) = $app->dbOld->next_record();
    if (!empty($UserResponse) && $UserResponse !== 0) {
        $Answers[$UserResponse] = '&rsaquo; '.$Answers[$UserResponse];
    } /* else {
        if (!empty($UserResponse) && $RevealVoters) {
            $Answers[$UserResponse] = '&rsaquo; '.$Answers[$UserResponse];
        }
    }
    */

    $JsonPoll['closed'] = ($Closed === 1);
    $JsonPoll['featured'] = $Featured;
    $JsonPoll['question'] = $Question;
    $JsonPoll['maxVotes'] = (int)$MaxVotes;
    $JsonPoll['totalVotes'] = $TotalVotes;
    $JsonPollAnswers = [];

    foreach ($Answers as $i => $Answer) {
        if (!empty($Votes[$i]) && $TotalVotes > 0) {
            $Ratio = $Votes[$i] / $MaxVotes;
            $Percent = $Votes[$i] / $TotalVotes;
        } else {
            $Ratio = 0;
            $Percent = 0;
        }
        $JsonPollAnswers[] = array(
            'answer' => $Answer,
            'ratio' => $Ratio,
            'percent' => $Percent
    );
    }

    if ($UserResponse !== null || $Closed || $ThreadInfo['IsLocked'] || $app->user->extra['Class'] < $Forums[$ForumID]['MinClassWrite']) {
        $JsonPoll['voted'] = true;
    } else {
        $JsonPoll['voted'] = false;
    }

    $JsonPoll['answers'] = $JsonPollAnswers;
}

// Sqeeze in stickypost
if ($ThreadInfo['StickyPostID']) {
    if ($ThreadInfo['StickyPostID'] !== $Thread[0]['ID']) {
        array_unshift($Thread, $ThreadInfo['StickyPost']);
    }
    if ($ThreadInfo['StickyPostID'] !== $Thread[count($Thread) - 1]['ID']) {
        $Thread[] = $ThreadInfo['StickyPost'];
    }
}

$JsonPosts = [];
foreach ($Thread as $Key => $Post) {
    list($PostID, $AuthorID, $AddedTime, $Body, $EditedUserID, $EditedTime) = array_values($Post);
    list($AuthorID, $Username, $PermissionID, $Paranoia, $Artist, $Donor, $Warned, $Avatar, $Enabled, $UserTitle) = array_values(User::user_info($AuthorID));

    $UserInfo = User::user_info($EditedUserID);
    $JsonPosts[] = [
    'postId' => (int)$PostID,
    'addedTime' => $AddedTime,
    'bbBody' => $Body,
    'body' => Text::parse($Body),
    'editedUserId' => (int)$EditedUserID,
    'editedTime' => $EditedTime,
    'editedUsername' => $UserInfo['Username'],
    'author' => [
      'authorId' => (int)$AuthorID,
      'authorName' => $Username,
      'paranoia' => $Paranoia,
      'artist' => $Artist === '1',
      'donor' => $Donor === '1',
      'warned' => (bool)$Warned,
      'avatar' => $Avatar,
      'enabled' => $Enabled === '2' ? false : true,
      'userTitle' => $UserTitle
    ]
  ];
}

echo json_encode([
  'status' => 'success',
  'response' => [
    'forumId' => (int)$ForumID,
    'forumName' => $Forums[$ForumID]['Name'],
    'threadId' => (int)$ThreadID,
    'threadTitle' => Text::esc($ThreadInfo['Title']),
    'subscribed' => in_array($ThreadID, $UserSubscriptions),
    'locked' => $ThreadInfo['IsLocked'] === 1,
    'sticky' => $ThreadInfo['IsSticky'] === 1,
    'currentPage' => (int)$Page,
    'pages' => ceil($ThreadInfo['Posts'] / $PerPage),
    'poll' => empty($JsonPoll) ? null : $JsonPoll,
    'posts' => $JsonPosts
  ]
]);
