<?php

$app = App::go();

//calculate ratio
//returns 0 for DNE and -1 for infinity, because we don't want strings being returned for a numeric value in our java
$Ratio = 0;
if ($user['BytesUploaded'] == 0 && $user['BytesDownloaded'] == 0) {
    $Ratio = 0;
} elseif ($user['BytesDownloaded'] == 0) {
    $Ratio = -1;
} else {
    $Ratio = Text::float(max($user['BytesUploaded'] / $user['BytesDownloaded'] - 0.005, 0), 2); //Subtract .005 to floor to 2 decimals
}

$MyNews = $user['LastReadNews'];
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

$NewMessages = $app->cacheOld->get_value('inbox_new_' . $user['ID']);
if ($NewMessages === false) {
    $app->dbOld->query("
    SELECT COUNT(UnRead)
    FROM pm_conversations_users
    WHERE UserID = '" . $user['ID'] . "'
      AND UnRead = '1'
      AND InInbox = '1'");
    list($NewMessages) = $app->dbOld->next_record();
    $app->cacheOld->cache_value('inbox_new_' . $user['ID'], $NewMessages, 0);
}

if (check_perms('site_torrents_notify')) {
    $NewNotifications = $app->cacheOld->get_value('notifications_new_' . $user['ID']);
    if ($NewNotifications === false) {
        $app->dbOld->query("
      SELECT COUNT(UserID)
      FROM users_notify_torrents
      WHERE UserID = '$user[ID]'
        AND UnRead = '1'");
        list($NewNotifications) = $app->dbOld->next_record();
        /* if ($NewNotifications && !check_perms('site_torrents_notify')) {
            $app->dbOld->query("DELETE FROM users_notify_torrents WHERE UserID='$user[ID]'");
            $app->dbOld->query("DELETE FROM users_notify_filters WHERE UserID='$user[ID]'");
        } */
        $app->cacheOld->cache_value('notifications_new_' . $user['ID'], $NewNotifications, 0);
    }
}

// News
$MyNews = $user['LastReadNews'];
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
$MyBlog = $user['LastReadBlog'];
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
  'username' => $user['Username'],
  'id' => (int)$user['ID'],
  'authkey' => $user['AuthKey'],
  'passkey' => $user['torrent_pass'],
  'notifications' => array(
    'messages' => (int)$NewMessages,
    'notifications' => (int)$NewNotifications,
    'newAnnouncement' => $MyNews < $CurrentNews,
    'newBlog' => $MyBlog < $CurrentBlog,
    'newSubscriptions' => $NewSubscriptions == 1
  ),
  'userstats' => array(
    'uploaded' => (int)$user['BytesUploaded'],
    'downloaded' => (int)$user['BytesDownloaded'],
    'ratio' => (float)$Ratio,
    'requiredratio' => (float)$user['RequiredRatio'],
    'class' => $ClassLevels[$user['Class']]['Name']
  )
));
