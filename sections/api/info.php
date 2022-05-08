<?php
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
$CurrentNews = $cache->get_value('news_latest_id');
if ($CurrentNews === false) {
  $db->query("
    SELECT ID
    FROM news
    ORDER BY Time DESC
    LIMIT 1");
  if ($db->record_count() === 1) {
    list($CurrentNews) = $db->next_record();
  } else {
    $CurrentNews = -1;
  }
  $cache->cache_value('news_latest_id', $CurrentNews, 0);
}

$NewMessages = $cache->get_value('inbox_new_' . $user['ID']);
if ($NewMessages === false) {
  $db->query("
    SELECT COUNT(UnRead)
    FROM pm_conversations_users
    WHERE UserID = '" . $user['ID'] . "'
      AND UnRead = '1'
      AND InInbox = '1'");
  list($NewMessages) = $db->next_record();
  $cache->cache_value('inbox_new_' . $user['ID'], $NewMessages, 0);
}

if (check_perms('site_torrents_notify')) {
  $NewNotifications = $cache->get_value('notifications_new_' . $user['ID']);
  if ($NewNotifications === false) {
    $db->query("
      SELECT COUNT(UserID)
      FROM users_notify_torrents
      WHERE UserID = '$user[ID]'
        AND UnRead = '1'");
    list($NewNotifications) = $db->next_record();
    /* if ($NewNotifications && !check_perms('site_torrents_notify')) {
        $db->query("DELETE FROM users_notify_torrents WHERE UserID='$user[ID]'");
        $db->query("DELETE FROM users_notify_filters WHERE UserID='$user[ID]'");
    } */
    $cache->cache_value('notifications_new_' . $user['ID'], $NewNotifications, 0);
  }
}

// News
$MyNews = $user['LastReadNews'];
$CurrentNews = $cache->get_value('news_latest_id');
if ($CurrentNews === false) {
  $db->query("
    SELECT ID
    FROM news
    ORDER BY Time DESC
    LIMIT 1");
  if ($db->record_count() === 1) {
    list($CurrentNews) = $db->next_record();
  } else {
    $CurrentNews = -1;
  }
  $cache->cache_value('news_latest_id', $CurrentNews, 0);
}

// Blog
$MyBlog = $user['LastReadBlog'];
$CurrentBlog = $cache->get_value('blog_latest_id');
if ($CurrentBlog === false) {
  $db->query("
    SELECT ID
    FROM blog
    WHERE Important = 1
    ORDER BY Time DESC
    LIMIT 1");
  if ($db->record_count() === 1) {
    list($CurrentBlog) = $db->next_record();
  } else {
    $CurrentBlog = -1;
  }
  $cache->cache_value('blog_latest_id', $CurrentBlog, 0);
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

?>
