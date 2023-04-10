<?php

declare(strict_types=1);


/**
 * friends page
 */

$app = \Gazelle\App::go();

$friends = \Gazelle\Friends::read();
#!d($friends);exit;

# twig template
$app->twig->display("user/friends.twig", [
    "title" => "Friends",
    "js" => ["user"],
    #"sidebar" => true,

    "friends" => $friends,
    "error" => $error ?? null,
]);
