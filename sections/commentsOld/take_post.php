<?php

declare(strict_types=1);


/**
 * comment backend
 */

$app = \Gazelle\App::go();



if (!isset($_REQUEST['page'])
    || !in_array($_REQUEST['page'], array('artist', 'collages', 'requests', 'torrents'))
    || !isset($_POST['pageid'])
    || !is_numeric($_POST['pageid'])
    || !isset($_POST['body'])
    || trim($_POST['body']) === '') {
    error(0);
}

/*
if ($app->user->cant(["messages" => "create"])) {
    error("Your posting privileges have been removed.");
}
*/

$Page = $_REQUEST['page'];
$PageID = (int)$_POST['pageid'];
if (!$PageID) {
    error(404);
}

if (isset($_POST['subscribe']) && Subscriptions::has_subscribed_comments($Page, $PageID) === false) {
    Subscriptions::subscribe_comments($Page, $PageID);
}

$PostID = Comments::post($Page, $PageID, $_POST['body']);

header("Location: " . Comments::get_url($Page, $PageID, $PostID));
die();
