<?php
#declare(strict_types = 1);

/**
 * User history switch center
 */

enforce_login();

if ($_GET['action']) {
    switch ($_GET['action']) {
    case 'tracker_ips':
      include('ip_tracker_history.php');
      break;

    case 'posts':
      //Load ratio history page
      include('post_history.php');
      break;

    case 'subscriptions':
      // View subscriptions
      require('subscriptions.php');
      break;

    case 'thread_subscribe':
      require('thread_subscribe.php');
      break;

    case 'comments_subscribe':
      require('comments_subscribe.php');
      break;

    case 'catchup':
      require('catchup.php');
      break;

    case 'collage_subscribe':
      require('collage_subscribe.php');
      break;

    case 'subscribed_collages':
      require('subscribed_collages.php');
      break;

    case 'catchup_collages':
      require('catchup_collages.php');
      break;

    case 'token_history':
      require('token_history.php');
      break;

    case 'quote_notifications':
      require('quote_notifications.php');
      break;

    default:
      //You trying to mess with me query string? To the home page with you!
      header('Location: index.php');
  }
}
