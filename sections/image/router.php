<?php

declare(strict_types=1);

/**
 * Flight router
 * @see https://flightphp.com/learn
 */

# endpoints go here

# start the router
#Flight::start();


/** LEGACY ROUTES */

$app = \Gazelle\App::go();


if (!check_perms('site_proxy_images')) {
    img_error('forbidden');
}

$URL = isset($_GET['i']) ? htmlspecialchars_decode($_GET['i']) : null;

if (!extension_loaded('openssl') && strtoupper($URL[4]) === 'S') {
    img_error('badprotocol');
}

if (isset($_GET['c'])) {
    list($Data, $FileType) = $app->cache->get('image_cache_'.md5($URL));
    $cached = true;
}

if (!isset($Data) || !$Data) {
    $cached = false;
    $Data = @file_get_contents($URL, 0, stream_context_create(array('http' => array('timeout' => 15), 'ssl' => array('verify_peer' => false))));
    if (!$Data || empty($Data)) {
        img_error('timeout');
    }

    $FileType = image_type($Data);
    if ($FileType && function_exists("imagecreatefrom$FileType")) {
        $Image = imagecreatefromstring($Data);
        if (invisible($Image)) {
            img_error('invisible');
        }

        if (verysmall($Image)) {
            img_error('small');
        }
    }

    if (isset($_GET['c']) && strlen($Data) < 524288 && substr($Data, 0, 1) != '<') {
        $app->cache->set('image_cache_'.md5($URL), array($Data, $FileType), 3600 * 24 * 7);
    }
}

// Reset avatar, add mod note
function reset_image($UserID, $Type, $AdminComment, $PrivMessage)
{
    $ENV = ENV::go();

    if ($Type === 'avatar') {
        $cacheKey = "user_info_$UserID";
        $dbTable = 'users_info';
        $dbColumn = 'Avatar';
        $PMSubject = 'Your avatar has been automatically reset';
    }

    $UserInfo = $app->cache->get($cacheKey, true);
    if ($UserInfo !== false) {
        if ($UserInfo[$dbColumn] === '') {
            // This image has already been reset
            return;
        }

        $UserInfo[$dbColumn] = '';
        $app->cache->set($cacheKey, $UserInfo, 2592000); // cache for 30 days
    }

    // Reset the avatar or donor icon URL
    $app->dbOld->query("
      UPDATE $dbTable
      SET $dbColumn = ''
      WHERE UserID = '$UserID'");

    // Write comment to staff notes
    $app->dbOld->query("
      UPDATE users_info
      SET AdminComment = CONCAT('".sqltime().' - '.db_string($AdminComment)."\n\n', AdminComment)
      WHERE UserID = '$UserID'");

    // Clear cache keys
    $app->cache->delete($cacheKey);
    Misc::send_pm($UserID, 0, $PMSubject, $PrivMessage);
}

// Enforce avatar rules
if (isset($_GET['type']) && isset($_GET['userid'])) {
    $ValidTypes = array('avatar', 'avatar2', 'donoricon');
    if (!is_numeric($_GET['userid']) || !in_array($_GET['type'], $ValidTypes)) {
        error();
    }

    $UserID = $_GET['userid'];
    $Type = $_GET['type'];

    if ($Type === 'avatar' || $Type === 'avatar2') {
        $MaxFileSize = 512 * 1024; // 512 kiB
        $MaxImageHeight = 600; // pixels
        $TypeName = $Type === 'avatar' ? 'avatar' : 'second avatar';
    } elseif ($Type === 'donoricon') {
        $MaxFileSize = 128 * 1024; // 128 kiB
        $MaxImageHeight = 100; // pixels
        $TypeName = 'donor icon';
    }

    $Height = image_height($FileType, $Data);
    if (strlen($Data) > $MaxFileSize || $Height > $MaxImageHeight) {
        // Sometimes the cached image we have isn't the actual image
        if ($cached) {
            $Data2 = file_get_contents($URL, 0, stream_context_create(array('http' => array('timeout' => 60), 'ssl' => array('verify_peer' => false))));
        } else {
            $Data2 = $Data;
        }

        if ((strlen($Data2) > $MaxFileSize || image_height($FileType, $Data2) > $MaxImageHeight) && $UserID !== 1 && $UserID !== 2) {
            require_once serverRoot.'/classes/db.class.php';
            $dbURL = db_string($URL);
            $AdminComment = ucfirst($TypeName)." reset automatically (Size: ".\Gazelle\Text::float((strlen($Data)) / 1024)." kB, Height: ".$Height."px). Used to be $dbURL";
            $PrivMessage = "$ENV->siteName has the following requirements for {$TypeName}s:\n\n".
        "[b]".ucfirst($TypeName)."s must not exceed ".($MaxFileSize / 1024)." kB or be vertically longer than {$MaxImageHeight}px.[/b]\n\n".
        "Your $TypeName at $dbURL has been found to exceed these rules. As such, it has been automatically reset. You are welcome to reinstate your $TypeName once it has been resized down to an acceptable size.";
            reset_image($UserID, $Type, $AdminComment, $PrivMessage);
        }
    }
}

if (!isset($FileType)) {
    img_error('timeout');
}

if ($FileType === 'webm') {
    header("Content-type: video/$FileType");
} else {
    header("Content-type: image/$FileType");
}

echo $Data;
