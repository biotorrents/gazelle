<?php

$app = \Gazelle\App::go();

if (!check_perms('users_warn')) {
    error(404);
}
Http::assertRequest($_POST, array('reason', 'privatemessage', 'body', 'length', 'postid', 'userid'));

$Reason = $_POST['reason'];
$PrivateMessage = $_POST['privatemessage'];
$Body = $_POST['body'];
$WarningLength = $_POST['length'];
$PostID = (int)$_POST['postid'];
$UserID = (int)$_POST['userid'];
$Key = (int)$_POST['key'];
$SQLTime = sqltime();

$UserInfo = User::user_info($UserID);
if ($UserInfo['Class'] > $app->userNew->extra['Class']) {
    error(403);
}

$URL = site_url() . "forums.php?action=viewthread&amp;postid=$PostID#post$PostID";
if ($WarningLength !== 'verbal') {
    $Time = (int)$WarningLength * (7 * 24 * 60 * 60);
    Tools::warn_user($UserID, $Time, "$URL - $Reason");
    $Subject = 'You have received a warning';
    $PrivateMessage = "You have received a $WarningLength week warning for [url=$URL]this post[/url].\n\n" . $PrivateMessage;

    $WarnTime = time_plus($Time);
    $AdminComment = date('Y-m-d') . " - Warned until $WarnTime by " . $app->userNew->core['username'] . " for $URL\nReason: $Reason\n\n";
} else {
    $Subject = 'You have received a verbal warning';
    $PrivateMessage = "You have received a verbal warning for [url=$URL]this post[/url].\n\n" . $PrivateMessage;
    $AdminComment = date('Y-m-d') . ' - Verbally warned by ' . $app->userNew->core['username'] . " for $URL\nReason: $Reason\n\n";
    Tools::update_user_notes($UserID, $AdminComment);
}

$app->dbOld->prepared_query("
  INSERT INTO users_warnings_forums
    (UserID, Comment)
  VALUES
    ('$UserID', '" . db_string($AdminComment) . "')
  ON DUPLICATE KEY UPDATE
    Comment = CONCAT('" . db_string($AdminComment) . "', Comment)");
Misc::send_pm($UserID, $app->userNew->core['id'], $Subject, $PrivateMessage);

//edit the post
$app->dbOld->prepared_query("
  SELECT
    p.Body,
    p.AuthorID,
    p.TopicID,
    t.ForumID,
    CEIL(
      (
        SELECT COUNT(p2.ID)
        FROM forums_posts AS p2
        WHERE p2.TopicID = p.TopicID
          AND p2.ID <= '$PostID'
      ) / " . POSTS_PER_PAGE . "
    ) AS Page
  FROM forums_posts AS p
    JOIN forums_topics AS t ON p.TopicID = t.ID
    JOIN forums AS f ON t.ForumID = f.ID
  WHERE p.ID = '$PostID'");
list($OldBody, $AuthorID, $TopicID, $ForumID, $Page) = $app->dbOld->next_record();

// Perform the update
$app->dbOld->prepared_query("
  UPDATE forums_posts
  SET Body = '" . db_string($Body) . "',
    EditedUserID = '$UserID',
    EditedTime = '$SQLTime'
  WHERE ID = '$PostID'");

$CatalogueID = floor((POSTS_PER_PAGE * $Page - POSTS_PER_PAGE) / THREAD_CATALOGUE);
$app->cacheOld->begin_transaction("thread_$TopicID" . "_catalogue_$CatalogueID");
if ($app->cacheOld->MemcacheDBArray[$Key]['ID'] != $PostID) {
    $app->cacheOld->cancel_transaction();
    $app->cacheOld->delete_value("thread_$TopicID" . "_catalogue_$CatalogueID");
//just clear the cache for would be cache-screwer-uppers
} else {
    $app->cacheOld->update_row($Key, array(
            'ID' => $app->cacheOld->MemcacheDBArray[$Key]['ID'],
            'AuthorID' => $app->cacheOld->MemcacheDBArray[$Key]['AuthorID'],
            'AddedTime' => $app->cacheOld->MemcacheDBArray[$Key]['AddedTime'],
            'Body' => $Body, //Don't url decode.
            'EditedUserID' => $app->userNew->core['id'],
            'EditedTime' => $SQLTime,
            'Username' => $app->userNew->core['username']));
    $app->cacheOld->commit_transaction(3600 * 24 * 5);
}
$ThreadInfo = Forums::get_thread_info($TopicID);
if ($ThreadInfo === null) {
    error(404);
}
if ($ThreadInfo['StickyPostID'] == $PostID) {
    $ThreadInfo['StickyPost']['Body'] = $Body;
    $ThreadInfo['StickyPost']['EditedUserID'] = $app->userNew->core['id'];
    $ThreadInfo['StickyPost']['EditedTime'] = $SQLTime;
    $app->cacheOld->cache_value("thread_$TopicID" . '_info', $ThreadInfo, 0);
}

$app->dbOld->prepared_query("
  INSERT INTO comments_edits
    (Page, PostID, EditUser, EditTime, Body)
  VALUES
    ('forums', $PostID, $UserID, '$SQLTime', '" . db_string($OldBody) . "')");
$app->cacheOld->delete_value("forums_edits_$PostID");

Http::redirect("forums.php?action=viewthread&postid=$PostID#post$PostID");
