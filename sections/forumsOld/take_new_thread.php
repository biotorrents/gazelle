<?php

declare(strict_types=1);

$app = \Gazelle\App::go();

authorize();
$ENV = ENV::go();

/*
'new' if the user is creating a new thread
  It will be accompanied with:
  $_POST['forum']
  $_POST['title']
  $_POST['body']

  and optionally include:
  $_POST['question']
  $_POST['answers']
  the latter of which is an array
*/

if (isset($app->userNew->extra['PostsPerPage'])) {
    $PerPage = $app->userNew->extra['PostsPerPage'];
} else {
    $PerPage = POSTS_PER_PAGE;
}


if (isset($_POST['thread']) && !is_numeric($_POST['thread'])) {
    error(0);
}

if (isset($_POST['forum']) && !is_numeric($_POST['forum'])) {
    error(0);
}

// If you're not sending anything, go back
if (empty($_POST['body']) || empty($_POST['title'])) {
    header('Location: '.$_SERVER['HTTP_REFERER']);
    error();
}

$Body = $_POST['body'];

if ($app->userNew->extra['DisablePosting']) {
    error('Your posting privileges have been removed.');
}

$Title = Format::cut_string(trim($_POST['title']), 150, 1, 0);


$ForumID = $_POST['forum'];

if (!isset($Forums[$ForumID])) {
    error(404);
}

if (!Forums::check_forumperm($ForumID, 'Write') || !Forums::check_forumperm($ForumID, 'Create')) {
    error(403);
}

if (empty($_POST['question']) || empty($_POST['answers']) || !check_perms('forums_polls_create')) {
    $NoPoll = 1;
} else {
    $NoPoll = 0;
    $Question = trim($_POST['question']);
    $Answers = [];
    $Votes = [];

    //This can cause polls to have answer IDs of 1 3 4 if the second box is empty
    foreach ($_POST['answers'] as $i => $Answer) {
        if ($Answer === '') {
            continue;
        }
        $Answers[$i + 1] = $Answer;
        $Votes[$i + 1] = 0;
    }

    if (count($Answers) < 2) {
        error('You cannot create a poll with only one answer.');
    } elseif (count($Answers) > 25) {
        error('You cannot create a poll with greater than 25 answers.');
    }
}

