<?php

authorize();

if (!check_perms('admin_reports') && !check_perms('project_team') && !check_perms('site_moderate_forums')) {
    ajax_error();
}

$ReportID = (int) $_POST['reportid'];

$db->query("
  SELECT Type
  FROM reports
  WHERE ID = $ReportID");
list($Type) = $db->next_record();
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

$db->query("
  UPDATE reports
  SET Status = 'Resolved',
    ResolvedTime = NOW(),
    ResolverID = '".$user['ID']."'
  WHERE ID = '".db_string($ReportID)."'");

$Channels = [];

if ($Type == 'request_update') {
    $Channels[] = '#requestedits';
    $cache->decrement('num_update_reports');
}

if (in_array($Type, array('comment', 'post', 'thread'))) {
    $Channels[] = '#forumreports';
    $cache->decrement('num_forum_reports');
}

$db->query("
  SELECT COUNT(ID)
  FROM reports
  WHERE Status = 'New'");
list($Remaining) = $db->next_record();

send_irc([$Channels], "Report $ReportID resolved by ".preg_replace('/^(.{2})/', '$1·', $user['Username']).' on site ('.(int) $Remaining.' remaining).');
$cache->delete_value('num_other_reports');
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
