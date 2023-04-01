<?php

$app = \Gazelle\App::go();

//calculate ratio
//returns 0 for DNE and -1 for infinity, because we don't want strings being returned for a numeric value in our java
$Ratio = 0;
if ($app->user->extra['BytesUploaded'] == 0 && $app->user->extra['BytesDownloaded'] == 0) {
    $Ratio = 0;
} elseif ($app->user->extra['BytesDownloaded'] == 0) {
    $Ratio = -1;
} else {
    $Ratio = Text::float(max($app->user->extra['BytesUploaded'] / $app->user->extra['BytesDownloaded'] - 0.005, 0), 2); //Subtract .005 to floor to 2 decimals
}

$MyNews = $app->user->extra['LastReadNews'];
$CurrentNews = $app->cache->get('news_latest_id');
if ($CurrentNews === false) {
    $app->dbOld->query("
    SELECT ID
    FROM news
    ORDER BY Time DESC
    LIMIT 1");
    if ($app->dbOld->record_count() === 1) {
        list($CurrentNews) = $app->dbOld->next_record();
    } else {
        $CurrentNews = -1;
    }
    $app->cache->set('news_latest_id', $CurrentNews, 0);
}

$NewMessages = $app->cache->get('inbox_new_' . $app->user->core['id']);
if ($NewMessages === false) {
    $app->dbOld->query("
    SELECT COUNT(UnRead)
    FROM pm_conversations_users
    WHERE UserID = '" . $app->user->core['IidD'] . "'
      AND UnRead = '1'
      AND InInbox = '1'");
    list($NewMessages) = $app->dbOld->next_record();
    $app->cache->set('inbox_new_' . $app->user->core['id'], $NewMessages, 0);
}

if (check_perms('site_torrents_notify')) {
    $NewNotifications = $app->cache->get('notifications_new_' . $app->user->core['id']);
    if ($NewNotifications === false) {
        $app->dbOld->query("
      SELECT COUNT(UserID)
      FROM users_notify_torrents
      WHERE UserID = '{$app->user->core['id']}'
        AND UnRead = '1'");
        list($NewNotifications) = $app->dbOld->next_record();
        /* if ($NewNotifications && !check_perms('site_torrents_notify')) {
            $app->dbOld->query("DELETE FROM users_notify_torrents WHERE UserID='{$app->user->core['id']}'");
            $app->dbOld->query("DELETE FROM users_notify_filters WHERE UserID='{$app->user->core['id']}'");
        } */
        $app->cache->set('notifications_new_' . $app->user->core['id'], $NewNotifications, 0);
    }
}

// News
$MyNews = $app->user->extra['LastReadNews'];
$CurrentNews = $app->cache->get('news_latest_id');
if ($CurrentNews === false) {
    $app->dbOld->query("
    SELECT ID
    FROM news
    ORDER BY Time DESC
    LIMIT 1");
    if ($app->dbOld->record_count() === 1) {
        list($CurrentNews) = $app->dbOld->next_record();
    } else {
        $CurrentNews = -1;
    }
    $app->cache->set('news_latest_id', $CurrentNews, 0);
}

// Blog
$MyBlog = $app->user->extra['LastReadBlog'];
$CurrentBlog = $app->cache->get('blog_latest_id');
if ($CurrentBlog === false) {
    $app->dbOld->query("
    SELECT ID
    FROM blog
    WHERE Important = 1
    ORDER BY Time DESC
    LIMIT 1");
    if ($app->dbOld->record_count() === 1) {
        list($CurrentBlog) = $app->dbOld->next_record();
    } else {
        $CurrentBlog = -1;
    }
    $app->cache->set('blog_latest_id', $CurrentBlog, 0);
}

// Subscriptions
$NewSubscriptions = Subscriptions::has_new_subscriptions();

json_die("success", array(
  'username' => $app->user->core['username'],
  'id' => (int)$app->user->core['id'],
  'authkey' => $app->user->extra['AuthKey'],
  'passkey' => $app->user->extra['torrent_pass'],
  'notifications' => array(
    'messages' => (int)$NewMessages,
    'notifications' => (int)$NewNotifications,
    'newAnnouncement' => $MyNews < $CurrentNews,
    'newBlog' => $MyBlog < $CurrentBlog,
    'newSubscriptions' => $NewSubscriptions == 1
  ),
  'userstats' => array(
    'uploaded' => (int)$app->user->extra['BytesUploaded'],
    'downloaded' => (int)$app->user->extra['BytesDownloaded'],
    'ratio' => (float)$Ratio,
    'requiredratio' => (float)$app->user->extra['RequiredRatio'],
    'class' => $ClassLevels[$app->user->extra['Class']]['Name']
  )
));
