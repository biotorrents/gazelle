<?php

$app = Gazelle\App::go();



if ($app->user->cant(["admin" => "reports"]) && !check_perms('project_team') && $app->user->cant(["admin" => "moderateForums"])) {
    error(403);
}

$ReportID = (int) $_POST['reportid'];

$app->dbOld->query("
  SELECT Type
  FROM reports
  WHERE ID = $ReportID");
list($Type) = $app->dbOld->next_record();
if ($app->user->cant(["admin" => "reports"])) {
    if ($app->user->can(["admin" => "moderateForums"])) {
        if (!in_array($Type, array('comment', 'post', 'thread'))) {
            error($Type);
        }
    } elseif (check_perms('project_team')) {
        if ($Type != 'request_update') {
            error(403);
        }
    }
}

$app->dbOld->query("
  UPDATE reports
  SET Status = 'Resolved',
    ResolvedTime = NOW(),
    ResolverID = ?
  WHERE ID = ?", $app->user->core['id'], $app->user->core['id']);

$Channels = [];

if ($Type == 'request_update') {
    $Channels[] = '#requestedits';
    $app->cache->decrement('num_update_reports');
}

if (in_array($Type, array('comment', 'post', 'thread'))) {
    $Channels[] = '#forumreports';
    $app->cache->decrement('num_forum_reports');
}

$app->dbOld->query("
  SELECT COUNT(ID)
  FROM reports
  WHERE Status = 'New'");
list($Remaining) = $app->dbOld->next_record();

send_irc($Channels, "Report $ReportID resolved by " . preg_replace('/^(.{2})/', '$1·', $app->user->core['username']) . ' on site (' . (int) $Remaining . ' remaining).');
$app->cache->delete('num_other_reports');
Gazelle\Http::redirect("reports.php");
