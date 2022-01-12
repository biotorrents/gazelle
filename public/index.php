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

# Autoload classes via Composer
require_once __DIR__.'/../vendor/autoload.php';

$path = pathinfo($_SERVER['SCRIPT_NAME']);
$file = $path['filename'];

if ($path['dirname'] !== '/') {
    Http::response(403);
} elseif (in_array($file, ['announce', 'info_hash', 'peer_id', 'scrape'])) {
    die("d14:failure reason40:Invalid .torrent, try downloading again.e");
}

switch ($file) {
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
        $valid = true;
        break;
}

if ($valid) {
    require_once __DIR__.'/../config/app.php';
    require_once __DIR__.'/../bootstrap/utilities.php';
    require_once __DIR__.'/../bootstrap/app.php';
} else {
    Http::response(404);
}
