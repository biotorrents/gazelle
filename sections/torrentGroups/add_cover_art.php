<?php

$app = \Gazelle\App::go();



if ($app->user->cant(["torrentGroups" => "updateAny"])) {
    error(403);
}

$UserID = $app->user->core['id'];
$GroupID = db_string($_POST['groupid']);
$Summaries = $_POST['summary'];
$Images = $_POST['image'];
$Time = sqltime();

if (!is_numeric($GroupID) || !$GroupID) {
    error(0);
}

if (count($Images) != count($Summaries)) {
    error('Missing an image or a summary');
}

$Changed = false;
for ($i = 0; $i < count($Images); $i++) {
    $Image = $Images[$i];
    $Summary = $Summaries[$i];

    if (!preg_match("/{$app->env->regexImage}/i", $Image)) {
        continue;
    }

    // sanitize inputs
    $Image = db_string($Image);
    $Summary = db_string($Summary);
    $app->dbOld->query("
    INSERT IGNORE INTO cover_art
      (GroupID, Image, Summary, UserID, Time)
    VALUES
      ('$GroupID', '$Image', '$Summary', '$UserID', '$Time')");

    if ($app->dbOld->affected_rows()) {
        $Changed = true;
    }
}

if ($Changed) {
    $app->cache->delete("torrents_cover_art_$GroupID");
}

header('Location: '.$_SERVER['HTTP_REFERER']);
