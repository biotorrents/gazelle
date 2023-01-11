<?php

$app = App::go();

//calculate ratio
//returns 0 for DNE and -1 for infinity, because we don't want strings being returned for a numeric value in our java
$Ratio = 0;
if ($app->userNew->extra['BytesUploaded'] == 0 && $app->userNew->extra['BytesDownloaded'] == 0) {
    $Ratio = 0;
} elseif ($app->userNew->extra['BytesDownloaded'] == 0) {
    $Ratio = -1;
} else {
    $Ratio = Text::float(max($app->userNew->extra['BytesUploaded'] / $app->userNew->extra['BytesDownloaded'] - 0.005, 0), 2); //Subtract .005 to floor to 2 decimals
}

$MyNews = $app->userNew->extra['LastReadNews'];
$CurrentNews = $app->cacheOld->get_value('news_latest_id');
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
    $app->cacheOld->cache_value('news_latest_id', $CurrentNews, 0);
}

$NewMessages = $app->cacheOld->get_value('inbox_new_' . $app->userNew->core['id']);
if ($NewMessages === false) {
    $app->dbOld->query("
    SELECT COUNT(UnRead)
    FROM pm_conversations_users
    WHERE UserID = '" . $app->userNew->core['IidD'] . "'
      AND UnRead = '1'
      AND InInbox = '1'");
    list($NewMessages) = $app->dbOld->next_record();
    $app->cacheOld->cache_value('inbox_new_' . $app->userNew->core['id'], $NewMessages, 0);
}

if (check_perms('site_torrents_notify')) {
    $NewNotifications = $app->cacheOld->get_value('notifications_new_' . $app->userNew->core['id']);
    if ($NewNotifications === false) {
        $app->dbOld->query("
      SELECT COUNT(UserID)
      FROM users_notify_torrents
      WHERE UserID = '$app->userNew->core[id]'
        AND UnRead = '1'");
        list($NewNotifications) = $app->dbOld->next_record();
        /* if ($NewNotifications && !check_perms('site_torrents_notify')) {
            $app->dbOld->query("DELETE FROM users_notify_torrents WHERE UserID='$app->userNew->core[id]'");
            $app->dbOld->query("DELETE FROM users_notify_filters WHERE UserID='$app->userNew->core[id]'");
        } */
        $app->cacheOld->cache_value('notifications_new_' . $app->userNew->core['id'], $NewNotifications, 0);
    }
}

// News
$MyNews = $app->userNew->extra['LastReadNews'];
$CurrentNews = $app->cacheOld->get_value('news_latest_id');
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
    $app->cacheOld->cache_value('news_latest_id', $CurrentNews, 0);
}

// Blog
$MyBlog = $app->userNew->extra['LastReadBlog'];
$CurrentBlog = $app->cacheOld->get_value('blog_latest_id');
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
    $app->cacheOld->cache_value('blog_latest_id', $CurrentBlog, 0);
}

// Subscriptions
$NewSubscriptions = Subscriptions::has_new_subscriptions();

json_die("success", array(
  'username' => $app->userNew->core['username'],
  'id' => (int)$app->userNew->core['id'],
  'authkey' => $app->userNew->extra['AuthKey'],
  'passkey' => $app->userNew->extra['torrent_pass'],
  'notifications' => array(
    'messages' => (int)$NewMessages,
    'notifications' => (int)$NewNotifications,
    'newAnnouncement' => $MyNews < $CurrentNews,
    'newBlog' => $MyBlog < $CurrentBlog,
    'newSubscriptions' => $NewSubscriptions == 1
  ),
  'userstats' => array(
    'uploaded' => (int)$app->userNew->extra['BytesUploaded'],
    'downloaded' => (int)$app->userNew->extra['BytesDownloaded'],
    'ratio' => (float)$Ratio,
    'requiredratio' => (float)$app->userNew->extra['RequiredRatio'],
    'class' => $ClassLevels[$app->userNew->extra['Class']]['Name']
  )
));
