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

if (!empty($app->userNew->extra['DisableForums'])) {
    error(403);
}

$Forums = Forums::get_forums();
$ForumCats = Forums::get_forum_categories();

if (!empty($_POST['action'])) {
    switch ($_POST['action']) {
    case 'reply':
      require serverRoot.'/sections/forums/take_reply.php';
      break;

    case 'new':
      require serverRoot.'/sections/forums/take_new_thread.php';
      break;

    case 'mod_thread':
      require serverRoot.'/sections/forums/mod_thread.php';
      break;

    case 'poll_mod':
      require serverRoot.'/sections/forums/poll_mod.php';
      break;

    case 'add_poll_option':
      require serverRoot.'/sections/forums/add_poll_option.php';
      break;

    case 'warn':
      require serverRoot.'/sections/forums/warn.php';
      break;

    case 'take_warn':
      require serverRoot.'/sections/forums/take_warn.php';
      break;

    case 'take_topic_notes':
      require serverRoot.'/sections/forums/take_topic_notes.php';
      break;

    default:
      error(0);
  }
} elseif (!empty($_GET['action'])) {
    switch ($_GET['action']) {
    case 'viewforum':
      // Page that lists all the topics in a forum
      require serverRoot.'/sections/forums/forum.php';
      break;

    case 'viewthread':
    case 'viewtopic':
      // Page that displays threads
      require serverRoot.'/sections/forums/thread.php';
      break;

    case 'ajax_get_edit':
      // Page that switches edits for mods
      require serverRoot.'/sections/forums/ajax_get_edit.php';
      break;

    case 'new':
      // Create a new thread
      require serverRoot.'/sections/forums/newthread.php';
      break;

    case 'takeedit':
      // Edit posts
      require serverRoot.'/sections/forums/takeedit.php';
      break;

    case 'get_post':
      // Get posts
      require serverRoot.'/sections/forums/get_post.php';
      break;

    case 'delete':
      // Delete posts
      require serverRoot.'/sections/forums/delete.php';
      break;

    case 'catchup':
      // Catchup
      require serverRoot.'/sections/forums/catchup.php';
      break;

    case 'search':
      // Search posts
      require serverRoot.'/sections/forums/search.php';
      break;

    case 'change_vote':
      // Change poll vote
      require serverRoot.'/sections/forums/change_vote.php';
      break;

    case 'delete_poll_option':
      require serverRoot.'/sections/forums/delete_poll_option.php';
      break;

    case 'sticky_post':
      require serverRoot.'/sections/forums/sticky_post.php';
      break;

    case 'edit_rules':
      require serverRoot.'/sections/forums/edit_rules.php';
      break;

    case 'thread_subscribe':
      break;

    case 'warn':
      require serverRoot.'/sections/forums/warn.php';
      break;

    default:
      error(404);
  }
} else {
    require serverRoot.'/sections/forums/main.php';
}
