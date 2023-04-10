<?php

$app = \Gazelle\App::go();

authorize();

/*********************************************************************\
//--------------Take Post--------------------------------------------//

The page that handles the backend of the 'edit post' function.

$_GET['action'] must be "takeedit" for this page to work.

It will be accompanied with:
  $_POST['post'] - the ID of the post
  $_POST['body']


\*********************************************************************/

// Quick SQL injection check
if (!$_POST['post'] || !is_numeric($_POST['post']) || !is_numeric($_POST['key'])) {
    error(0, true);
}
// End injection check

if ($app->user->extra['DisablePosting']) {
    error('Your posting privileges have been removed.');
}

// Variables for database input
$UserID = $app->user->core['id'];
$Body = $_POST['body']; //Don't URL Decode
$PostID = $_POST['post'];
$Key = $_POST['key'];
$SQLTime = sqltime();
$DoPM = isset($_POST['pm']) ? $_POST['pm'] : 0;

// Mainly
$app->dbOld->query("
  SELECT
    p.Body,
    p.AuthorID,
    p.TopicID,
    t.IsLocked,
    t.ForumID,
    f.MinClassWrite,
    CEIL((
      SELECT COUNT(p2.ID)
      FROM forums_posts AS p2
      WHERE p2.TopicID = p.TopicID
        AND p2.ID <= '$PostID'
      ) / ".POSTS_PER_PAGE."
    ) AS Page
  FROM forums_posts AS p
    JOIN forums_topics AS t ON p.TopicID = t.ID
    JOIN forums AS f ON t.ForumID = f.ID
  WHERE p.ID = '$PostID'");
list($OldBody, $AuthorID, $TopicID, $IsLocked, $ForumID, $MinClassWrite, $Page) = $app->dbOld->next_record();


// Make sure they aren't trying to edit posts they shouldn't
if (!Forums::check_forumperm($ForumID, 'Write') || ($IsLocked && !check_perms('site_moderate_forums'))) {
    error('Either the thread is locked, or you lack the permission to edit this post.', true);
}
if ($UserID != $AuthorID && !check_perms('site_moderate_forums')) {
    error(403, true);
}
if ($app->user->extra['DisablePosting']) {
    error('Your posting privileges have been removed.', true);
}
if (!$app->dbOld->has_results()) {
    error(404, true);
}

// Send a PM to the user to notify them of the edit
if ($UserID != $AuthorID && $DoPM) {
    $PMSubject = "Your post #$PostID has been edited";
    $PMurl = site_url()."forums.php?action=viewthread&postid=$PostID#post$PostID";
    $ProfLink = '[url='.site_url()."user.php?id=$UserID]".$app->user->core['username'].'[/url]';
    $PMBody = "One of your posts has been edited by $ProfLink: [url]{$PMurl}[/url]";
    Misc::send_pm($AuthorID, 0, $PMSubject, $PMBody);
}

// Perform the update
$app->dbOld->query("
  UPDATE forums_posts
  SET
    Body = '" . db_string($Body) . "',
    EditedUserID = '$UserID',
    EditedTime = '$SQLTime'
  WHERE ID = '$PostID'");

/*
$CatalogueID = floor((POSTS_PER_PAGE * $Page - POSTS_PER_PAGE) / THREAD_CATALOGUE);
$app->cacheOld->begin_transaction("thread_$TopicID"."_catalogue_$CatalogueID");
if ($app->cacheOld->MemcacheDBArray[$Key]['ID'] != $PostID) {
  $app->cacheOld->cancel_transaction();
  $app->cache->delete("thread_$TopicID"."_catalogue_$CatalogueID"); //just clear the cache for would be cache-screwer-uppers
} else {
  $app->cacheOld->update_row($Key, array(
    'ID'=>$app->cacheOld->MemcacheDBArray[$Key]['ID'],
    'AuthorID'=>$app->cacheOld->MemcacheDBArray[$Key]['AuthorID'],
    'AddedTime'=>$app->cacheOld->MemcacheDBArray[$Key]['AddedTime'],
    'Body'=>$Body, //Don't url decode.
    'EditedUserID'=>$app->user->core['id'],
    'EditedTime'=>$SQLTime,
    'Username'=>$app->user->core['username']
    ));
  $app->cacheOld->commit_transaction(3600 * 24 * 5);
}
*/

$ThreadInfo = Forums::get_thread_info($TopicID);
if ($ThreadInfo === null) {
    error(404);
}
if ($ThreadInfo['StickyPostID'] == $PostID) {
    $ThreadInfo['StickyPost']['Body'] = $Body;
    $ThreadInfo['StickyPost']['EditedUserID'] = $app->user->core['id'];
    $ThreadInfo['StickyPost']['EditedTime'] = $SQLTime;
    $app->cache->set("thread_$TopicID".'_info', $ThreadInfo, 0);
}

$app->dbOld->query("
  INSERT INTO comments_edits
    (Page, PostID, EditUser, EditTime, Body)
  VALUES
    ('forums', $PostID, $UserID, '$SQLTime', '".db_string($OldBody)."')");
$app->cache->delete("forums_edits_$PostID");
// This gets sent to the browser, which echoes it in place of the old body
echo \Gazelle\Text::parse($Body);
?>
<br /><br />
<div class="last_edited">Last edited by <a
    href="user.php?id=<?=$app->user->core['id']?>"><?=$app->user->core['username']?></a> Just now</div>