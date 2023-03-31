<?php

declare(strict_types=1);


/**
 * download torrent file
 */

$app = \Gazelle\App::go();

# variables
$get = Http::query("get");
$server = Http::query("server");

$authKey = $get["authkey"] ?? null;
$passKey = $get["torrent_pass"] ?? null;
$torrentId = $get["torrentId"] ?? null;
$useToken = $get["usetoken"] ?? null;

# no keys in query string
if (!$authKey || !$passKey) {
    # this still works?
    enforce_login();

    $userId = $app->userNew->core["id"];
    $authKey = $app->userNew->extra["AuthKey"];
    $passKey = $app->userNew->extra["torrent_pass"];
}

# no userId
$userId ??= null;
if (!$userId) {
    $query = "select id from users_main where torrent_pass = ?";
    $userId = $app->dbNew->single($query, [$passKey]);
}

# check if user is locked
$query = "select 1 from locked_accounts where userId = ?";
$locked = $app->dbNew->single($query, [$userId]);

if ($locked) {
    Http::response(403);
}

/*
# old: unnecessary?
$query = "
    select id, locked_accounts.userId from users_main
    inner join users_info on users_info.userId = users_main.id
    left join locked_accounts on locked_accounts.userId = users_main.id
    where users_main.torrent_pass = ? and users_main.enabled = ?
";
$row = $app->dbNew->row($query, [ $app->userNew->extra["torrent_pass"], 1 ]);
*/

/** */

# uTorrent Remote and various scripts redownload .torrent files.
# To prevent this retardation from blowing bandwidth, etc.,
# let's block it if it's been downloaded five times before.
$scriptUserAgents = ["BTWebClient*", "Python-urllib*", "python-requests*"];
$inArrayPartial = Misc::in_array_partial($server["HTTP_USER_AGENT"], $scriptUserAgents);

if ($inArrayPartial) {
    $query = "select 1 from users_downloads where userId = ? and torrentId = ?";
    $ref = $app->dbNew->multi($query, [$userId, $torrentId]);

    if (count($ref) > 4) {
        Http::response(403);

        /*
        error("
            You have already downloaded this torrent file four times.
            If you need to download it again, please do so from your browser.
        ");
        */
    }
}

/** */

/*
# not entirely sure what this is
$Info = $app->cacheOld->get_value("torrent_download_".$torrentId);
if (!is_array($Info) || !array_key_exists("PlainArtists", $Info) || empty($Info[10])) {
    $app->dbOld->prepared_query("
      SELECT
        t.`Media`,
        t.`Version`,
        t.`Codec`,
        tg.`year`,
        tg.`id` AS GroupID,
        COALESCE(NULLIF(tg.`title`,''), NULLIF(tg.`subject`,''), tg.`object`) AS Name,
        tg.`picture`,
        tg.`category_id`,
        t.`Size`,
        t.`FreeTorrent`,
        HEX(t.`info_hash`)
      FROM `torrents` AS t
        INNER JOIN `torrents_group` AS tg ON tg.`id` = t.`GroupID`
      WHERE t.`ID` = '".db_string($torrentId)."'");


    if (!$app->dbOld->has_results()) {
        error(404);
    }

    $Info = array($app->dbOld->next_record(MYSQLI_NUM, array(4, 5, 6, 10)));
    $Artists = Artists::get_artist($Info[0][4], false);
    $Info["Artists"] = Artists::display_artists($Artists, false, true);
    $Info["PlainArtists"] = Artists::display_artists($Artists, false, true, false);
    $app->cacheOld->cache_value("torrent_download_$torrentId", $Info, 0);
}

if (!is_array($Info[0])) {
    error(404);
}

list($Media, $Format, $Encoding, $Year, $GroupID, $Name, $Image, $CategoryID, $size, $FreeTorrent, $infoHash) = array_shift($Info); // used for generating the filename
$Artists = $Info["Artists"];
*/

# if he's trying use a token on this, we need to make sure he has one,
# deduct it, add this to the FLs table, and update his cache key
$query = "select freeTorrent from torrents where id = ?";
$leechStatus = $app->dbNew->single($query, [$torrentId]);

