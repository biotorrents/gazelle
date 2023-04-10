<?php

$app = \Gazelle\App::go();

if (!check_perms('site_admin_forums')) {
    error(403);
}

if (empty($_GET['postid']) || !is_numeric($_GET['postid'])) {
    error();
}

$PostID = $_GET['postid'];

if (!isset($_GET['depth']) || !is_numeric($_GET['depth'])) {
    error();
}

$Depth = $_GET['depth'];

if (empty($_GET['type']) || !in_array($_GET['type'], array('forums', 'collages', 'requests', 'torrents', 'artist'))) {
    error();
}
$Type = $_GET['type'];

$Edits = $app->cache->get($Type.'_edits_'.$PostID);
if (!is_array($Edits)) {
    $app->dbOld->query("
    SELECT EditUser, EditTime, Body
    FROM comments_edits
    WHERE Page = '$Type' AND PostID = $PostID
    ORDER BY EditTime DESC");
    $Edits = $app->dbOld->to_array();
    $app->cache->set($Type.'_edits_'.$PostID, $Edits, 0);
}

list($UserID, $Time) = $Edits[$Depth];
if ($Depth != 0) {
    list(, , $Body) = $Edits[$Depth - 1];
} else {
    //Not an edit, have to get from the original
    switch ($Type) {
    case 'forums':
      //Get from normal forum stuffs
      $app->dbOld->query("
        SELECT Body
        FROM forums_posts
        WHERE ID = $PostID");
      list($Body) = $app->dbOld->next_record();
      break;
    case 'collages':
    case 'requests':
    case 'artist':
    case 'torrents':
      $app->dbOld->query("
        SELECT Body
        FROM comments
        WHERE Page = '$Type' AND ID = $PostID");
      list($Body) = $app->dbOld->next_record();
      break;
  }
}
?>
<?=\Gazelle\Text::parse($Body)?>
<br />
<br />

<?php if ($Depth < count($Edits)) { ?>
<a href="#edit_info_<?=$PostID?>"
  onclick="LoadEdit('<?=$Type?>', <?=$PostID?>, <?=($Depth + 1)?>); return false;">&laquo;</a>
<?=(($Depth == 0) ? 'Last edited by' : 'Edited by')?>
<?=User::format_username($UserID, false, false, false) ?> <?=time_diff($Time, 2, true, true)?>
<?php } else { ?>
<em>Original Post</em>
<?php }

if ($Depth > 0) { ?>
<a href="#edit_info_<?=$PostID?>"
  onclick="LoadEdit('<?=$Type?>', <?=$PostID?>, <?=($Depth - 1)?>); return false;">&raquo;</a>
<?php }
