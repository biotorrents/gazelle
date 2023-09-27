<?php

declare(strict_types=1);

$app = \Gazelle\App::go();

$ENV = \Gazelle\ENV::go();


/**
 * Actions
 */

switch ($_GET['action']) {
    /**
     * Torrents
     */
    case 'torrent':
        require_once "$ENV->serverRoot/sections/api/torrents/torrent.php";
        break;

    case 'group':
        require_once "$ENV->serverRoot/sections/api/torrents/group.php";
        break;

    case 'tcomments':
        require_once "$ENV->serverRoot/sections/api/tcomments.php";
        break;

        /**
         * Features
         */
    case 'collage':
        require_once "$ENV->serverRoot/sections/api/collage.php";
        break;

    case 'artist':
        require_once "$ENV->serverRoot/sections/api/artist.php";
        break;

    case 'request':
        require_once "$ENV->serverRoot/sections/api/request.php";
        break;

    case 'top10':
        require_once "$ENV->serverRoot/sections/api/top10/index.php";
        break;

        /**
         * Users
         */
    case 'user':
        require_once "$ENV->serverRoot/sections/api/user.php";
        break;

    case 'user_recents':
        require_once "$ENV->serverRoot/sections/api/user_recents.php";
        break;

        /**
         * Account
         */
    case 'inbox':
        require_once "$ENV->serverRoot/sections/api/inbox/index.php";
        break;

    case 'bookmarks':
        require_once "$ENV->serverRoot/sections/api/bookmarks/index.php";
        break;

    case 'notifications':
        require_once "$ENV->serverRoot/sections/api/notifications.php";
        break;

    case 'get_user_notifications':
        require_once "$ENV->serverRoot/sections/api/get_user_notifications.php";
        break;

    case 'clear_user_notification':
        require_once "$ENV->serverRoot/sections/api/clear_user_notification.php";
        break;

        /**
         * Forums
         */
    case 'forum':
        require_once "$ENV->serverRoot/sections/api/forum/index.php";
        break;

    case 'subscriptions':
        require_once "$ENV->serverRoot/sections/api/subscriptions.php";
        break;

        /**
         * Meta
         */
    case 'index':
        require_once "$ENV->serverRoot/sections/api/info.php";
        break;

    case 'announcements':
        require_once "$ENV->serverRoot/sections/api/announcements.php";
        break;

    case 'wiki':
        require_once "$ENV->serverRoot/sections/api/wiki.php";
        break;

        /**
         * Under construction
         */
    case 'news_ajax':
        require_once "$ENV->serverRoot/sections/api/news_ajax.php";
        break;

    case 'send_recommendation':
        require_once "$ENV->serverRoot/sections/api/send_recommendation.php";
        break;

    default:
        // If they're screwing around with the query string
        \Gazelle\Api\Base::failure(400);
}
