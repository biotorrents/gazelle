<?php

#declare(strict_types=1);

$app = \Gazelle\App::go();

authorize();
$ENV = ENV::go();

// todo: Remove all the stupid queries that could get their information just as easily from the cache
/*********************************************************************\
//--------------Take Post--------------------------------------------//

This page takes a forum post submission, validates it, and
enters it into the database. The user is then redirected to their
post.

$_POST['action'] is what the user is trying to do. It can be:

'reply' if the user is replying to a thread
  It will be accompanied with:
  $_POST['thread']
  $_POST['body']


\*********************************************************************/

// If you're not sending anything, go back
if (empty($_POST['body']) || !isset($_POST['body'])) {
    #if ($_POST['body'] === '' || !isset($_POST['body'])) {
    header('Location: '.$_SERVER['HTTP_REFERER']);
    error();
}

if (!empty($app->user->extra['DisablePosting'])) {
    error('Your posting privileges have been removed.');
}

$PerPage = $app->user->extra['PostsPerPage'] ?? POSTS_PER_PAGE;
$Body = $_POST['body'];
$TopicID = $_POST['thread'];
$ThreadInfo = Forums::get_thread_info($TopicID);

if ($ThreadInfo === null) {
    error(404);
}

$ForumID = $ThreadInfo['ForumID'];
$SQLTime = sqltime();

if (!Forums::check_forumperm($ForumID)) {
    error(403);
}

if (!Forums::check_forumperm($ForumID, 'Write') || $app->user->extra['DisablePosting'] || $ThreadInfo['IsLocked'] == '1' && !check_perms('site_moderate_forums')) {
    error(403);
}

if (strlen($Body) > 500000) {
    error('Your post is too large');
}

if (isset($_POST['subscribe']) && Subscriptions::has_subscribed($TopicID) === false) {
    Subscriptions::subscribe($TopicID);
}

