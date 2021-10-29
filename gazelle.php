<?php
declare(strict_types=1);

/**
 * Gazelle single app entry point, to clean up PHP files in root
 * Adapted from https://github.com/OPSnet/Gazelle/blob/master/gazelle.php
 * 
 * commit c10adab0e22c96d13c2ddbf3610792127245d97f
 * Author: itismadness <itismadness@apollo.rip>
 * Date:   Sat Jan 27 20:42:55 2018 -0100
 */

$PathInfo = pathinfo($_SERVER['SCRIPT_NAME']);
$Document = $PathInfo['filename'];

if ($PathInfo['dirname'] !== '/') {
    exit;
} elseif (in_array($Document, ['announce', 'scrape'])) {
    die("d14:failure reason40:Invalid .torrent, try downloading again.e");
}

$Valid = false;
switch ($Document) {
    # ls sections
    case 'api':
    case 'artist':
    case 'better':
    case 'blog':
    case 'bookmarks':
    case 'collages':
    case 'comments':
    case 'contest':
    case 'donate':
    case 'enable':
    case 'feeds':
    case 'forums':
    case 'friends':
    case 'image':
    case 'inbox':
    case 'index':
    case 'legal':
    case 'locked':
    case 'log':
    case 'login':
    case 'logout':
    case 'peerupdate':
    case 'pwgen':
    case 'register':
    case 'reports':
    case 'reportsv2':
    case 'requests':
    case 'rules':
    case 'schedule':
    case 'snatchlist':
    case 'staff':
    case 'staffpm':
    case 'stats':
    case 'store':
    case 'tools':
    case 'top10':
    case 'torrents':
    case 'upload':
    case 'user':
    case 'userhistory':
    case 'wiki':
        $Valid = true;
        break;
}

if (!$Valid) {
    http_response_code(404);
} else {
    require_once __DIR__.'/classes/script_start.php';
}