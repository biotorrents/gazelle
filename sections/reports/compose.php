<?php

$app = \Gazelle\App::go();

if (!check_perms('admin_reports')) {
    error(403);
}

if (empty($Return)) {
    $ToID = $_GET['to'];
    if ($ToID == $app->user->core['id']) {
        error("You cannot start a conversation with yourself!");
        Http::redirect("inbox.php");
    }
}

if (!$ToID || !is_numeric($ToID)) {
    error(404);
}

$ReportID = $_GET['reportid'];
$Type = $_GET['type'];
$ThingID = $_GET['thingid'];

if (!$ReportID || !is_numeric($ReportID) || !$ThingID || !is_numeric($ThingID) || !$Type) {
    error(403);
}

if (!empty($app->user->extra['DisablePM']) && !isset($StaffIDs[$ToID])) {
    error(403);
}

$app->dbOld->query("
  SELECT Username
  FROM users_main
  WHERE ID = ?", $ToID);
list($ComposeToUsername) = $app->dbOld->next_record();
if (!$ComposeToUsername) {
    error(404);
}
View::header('Compose', 'inbox');

// $TypeLink is placed directly in the <textarea> when composing a PM
switch ($Type) {
  case 'user':
    $app->dbOld->query("
      SELECT Username
      FROM users_main
      WHERE ID = ?", $ThingID);
    if (!$app->dbOld->has_results()) {
        $Error = 'No user with the reported ID found';
    } else {
        list($Username) = $app->dbOld->next_record();
        $TypeLink = "the user [user]{$Username}[/user]";
        $Subject = 'User Report: '.\Gazelle\Text::esc($Username);
    }
    break;
  case 'request':
  case 'request_update':
    $app->dbOld->query("
      SELECT Title
      FROM requests
      WHERE ID = ?", $ThingID);
    if (!$app->dbOld->has_results()) {
        $Error = 'No request with the reported ID found';
    } else {
        list($Name) = $app->dbOld->next_record();
        $TypeLink = 'the request [url='.site_url()."requests.php?action=view&amp;id=$ThingID]".\Gazelle\Text::esc($Name).'[/url]';
        $Subject = 'Request Report: '.\Gazelle\Text::esc($Name);
    }
    break;
  case 'collage':
    $app->dbOld->query("
      SELECT Name
      FROM collages
      WHERE ID = ?", $ThingID);
    if (!$app->dbOld->has_results()) {
        $Error = 'No collage with the reported ID found';
    } else {
        list($Name) = $app->dbOld->next_record();
        $TypeLink = 'the collage [url='.site_url()."collage.php?id=$ThingID]".\Gazelle\Text::esc($Name).'[/url]';
        $Subject = 'Collage Report: '.\Gazelle\Text::esc($Name);
    }
    break;
  case 'thread':
    $app->dbOld->query("
      SELECT Title
      FROM forums_topics
      WHERE ID = ?", $ThingID);
    if (!$app->dbOld->has_results()) {
        $Error = 'No forum thread with the reported ID found';
    } else {
        list($Title) = $app->dbOld->next_record();
        $TypeLink = 'the forum thread [url='.site_url()."forums.php?action=viewthread&amp;threadid=$ThingID]".\Gazelle\Text::esc($Title).'[/url]';
        $Subject = 'Forum Thread Report: '.\Gazelle\Text::esc($Title);
    }
    break;
  case 'post':
    if (isset($app->user->extra['PostsPerPage'])) {
        $PerPage = $app->user->extra['PostsPerPage'];
    } else {
        $PerPage = POSTS_PER_PAGE;
    }
    $app->dbOld->query("
      SELECT
        p.ID,
        p.Body,
        p.TopicID,
        (
          SELECT COUNT(p2.ID)
          FROM forums_posts AS p2
          WHERE p2.TopicID = p.TopicID
            AND p2.ID <= p.ID
        ) AS PostNum
      FROM forums_posts AS p
      WHERE p.ID = ?", $ThingID);
    if (!$app->dbOld->has_results()) {
        $Error = 'No forum post with the reported ID found';
    } else {
        list($PostID, $Body, $TopicID, $PostNum) = $app->dbOld->next_record();
        $TypeLink = 'this [url='.site_url()."forums.php?action=viewthread&amp;threadid=$TopicID&amp;post=$PostNum#post$PostID]forum post[/url]";
        $Subject = 'Forum Post Report: Post ID #'.\Gazelle\Text::esc($PostID);
    }
    break;
  case 'comment':
    $app->dbOld->query("
      SELECT 1
      FROM comments
      WHERE ID = ?", $ThingID);
    if (!$app->dbOld->has_results()) {
        $Error = 'No comment with the reported ID found';
    } else {
        $TypeLink = '[url='.site_url()."comments.php?action=jump&amp;postid=$ThingID]this comment[/url]";
        $Subject = 'Comment Report: ID #'.\Gazelle\Text::esc($ThingID);
    }
    break;
  default:
    error('Incorrect type');
    break;
}
if (isset($Error)) {
    error($Error);
}

$app->dbOld->query("
  SELECT Reason
  FROM reports
  WHERE ID = ?", $ReportID);
list($Reason) = $app->dbOld->next_record();

$Body = "You reported $TypeLink for the reason:\n[quote]{$Reason}[/quote]";

?>
<div class="thin">
  <div class="header">
    <h2>
      Send a message to <a href="user.php?id=<?=$ToID?>"> <?=$ComposeToUsername?></a>
    </h2>
  </div>
  <form class="send_form" name="message" action="reports.php" method="post" id="messageform">
    <div class="box pad">
      <input type="hidden" name="action" value="takecompose" />
      <input type="hidden" name="toid" value="<?=$ToID?>" />
      <input type="hidden" name="auth" value="<?=$app->user->extra['AuthKey']?>" />
      <div id="quickpost">
        <h3>Subject</h3>
        <input type="text" name="subject" size="95" value="<?=(!empty($Subject) ? $Subject : '')?>" />
        <br />
        <h3>Body</h3>
        <textarea id="body" name="body" cols="95" rows="10"><?=(!empty($Body) ? $Body : '')?></textarea>
      </div>
      <div id="preview" class="hidden"></div>
      <div id="buttons" class="center">
        <input type="button" value="Preview" onclick="Quick_Preview();" />
        <input type="submit" value="Send message" />
      </div>
    </div>
  </form>
</div>
<?php
View::footer();
?>