// Now lets handle the special case of merging posts, we can skip bumping the thread and all that fun
if ($ThreadInfo['LastPostAuthorID'] == $app->user->core['id'] && ((!check_perms('site_forums_double_post') || isset($_POST['merge'])))) {
    // Get the id for this post in the database to append
    $app->dbOld->query("
    SELECT ID, Body
    FROM forums_posts
    WHERE TopicID = ?
      AND AuthorID = ?
    ORDER BY ID DESC
    LIMIT 1", $TopicID, $app->user->core['id']);
    list($PostID, $OldBody) = $app->dbOld->next_record(MYSQLI_NUM, false);

    //Edit the post
    $app->dbOld->query("
    UPDATE forums_posts
    SET
      Body = CONCAT(Body, '\n\n', ?),
      EditedUserID = ?,
      EditedTime = ?
    WHERE ID = ?", $Body, $app->user->core['id'], $SQLTime, $PostID);

    //Store edit history
    $app->dbOld->query("
    INSERT INTO comments_edits
      (Page, PostID, EditUser, EditTime, Body)
    VALUES
      ('forums', ?, ?, ?, ?)", $PostID, $app->user->core['id'], $SQLTime, $OldBody);
    $app->cacheNew->delete("forums_edits_$PostID");

    //Get the catalogue it is in
    $CatalogueID = floor((POSTS_PER_PAGE * ceil($ThreadInfo['Posts'] / POSTS_PER_PAGE) - POSTS_PER_PAGE) / THREAD_CATALOGUE);

    //Get the catalogue value for the post we're appending to
    if ($ThreadInfo['Posts'] % THREAD_CATALOGUE == 0) {
        $Key = THREAD_CATALOGUE - 1;
    } else {
        $Key = ($ThreadInfo['Posts'] % THREAD_CATALOGUE) - 1;
    }
    if ($ThreadInfo['StickyPostID'] == $PostID) {
        $ThreadInfo['StickyPost']['Body'] .= "\n\n".$Body;
        $ThreadInfo['StickyPost']['EditedUserID'] = $app->user->core['id'];
        $ThreadInfo['StickyPost']['EditedTime'] = $SQLTime;
        $app->cacheNew->set("thread_$TopicID".'_info', $ThreadInfo, 0);
    }

    //Edit the post in the cache
    $app->cacheOld->begin_transaction("thread_$TopicID"."_catalogue_$CatalogueID");
    $app->cacheOld->update_row($Key, [
    'Body' => $app->cacheOld->MemcacheDBArray[$Key]['Body']."\n\n$Body",
    'EditedUserID' => $app->user->core['id'],
    'EditedTime' => $SQLTime,
    'Username' => $app->user->core['username']
  ]);
    $app->cacheOld->commit_transaction(0);

//Now we're dealing with a normal post
} else {
    //Insert the post into the posts database
    $app->dbOld->query(
        "
    INSERT INTO forums_posts (TopicID, AuthorID, AddedTime, Body)
    VALUES (?, ?, ?, ?)",
        $TopicID,
        $app->user->core['id'],
        $SQLTime,
        $Body
    );

    $PostID = $app->dbOld->inserted_id();

    //This updates the root index
    $app->dbOld->query("
    UPDATE forums
    SET
      NumPosts = NumPosts + 1,
      LastPostID = ?,
      LastPostAuthorID = ?,
      LastPostTopicID = ?,
      LastPostTime = ?
    WHERE ID = ?", $PostID, $app->user->core['id'], $TopicID, $SQLTime, $ForumID);

    //Update the topic
    $app->dbOld->query("
    UPDATE forums_topics
    SET
      NumPosts = NumPosts + 1,
      LastPostID = ?,
      LastPostAuthorID = ?,
      LastPostTime = ?
    WHERE ID = ?", $PostID, $app->user->core['id'], $SQLTime, $TopicID);

    // if cache exists modify it, if not, then it will be correct when selected next, and we can skip this block
    if ($Forum = $app->cacheNew->get("forums_$ForumID")) {
        list($Forum, , , $Stickies) = $Forum;

        // if the topic is already on this page
        if (array_key_exists($TopicID, $Forum)) {
            $Thread = $Forum[$TopicID];
            unset($Forum[$TopicID]);
            $Thread['NumPosts'] = $Thread['NumPosts'] + 1; // Increment post count
      $Thread['LastPostID'] = $PostID; // Set post ID for read/unread
      $Thread['LastPostTime'] = $SQLTime; // Time of last post
      $Thread['LastPostAuthorID'] = $app->user->core['id']; // Last poster ID
      $Part2 = [$TopicID => $Thread]; // Bumped thread

    // if we're bumping from an older page
        } else {
            // Remove the last thread from the index
            if (count($Forum) == TOPICS_PER_PAGE && $Stickies < TOPICS_PER_PAGE) {
                array_pop($Forum);
            }
            // Never know if we get a page full of stickies...
            if ($Stickies < TOPICS_PER_PAGE || $ThreadInfo['IsSticky'] == 1) {
                //Pull the data for the thread we're bumping
                $app->dbOld->query("
          SELECT
            f.AuthorID,
            f.IsLocked,
            f.IsSticky,
            f.NumPosts,
            ISNULL(p.TopicID) AS NoPoll
          FROM forums_topics AS f
            LEFT JOIN forums_polls AS p ON p.TopicID = f.ID
          WHERE f.ID = ?", $TopicID);
                list($AuthorID, $IsLocked, $IsSticky, $NumPosts, $NoPoll) = $app->dbOld->next_record();
                $Part2 = [
          $TopicID => [
            'ID'               => $TopicID,
            'Title'            => $ThreadInfo['Title'],
            'AuthorID'         => $AuthorID,
            'IsLocked'         => $IsLocked,
            'IsSticky'         => $IsSticky,
            'NumPosts'         => $NumPosts,
            'LastPostID'       => $PostID,
            'LastPostTime'     => $SQLTime,
            'LastPostAuthorID' => $app->user->core['id'],
            'NoPoll'           => $NoPoll
          ]
        ]; //Bumped
            } else {
                $Part2 = [];
            }
        }
        if ($Stickies > 0) {
            $Part1 = array_slice($Forum, 0, $Stickies, true); //Stickies
      $Part3 = array_slice($Forum, $Stickies, TOPICS_PER_PAGE - $Stickies - 1, true); //Rest of page
        } else {
            $Part1 = [];
            $Part3 = $Forum;
        }
        if (is_null($Part1)) {
            $Part1 = [];
        }
        if (is_null($Part3)) {
            $Part3 = [];
        }
        if ($ThreadInfo['IsSticky'] == 1) {
            $Forum = $Part2 + $Part1 + $Part3; //Merge it
        } else {
            $Forum = $Part1 + $Part2 + $Part3; //Merge it
        }
        $app->cacheNew->set("forums_$ForumID", [$Forum, '', 0, $Stickies], 0);

        //Update the forum root
        $app->cacheOld->begin_transaction('forums_list');
        $app->cacheOld->update_row($ForumID, [
      'NumPosts'         => '+1',
      'LastPostID'       => $PostID,
      'LastPostAuthorID' => $app->user->core['id'],
      'LastPostTopicID'  => $TopicID,
      'LastPostTime'     => $SQLTime,
      'Title'            => $ThreadInfo['Title'],
      'IsLocked'         => $ThreadInfo['IsLocked'],
      'IsSticky'         => $ThreadInfo['IsSticky']
    ]);
        $app->cacheOld->commit_transaction(0);
    } else {
        //If there's no cache, we have no data, and if there's no data
        $app->cacheNew->delete('forums_list');
    }


    //This calculates the block of 500 posts that this one will fall under
    $CatalogueID = floor((POSTS_PER_PAGE * ceil($ThreadInfo['Posts'] / POSTS_PER_PAGE) - POSTS_PER_PAGE) / THREAD_CATALOGUE);

    //Insert the post into the thread catalogue (block of 500 posts)
    $app->cacheOld->begin_transaction("thread_$TopicID"."_catalogue_$CatalogueID");
    $app->cacheOld->insert('', [
    'ID'           => $PostID,
    'AuthorID'     => $app->user->core['id'],
    'AddedTime'    => $SQLTime,
    'Body'         => $Body,
    'EditedUserID' => 0,
    'EditedTime'   => null,
    'Username'     => $app->user->core['username'] // todo: Remove, it's never used?
  ]);
    $app->cacheOld->commit_transaction(0);

    //Update the thread info
    $app->cacheOld->begin_transaction("thread_$TopicID".'_info');
    $app->cacheOld->update_row(false, ['Posts' => '+1', 'LastPostAuthorID' => $app->user->core['id']]);
    $app->cacheOld->commit_transaction(0);

    //Increment this now to make sure we redirect to the correct page
    $ThreadInfo['Posts']++;

    //Award a badge if necessary
    $app->dbOld->query("
    SELECT COUNT(ID)
    FROM forums_posts
    WHERE AuthorID = '{$app->user->core['id']}'");
    list($UserPosts) = $app->dbOld->next_record(MYSQLI_NUM, false);
    foreach ($ENV->AUTOMATED_BADGE_IDS->Posts as $Count => $Badge) {
        if ((int) $UserPosts >= $Count) {
            $Success = Badges::awardBadge($app->user->core['id'], $Badge);
            if ($Success) {
                Misc::send_pm($app->user->core['id'], 0, 'You have received a badge!', "You have received a badge for making ".$Count." forum posts.\n\nIt can be enabled from your user settings.");
            }
        }
    }
}

Subscriptions::flush_subscriptions('forums', $TopicID);
Subscriptions::quote_notify($Body, $PostID, 'forums', $TopicID);

header("Location: forums.php?action=viewthread&threadid=$TopicID&page=".ceil($ThreadInfo['Posts'] / $PerPage));
die();