$app->dbOld->query("
  INSERT INTO forums_topics
    (Title, AuthorID, ForumID, LastPostTime, LastPostAuthorID, CreatedTime)
  Values
    ('".db_string($Title)."', '".$app->userNew->core['id']."', '$ForumID', NOW(), '".$app->userNew->core['id']."', NOW())");
$TopicID = $app->dbOld->inserted_id();

$app->dbOld->query("
  INSERT INTO forums_posts
    (TopicID, AuthorID, AddedTime, Body)
  VALUES
    ('$TopicID', '".$app->userNew->core['id']."', NOW(), '".db_string($Body)."')");

$PostID = $app->dbOld->inserted_id();

$app->dbOld->query("
  UPDATE forums
  SET
    NumPosts         = NumPosts + 1,
    NumTopics        = NumTopics + 1,
    LastPostID       = '$PostID',
    LastPostAuthorID = '".$app->userNew->core['id']."',
    LastPostTopicID  = '$TopicID',
    LastPostTime     = NOW()
  WHERE ID = '$ForumID'");

$app->dbOld->query("
  UPDATE forums_topics
  SET
    NumPosts         = NumPosts + 1,
    LastPostID       = '$PostID',
    LastPostAuthorID = '".$app->userNew->core['id']."',
    LastPostTime     = NOW()
  WHERE ID = '$TopicID'");

if (isset($_POST['subscribe'])) {
    Subscriptions::subscribe($TopicID);
}

//Award a badge if necessary
$app->dbOld->query("
  SELECT COUNT(ID)
  FROM forums_posts
  WHERE AuthorID = '{$app->userNew->core['id']}'");
list($UserPosts) = $app->dbOld->next_record(MYSQLI_NUM, false);
foreach ($ENV->AUTOMATED_BADGE_IDS->Posts as $Count => $Badge) {
    if ((int) $UserPosts >= $Count) {
        $Success = Badges::awardBadge($app->userNew->core['id'], $Badge);
        if ($Success) {
            Misc::send_pm($app->userNew->core['id'], 0, 'You have received a badge!', "You have received a badge for making ".$Count." forum posts.\n\nIt can be enabled from your user settings.");
        }
    }
}

if (!$NoPoll) { // god, I hate double negatives...
    $app->dbOld->query("
    INSERT INTO forums_polls
      (TopicID, Question, Answers)
    VALUES
      ('$TopicID', '".db_string($Question)."', '".db_string(serialize($Answers))."')");
    $app->cacheNew->set("polls_$TopicID", array($Question, $Answers, $Votes, null, '0'), 0);

    if ($ForumID === STAFF_FORUM) {
        send_irc(STAFF_CHAN, 'Poll created by '.$app->userNew->core['username'].": '$Question' ".site_url()."forums.php?action=viewthread&threadid=$TopicID");
    }
}

// if cache exists modify it, if not, then it will be correct when selected next, and we can skip this block
if ($Forum = $app->cacheNew->get("forums_$ForumID")) {
    list($Forum, , , $Stickies) = $Forum;

    // Remove the last thread from the index
    if (count($Forum) === TOPICS_PER_PAGE && $Stickies < TOPICS_PER_PAGE) {
        array_pop($Forum);
    }

    if ($Stickies > 0) {
        $Part1 = array_slice($Forum, 0, $Stickies, true); // Stickies
    $Part3 = array_slice($Forum, $Stickies, TOPICS_PER_PAGE - $Stickies - 1, true); // Rest of page
    } else {
        $Part1 = [];
        $Part3 = $Forum;
    }
    $Part2 = array($TopicID => array(
    'ID' => $TopicID,
    'Title' => $Title,
    'AuthorID' => $app->userNew->core['id'],
    'IsLocked' => 0,
    'IsSticky' => 0,
    'NumPosts' => 1,
    'LastPostID' => $PostID,
    'LastPostTime' => sqltime(),
    'LastPostAuthorID' => $app->userNew->core['id'],
    'NoPoll' => $NoPoll
  )); // Bumped
    $Forum = $Part1 + $Part2 + $Part3;

    $app->cacheNew->set("forums_$ForumID", array($Forum, '', 0, $Stickies), 0);

    // Update the forum root
    $app->cacheOld->begin_transaction('forums_list');
    $app->cacheOld->update_row($ForumID, array(
    'NumPosts' => '+1',
    'NumTopics' => '+1',
    'LastPostID' => $PostID,
    'LastPostAuthorID' => $app->userNew->core['id'],
    'LastPostTopicID' => $TopicID,
    'LastPostTime' => sqltime(),
    'Title' => $Title,
    'IsLocked' => 0,
    'IsSticky' => 0
    ));
    $app->cacheOld->commit_transaction(0);
} else {
    // If there's no cache, we have no data, and if there's no data
    $app->cacheNew->delete('forums_list');
}

$app->cacheOld->begin_transaction("thread_$TopicID".'_catalogue_0');
$Post = array(
  'ID' => $PostID,
  'AuthorID' => $app->userNew->core['id'],
  'AddedTime' => sqltime(),
  'Body' => $Body,
  'EditedUserID' => 0,
  'EditedTime' => null
  );
$app->cacheOld->insert('', $Post);
$app->cacheOld->commit_transaction(0);

$app->cacheOld->begin_transaction("thread_$TopicID".'_info');
$app->cacheOld->update_row(false, array('Posts' => '+1', 'LastPostAuthorID' => $app->userNew->core['id']));
$app->cacheOld->commit_transaction(0);


Http::redirect("forums.php?action=viewthread&threadid=$TopicID");
die();