if ($useToken && intval($leechStatus) === 0) {
    # check for leech status and collect token count
    $isLoggedIn = $app->userNew->isLoggedIn();

    # logged in
    if ($isLoggedIn) {
        $tokenCount = $app->userNew->extra["FLTokens"];

        if (!$app->userNew->extra["CanLeech"]) {
            error("You can't use freeleech tokens while your leeching privileges are disabled.");
            exit;
        }
    }

    # not logged in
    if (!$isLoggedIn) {
        $userHeavyInfo = User::user_heavy_info($userId);
        $tokenCount = $userHeavyInfo["FLTokens"];

        if (!$userHeavyInfo["CanLeech"]) {
            error("You can't use freeleech tokens while your leeching privileges are disabled.");
            exit;
        }
    }

    # first make sure this isn't already FL, and if it is, do nothing
    if (!Torrents::has_token($torrentId)) {
        if ($tokenCount <= 0) {
            error("You don't have any freeleech tokens left. Please use the regular DL link.");
            exit;
        }

        /*
        # undocumented business logic
        if ($size >= 10737418240) {
            error("This torrent is too large. Please use the regular DL link.");
            exit;
        }
        */

        # let the tracker know about this
        try {
            Tracker::update_tracker(
                "add_token",
                ["info_hash" => substr("%".chunk_split($infoHash, 2, "%"), 0, -1), "userid" => $userId]
            );
        } catch (Throwable $e) {
            error("
                Sorry!
                An error occurred while trying to register your token.
                Most often, this is due to the tracker being down or under heavy load.
                Please try again later.
            ");
            exit;
        }

        # register the freeleech token use
        $query = "
            insert into users_freeleeches (userId, torrentId, time) values (?, ?, now())
            on duplicate key update time = now(), expired = false, uses = uses + 1
        ";
        $app->dbNew->do($query, [$userId, $torrentId]);

        $query = "update users_main set flTokens = flTokens - 1 where id = ?";
        $app->dbNew->do($query, [$userId]);
    }
} # if ($useToken && intval($leechStatus) === 0)

/*
# there's gotta be a better way to do this
// Stupid Recent Snatches On User Page
if ($Image !== "") {
    $RecentSnatches = $app->cacheOld->get_value("recent_snatches_$userId");
    if (!empty($RecentSnatches)) {
        $Snatch = array(
        "ID" => $GroupID,
        "Name" => $Name,
        "Artist" => $Artists,
        "WikiImage" => $Image);

        if (!in_array($Snatch, $RecentSnatches)) {
            if (count($RecentSnatches) === 5) {
                array_pop($RecentSnatches);
            }
            array_unshift($RecentSnatches, $Snatch);
        } elseif (!is_array($RecentSnatches)) {
            $RecentSnatches = array($Snatch);
        }
        $app->cacheOld->cache_value("recent_snatches_$userId", $RecentSnatches, 0);
    }
}
*/

/** */


/**
 * add_passkey
 */
function add_passkey($announceUri)
{
    global $passKey;

    return (is_array($announceUri))
        ? array_map("add_passkey", $announceUri)
        : "{$announceUri}/{$passKey}/announce";
}

# continue downloading the actual file
$query = "insert ignore into users_downloads (userId, torrentId, time) values (?, ?, now())";
$app->dbNew->do($query, [$userId, $torrentId]);

Torrents::set_snatch_update_time($userId, Torrents::SNATCHED_UPDATE_AFTERDL);
$contents = file_get_contents("{$app->env->torrentStore}/$torrentId.torrent");
$fileName = TorrentsDL::construct_file_name($torrentId);

# announce uri stuff
$userAnnounceUri = ANNOUNCE_URLS[0][0]."/".$passKey."/announce";

# todo: probably not working, but no need yet
$userAnnounceList = array_map("add_passkey", ANNOUNCE_URLS);

# tracker tiers (pending)
#$userAnnounceList = (sizeof(ANNOUNCE_URLS) === 1 && sizeof(ANNOUNCE_URLS[0]) === 1) ? [] : array(array_map("add_passkey", ANNOUNCE_URLS[0]), ANNOUNCE_URLS[1]);

# original oppaitime
#$userAnnounceList = (sizeof(ANNOUNCE_URLS) == 1 && sizeof(ANNOUNCE_URLS[0]) == 1) ? [] : array_map("add_passkey", ANNOUNCE_URLS);

# headers
header("Content-Type: application/x-bittorrent; charset=utf-8");
header("Content-disposition: attachment; filename={$fileName}");

# the .torrent file
echo TorrentsDL::get_file($contents, $userAnnounceUri, $userAnnounceList);
