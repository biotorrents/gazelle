<?php

if (!check_perms('users_warn')) {
    error(404);
}
Http::assertRequest($_POST, array('reason', 'privatemessage', 'body', 'length', 'postid'));

$Reason = $_POST['reason'];
$PrivateMessage = $_POST['privatemessage'];
$Body = $_POST['body'];
$Length = $_POST['length'];
$PostID = (int)$_POST['postid'];

$db->query("
  SELECT AuthorID
  FROM comments
  WHERE ID = $PostID");
if (!$db->has_results()) {
    error(404);
}
list($AuthorID) = $db->next_record();

$UserInfo = User::user_info($AuthorID);
if ($UserInfo['Class'] > $user['Class']) {
    error(403);
}

$URL = site_url() . Comments::get_url_query($PostID);
if ($Length !== 'verbal') {
    $Time = (int)$Length * (7 * 24 * 60 * 60);
    Tools::warn_user($AuthorID, $Time, "$URL - $Reason");
    $Subject = 'You have received a warning';
    $PrivateMessage = "You have received a $Length week warning for [url=$URL]this comment[/url].\n\n[quote]{$PrivateMessage}[/quote]";
    $WarnTime = time_plus($Time);
    $AdminComment = date('Y-m-d') . " - Warned until $WarnTime by " . $user['Username'] . "\nReason: $URL - $Reason\n\n";
} else {
    $Subject = 'You have received a verbal warning';
    $PrivateMessage = "You have received a verbal warning for [url=$URL]this comment[/url].\n\n[quote]{$PrivateMessage}[/quote]";
    $AdminComment = date('Y-m-d') . ' - Verbally warned by ' . $user['Username'] . " for $URL\nReason: $Reason\n\n";
    Tools::update_user_notes($AuthorID, $AdminComment);
}
$db->query("
  INSERT INTO users_warnings_forums
    (UserID, Comment)
  VALUES
    ('$AuthorID', '" . db_string($AdminComment) . "')
  ON DUPLICATE KEY UPDATE
    Comment = CONCAT('" . db_string($AdminComment) . "', Comment)");
Misc::send_pm($AuthorID, $user['ID'], $Subject, $PrivateMessage);

Comments::edit($PostID, $Body);

Http::redirect("$URL");
