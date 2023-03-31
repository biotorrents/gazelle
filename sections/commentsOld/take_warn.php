<?php

$app = \Gazelle\App::go();

if (!check_perms('users_warn')) {
    error(404);
}
Http::assertRequest($_POST, array('reason', 'privatemessage', 'body', 'length', 'postid'));

$Reason = $_POST['reason'];
$PrivateMessage = $_POST['privatemessage'];
$Body = $_POST['body'];
$Length = $_POST['length'];
$PostID = (int)$_POST['postid'];

$app->dbOld->query("
  SELECT AuthorID
  FROM comments
  WHERE ID = $PostID");
if (!$app->dbOld->has_results()) {
    error(404);
}
list($AuthorID) = $app->dbOld->next_record();

$UserInfo = User::user_info($AuthorID);
if ($UserInfo['Class'] > $app->user->extra['Class']) {
    error(403);
}

$URL = site_url() . Comments::get_url_query($PostID);
if ($Length !== 'verbal') {
    $Time = (int)$Length * (7 * 24 * 60 * 60);
    Tools::warn_user($AuthorID, $Time, "$URL - $Reason");
    $Subject = 'You have received a warning';
    $PrivateMessage = "You have received a $Length week warning for [url=$URL]this comment[/url].\n\n[quote]{$PrivateMessage}[/quote]";
    $WarnTime = time_plus($Time);
    $AdminComment = date('Y-m-d') . " - Warned until $WarnTime by " . $app->user->core['username'] . "\nReason: $URL - $Reason\n\n";
} else {
    $Subject = 'You have received a verbal warning';
    $PrivateMessage = "You have received a verbal warning for [url=$URL]this comment[/url].\n\n[quote]{$PrivateMessage}[/quote]";
    $AdminComment = date('Y-m-d') . ' - Verbally warned by ' . $app->user->core['username'] . " for $URL\nReason: $Reason\n\n";
    Tools::update_user_notes($AuthorID, $AdminComment);
}
$app->dbOld->query("
  INSERT INTO users_warnings_forums
    (UserID, Comment)
  VALUES
    ('$AuthorID', '" . db_string($AdminComment) . "')
  ON DUPLICATE KEY UPDATE
    Comment = CONCAT('" . db_string($AdminComment) . "', Comment)");
Misc::send_pm($AuthorID, $app->user->core['id'], $Subject, $PrivateMessage);

Comments::edit($PostID, $Body);

Http::redirect("$URL");
