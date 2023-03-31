<?php

declare(strict_types=1);


/**
 * main feeds page
 *
 * The feeds don't use bootstrap/app.php,
 * their code resides entirely in feeds.php in the document root.
 * Bear this in mind when you try to use bootstrap functions.
 */

$app = \Gazelle\App::go();

if (empty($_GET["feed"])
  || empty($_GET["authkey"])
  || empty($_GET["auth"])
  || empty($_GET["passkey"])
  || empty($_GET["user"])
  || !is_numeric($_GET["user"])
  || strlen($_GET["authkey"]) !== 32
  || strlen($_GET["passkey"]) !== 32
  || strlen($_GET["auth"]) !== 32
) {
    $feed->open();
    $feed->channel("Blocked", "RSS feed");
    $feed->close();
    error(400, $noHtml = true);
}


$userId = intval($_GET["user"]);
$enabled = $app->cacheNew->get("enabled_{$userId}");

if (!$enabled) {
    $app->dbOld->query("select enabled from users_main where id = {$userId}");

    list($enabled) = $app->dbOld->next_record();
    $app->cacheNew->set("enabled_{$userId}", $enabled, 0);
}

# check for RSS auth
$rssHash = md5($userId . $app->env->getPriv("rssHash") . $_GET["passkey"]);
if ($rssHash !== $_GET["auth"] || intval($enabled) !== 1) {
    $feed->open();
    $feed->channel("Blocked", "RSS feed");
    $feed->close();
    error(400, $noHtml = true);
}
