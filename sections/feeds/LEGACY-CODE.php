<?php
declare(strict_types=1);


/** LEGACY ROUTES */


// Main feeds page
//
// The feeds don"t use bootstrap/app.php, their code resides entirely in feeds.php in the document root.
// Bear this in mind when you try to use bootstrap functions.

if (
  empty($_GET["feed"])
  || empty($_GET["authkey"])
  || empty($_GET["auth"])
  || empty($_GET["passkey"])
  || empty($_GET["user"])
  || !is_number($_GET["user"])
  || strlen($_GET["authkey"]) !== 32
  || strlen($_GET["passkey"]) !== 32
  || strlen($_GET["auth"]) !== 32
) {
    $feed->open();
    $feed->channel("Blocked", "RSS feed.");
    $feed->close();
    error(400, $NoHTML = true);
}

# Initialize
require_once "classes/env.class.php";
$ENV = ENV::go();

$User = (int) $_GET["user"];
if (!$Enabled = $cache->get_value("enabled_$User")) {
    require_once serverRoot."/classes/db.class.php";
    $db = new DB; // Load the database wrapper

    $db->query("
    SELECT
      `Enabled`
    FROM
      `users_main`
    WHERE
      `ID` = \"$User\"
    ");

    list($Enabled) = $db->next_record();
    $cache->cache_value("enabled_$User", $Enabled, 0);
}

# Check for RSS auth
if (md5($User.$ENV->getPriv("rssHash").$_GET["passkey"]) !== $_GET["auth"] || (int) $Enabled !== 1) {
    $feed->open();
    $feed->channel("Blocked", "RSS feed.");
    $feed->close();
    error(400, $NoHTML = true);
}
