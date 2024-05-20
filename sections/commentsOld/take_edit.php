<?php



if (!isset($_POST['postid']) || !is_numeric($_POST['postid']) || !isset($_POST['body'])) {
    error(0);
}

if ($app->user->extra['DisablePosting']) {
    error('Your posting privileges have been removed.');
}

$SendPM = isset($_POST['pm']) && $_POST['pm'];
Comments::edit((int)$_POST['postid'], $_POST['body'], $SendPM);

// This gets sent to the browser, which echoes it in place of the old body
echo \Gazelle\Text::parse($_POST['body']);
