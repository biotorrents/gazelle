<?php

#declare(strict_types=1);

$app = \Gazelle\App::go();

/**
 * Input validation
 */

authorize();

$group_id = (int) $_POST['groupid'];
Security::int($group_id);

$NewTitle = $_POST['name'];
$NewSubject = $_POST['Title2'];
$NewObject = $_POST['namejp'];

$app->dbOld->prepared_query("
SELECT
  `ID`
FROM
  `torrents`
WHERE
  `GroupID` = '$group_id' AND `UserID` = '{$app->user->core['id']}'
");


$Contributed = $app->dbOld->has_results();
if (!($Contributed || check_perms('torrents_edit'))) {
    error(403);
}

# NewSubject, NewObject optional
if (empty($NewTitle)) {
    error('Torrent groups must have a name');
}

$app->dbOld->prepared_query("
UPDATE
  `torrents_group`
SET
  `title` = '$NewTitle',
  `subject` = '$NewSubject',
  `object` = '$NewObject'
WHERE
  `id` = '$group_id'
");


$app->cache->delete("torrents_details_$group_id");
Torrents::update_hash($group_id);

$app->dbOld->query("
SELECT
  `title`,
  `subject`,
  `object`
FROM
  `torrents_group`
WHERE
  `id` = '$group_id'
");
list($OldTitle, $OldSubject, $OldObject) = $app->dbOld->next_record(MYSQLI_NUM, false);

# Map metadata over generic database fields
# todo: Work into $ENV in classes/config.php
$Title1 = 'Title';
$Title2 = 'Organism';
$Title3 = 'Strain/Variety';

if ($OldTitle !== $NewTitle) {
    Misc::write_log("Torrent Group $group_id ($OldTitle)'s $Title1 was changed to '$NewTitle' from '$OldTitle' by ".$app->user->core['username']);
    Torrents::write_group_log($group_id, 0, $app->user->core['id'], "$Title1 changed to '$NewTitle' from '$OldTitle'", 0);
}

if ($OldSubject !== $NewSubject) {
    Misc::write_log("Torrent Group $group_id ($OldSubject)'s $Title2 was changed to '$NewSubject' from '$OldSubject' by ".$app->user->core['username']);
    Torrents::write_group_log($group_id, 0, $app->user->core['id'], "$Title2 changed to '$NewSubject' from '$OldSubject'", 0);
}

if ($OldObject !== $NewObject) {
    Misc::write_log("Torrent Group $group_id ($OldObject)'s $Title3 was changed to '$NewObject' from '$OldObject' by ".$app->user->core['username']);
    Torrents::write_group_log($group_id, 0, $app->user->core['id'], "$Title3 changed to '$NewObject' from '$OldObject'", 0);
}

Http::redirect("torrents.php?id=$group_id");
