<?php

$app = \Gazelle\App::go();

authorize();

if (!check_perms('admin_reports') && !check_perms('project_team') && !check_perms('site_moderate_forums')) {
    ajax_error();
}

$ReportID = (int) $_POST['reportid'];

$app->dbOld->query("
  SELECT Type
  FROM reports
  WHERE ID = $ReportID");
list($Type) = $app->dbOld->next_record();
if (!check_perms('admin_reports')) {
    if (check_perms('site_moderate_forums')) {
        if (!in_array($Type, array('comment', 'post', 'thread'))) {
            ajax_error();
        }
    } elseif (check_perms('project_team')) {
        if ($Type != 'request_update') {
            ajax_error();
        }
    }
}

$app->dbOld->query("
  UPDATE reports
  SET Status = 'Resolved',
    ResolvedTime = NOW(),
    ResolverID = '".$app->userNew->core['id']."'
  WHERE ID = '".db_string($ReportID)."'");

$Channels = [];

if ($Type == 'request_update') {
    $Channels[] = '#requestedits';
    $app->cacheOld->decrement('num_update_reports');
}

if (in_array($Type, array('comment', 'post', 'thread'))) {
    $Channels[] = '#forumreports';
    $app->cacheOld->decrement('num_forum_reports');
}

$app->dbOld->query("
  SELECT COUNT(ID)
  FROM reports
  WHERE Status = 'New'");
list($Remaining) = $app->dbOld->next_record();

send_irc([$Channels], "Report $ReportID resolved by ".preg_replace('/^(.{2})/', '$1·', $app->userNew->core['username']).' on site ('.(int) $Remaining.' remaining).');
$app->cacheOld->delete_value('num_other_reports');
ajax_success();

function ajax_error($Error = 'error')
{
    echo json_encode(array('status' => $Error));
    die();
}

function ajax_success()
{
    echo json_encode(array('status' => 'success'));
    die();
}
