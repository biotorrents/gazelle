<?php
#declare(strict_types=1);

if (!isset($_REQUEST['authkey']) || !isset($_REQUEST['torrent_pass'])) {
    enforce_login();
    $TorrentPass = $LoggedUser['torrent_pass'];
    $UserID = $LoggedUser['ID'];
    $AuthKey = $LoggedUser['AuthKey'];
} else {
    if (strpos($_REQUEST['torrent_pass'], '_') !== false) {
        error(404);
    }

    $UserInfo = $Cache->get_value('user_'.$_REQUEST['torrent_pass']);
    if (!is_array($UserInfo)) {
        $DB->query("
          SELECT ID, la.UserID
          FROM users_main AS m
            INNER JOIN users_info AS i ON i.UserID = m.ID
            LEFT JOIN locked_accounts AS la ON la.UserID = m.ID
          WHERE m.torrent_pass = '".db_string($_REQUEST['torrent_pass'])."'
            AND m.Enabled = '1'");

        $UserInfo = $DB->next_record();
        $Cache->cache_value('user_'.$_REQUEST['torrent_pass'], $UserInfo, 3600);
    }

    $UserInfo = array($UserInfo);
    list($UserID, $Locked) = array_shift($UserInfo);
    if (!$UserID) {
        error(0);
    }

    $TorrentPass = $_REQUEST['torrent_pass'];
    $AuthKey = $_REQUEST['authkey'];

    if ($Locked === $UserID) {
        header('HTTP/1.1 403 Forbidden');
        error();
    }
}

$TorrentID = $_REQUEST['id'];
if (!is_number($TorrentID)) {
    error(0);
}

/*
  uTorrent Remote and various scripts redownload .torrent files periodically.
  To prevent this retardation from blowing bandwidth etc., let's block it
  if the .torrent file has been downloaded four times before
 */
$ScriptUAs = array('BTWebClient*', 'Python-urllib*', 'python-requests*');
if (Misc::in_array_partial($_SERVER['HTTP_USER_AGENT'], $ScriptUAs)) {
    $DB->query("
      SELECT 1
      FROM users_downloads
      WHERE UserID = $UserID
        AND TorrentID = $TorrentID
      LIMIT 4");

    if ($DB->record_count() === 4) {
        error('You have already downloaded this torrent file four times. If you need to download it again, please do so from your browser.', true);
        error();
    }
}

$Info = $Cache->get_value('torrent_download_'.$TorrentID);
if (!is_array($Info) || !array_key_exists('PlainArtists', $Info) || empty($Info[10])) {
    $DB->query("
      SELECT
        t.Media,
        t.Version,
        t.Codec,
        tg.Year,
        tg.ID AS GroupID,
        COALESCE(NULLIF(tg.Name,''), NULLIF(tg.Title2,''), tg.NameJP) AS Name,
        tg.WikiImage,
        tg.CategoryID,
        t.Size,
        t.FreeTorrent,
        HEX(t.info_hash)
      FROM torrents AS t
        INNER JOIN torrents_group AS tg ON tg.ID = t.GroupID
      WHERE t.ID = '".db_string($TorrentID)."'");

    if (!$DB->has_results()) {
        error(404);
    }

    $Info = array($DB->next_record(MYSQLI_NUM, array(4, 5, 6, 10)));
    $Artists = Artists::get_artist($Info[0][4], false);
    $Info['Artists'] = Artists::display_artists($Artists, false, true);
    $Info['PlainArtists'] = Artists::display_artists($Artists, false, true, false);
    $Cache->cache_value("torrent_download_$TorrentID", $Info, 0);
}

if (!is_array($Info[0])) {
    error(404);
}

list($Media, $Format, $Encoding, $Year, $GroupID, $Name, $Image, $CategoryID, $Size, $FreeTorrent, $InfoHash) = array_shift($Info); // used for generating the filename
$Artists = $Info['Artists'];

// If he's trying use a token on this, we need to make sure he has one,
// deduct it, add this to the FLs table, and update his cache key.
if ($_REQUEST['usetoken'] && $FreeTorrent === '0') {
    if (isset($LoggedUser)) {
        $FLTokens = $LoggedUser['FLTokens'];
        if ($LoggedUser['CanLeech'] !== '1') {
            error('You cannot use tokens while leech disabled.');
        }
    } else {
        $UInfo = Users::user_heavy_info($UserID);
        if ($UInfo['CanLeech'] !== '1') {
            error('You may not use tokens while leech disabled.');
        }
        $FLTokens = $UInfo['FLTokens'];
    }

    // First make sure this isn't already FL, and if it is, do nothing
    if (!Torrents::has_token($TorrentID)) {
        if ($FLTokens <= 0) {
            error('You do not have any freeleech tokens left. Please use the regular DL link.');
        }

        if ($Size >= 10737418240) {
            error('This torrent is too large. Please use the regular DL link.');
        }

        // Let the tracker know about this
        if (!Tracker::update_tracker('add_token', ['info_hash' => substr('%'.chunk_split($InfoHash, 2, '%'), 0, -1), 'userid' => $UserID])) {
            error('Sorry! An error occurred while trying to register your token. Most often, this is due to the tracker being down or under heavy load. Please try again later.');
        }

        if (!Torrents::has_token($TorrentID)) {
            $DB->query("
              INSERT INTO users_freeleeches (UserID, TorrentID, Time)
              VALUES ($UserID, $TorrentID, NOW())
              ON DUPLICATE KEY UPDATE
                Time = VALUES(Time),
                Expired = FALSE,
                Uses = Uses + 1");

            $DB->query("
              UPDATE users_main
              SET FLTokens = FLTokens - 1
              WHERE ID = $UserID");

            // Fix for downloadthemall messing with the cached token count
            $UInfo = Users::user_heavy_info($UserID);
            $FLTokens = $UInfo['FLTokens'];

            $Cache->begin_transaction("user_info_heavy_$UserID");
            $Cache->update_row(false, array('FLTokens' => ($FLTokens - 1)));
            $Cache->commit_transaction(0);

            $Cache->delete_value("users_tokens_$UserID");
        }
    }
}

// Stupid Recent Snatches On User Page
if ($Image !== '') {
    $RecentSnatches = $Cache->get_value("recent_snatches_$UserID");
    if (!empty($RecentSnatches)) {
        $Snatch = array(
        'ID' => $GroupID,
        'Name' => $Name,
        'Artist' => $Artists,
        'WikiImage' => $Image);

        if (!in_array($Snatch, $RecentSnatches)) {
            if (count($RecentSnatches) === 5) {
                array_pop($RecentSnatches);
            }
            array_unshift($RecentSnatches, $Snatch);
        } elseif (!is_array($RecentSnatches)) {
            $RecentSnatches = array($Snatch);
        }
        $Cache->cache_value("recent_snatches_$UserID", $RecentSnatches, 0);
    }
}

$DB->query("
  INSERT IGNORE INTO users_downloads (UserID, TorrentID, Time)
  VALUES ('$UserID', '$TorrentID', NOW())");

Torrents::set_snatch_update_time($UserID, Torrents::SNATCHED_UPDATE_AFTERDL);
$Contents = file_get_contents(TORRENT_STORE.$TorrentID.'.torrent');
$FileName = TorrentsDL::construct_file_name($TorrentID);

header('Content-Type: application/x-bittorrent; charset=utf-8');
header('Content-disposition: attachment; filename="'.$FileName.'"');

function add_passkey($ann)
{
    global $TorrentPass;
    return (is_array($ann)) ? array_map("add_passkey", $ann) : $ann."/".$TorrentPass."/announce";
}

$UserAnnounceURL = ANNOUNCE_URLS[0][0]."/".$TorrentPass."/announce";

# todo: Probably not working, but no need yet
$UserAnnounceList = (sizeof(ANNOUNCE_URLS[0]) === 1 && sizeof(ANNOUNCE_URLS[0][0]) === 1) ? [] : array_map('add_passkey', ANNOUNCE_URLS[0]);

# Tracker tiers (pending)
#$UserAnnounceList = (sizeof(ANNOUNCE_URLS) === 1 && sizeof(ANNOUNCE_URLS[0]) === 1) ? [] : array(array_map('add_passkey', ANNOUNCE_URLS[0]), ANNOUNCE_URLS[1]);

# Original Oppaitime
#$UserAnnounceList = (sizeof(ANNOUNCE_URLS) == 1 && sizeof(ANNOUNCE_URLS[0]) == 1) ? [] : array_map("add_passkey", ANNOUNCE_URLS);

echo TorrentsDL::get_file($Contents, $UserAnnounceURL, $UserAnnounceList);
