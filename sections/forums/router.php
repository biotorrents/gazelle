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


enforce_login();

/*
if (!empty($app->user->extra['DisableForums'])) {
    error(403);
}
*/

$Forums = Forums::get_forums();
$ForumCats = Forums::get_forum_categories();

$_POST['action'] ??= null;
$_GET['action'] ??= null;

if (!empty($_POST['action'])) {
    switch ($_POST['action']) {
        case 'reply':
            require serverRoot.'/sections/forumsOld/take_reply.php';
            break;

        case 'new':
            require serverRoot.'/sections/forumsOld/take_new_thread.php';
            break;

        case 'mod_thread':
            require serverRoot.'/sections/forumsOld/mod_thread.php';
            break;

        case 'poll_mod':
            require serverRoot.'/sections/forumsOld/poll_mod.php';
            break;

        case 'add_poll_option':
            require serverRoot.'/sections/forumsOld/add_poll_option.php';
            break;

        case 'warn':
            require serverRoot.'/sections/forumsOld/warn.php';
            break;

        case 'take_warn':
            require serverRoot.'/sections/forumsOld/take_warn.php';
            break;

        case 'take_topic_notes':
            require serverRoot.'/sections/forumsOld/take_topic_notes.php';
            break;

        default:
            error(0);
    }
} elseif (!empty($_GET['action'])) {
    switch ($_GET['action']) {
        case 'viewforum':
            // Page that lists all the topics in a forum
            require serverRoot.'/sections/forumsOld/forum.php';
            break;

        case 'viewthread':
        case 'viewtopic':
            // Page that displays threads
            require serverRoot.'/sections/forumsOld/thread.php';
            break;

        case 'ajax_get_edit':
            // Page that switches edits for mods
            require serverRoot.'/sections/forumsOld/ajax_get_edit.php';
            break;

        case 'new':
            // Create a new thread
            require serverRoot.'/sections/forumsOld/newthread.php';
            break;

        case 'takeedit':
            // Edit posts
            require serverRoot.'/sections/forumsOld/takeedit.php';
            break;

        case 'get_post':
            // Get posts
            require serverRoot.'/sections/forumsOld/get_post.php';
            break;

        case 'delete':
            // Delete posts
            require serverRoot.'/sections/forumsOld/delete.php';
            break;

        case 'catchup':
            // Catchup
            require serverRoot.'/sections/forumsOld/catchup.php';
            break;

        case 'search':
            // Search posts
            require serverRoot.'/sections/forumsOld/search.php';
            break;

        case 'change_vote':
            // Change poll vote
            require serverRoot.'/sections/forumsOld/change_vote.php';
            break;

        case 'delete_poll_option':
            require serverRoot.'/sections/forumsOld/delete_poll_option.php';
            break;

        case 'sticky_post':
            require serverRoot.'/sections/forumsOld/sticky_post.php';
            break;

        case 'edit_rules':
            require serverRoot.'/sections/forumsOld/edit_rules.php';
            break;

        case 'thread_subscribe':
            break;

        case 'warn':
            require serverRoot.'/sections/forumsOld/warn.php';
            break;

        default:
            error(404);
    }
} else {
    require serverRoot.'/sections/forumsOld/main.php';
}
