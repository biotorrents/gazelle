<?php

if (!$News = $cache->get_value('news')) {
    $db->query("
    SELECT
      ID,
      Title,
      Body,
      Time
    FROM news
    ORDER BY Time DESC
    LIMIT 5");
    $News = $db->to_array(false, MYSQLI_NUM, false);
    $cache->cache_value('news', $News, 3600 * 24 * 30);
    $cache->cache_value('news_latest_id', $News[0][0], 0);
}

if ($user['LastReadNews'] != $News[0][0]) {
    $cache->begin_transaction("user_info_heavy_$UserID");
    $cache->update_row(false, array('LastReadNews' => $News[0][0]));
    $cache->commit_transaction(0);
    $db->query("
    UPDATE users_info
    SET LastReadNews = '".$News[0][0]."'
    WHERE UserID = $UserID");
    $user['LastReadNews'] = $News[0][0];
}

if (($Blog = $cache->get_value('blog')) === false) {
    $db->query("
    SELECT
      b.ID,
      um.Username,
      b.UserID,
      b.Title,
      b.Body,
      b.Time,
      b.ThreadID
    FROM blog AS b
      LEFT JOIN users_main AS um ON b.UserID = um.ID
    ORDER BY Time DESC
    LIMIT 20");
    $Blog = $db->to_array();
    $cache->cache_value('blog', $Blog, 1209600);
}
$JsonBlog = [];
for ($i = 0; $i < 5; $i++) {
    list($BlogID, $Author, $AuthorID, $Title, $Body, $BlogTime, $ThreadID) = $Blog[$i];
    $JsonBlog[] = array(
    'blogId' => (int)$BlogID,
    'author' => $Author,
    'title' => $Title,
    'bbBody' => $Body,
    'body' => Text::parse($Body),
    'blogTime' => $BlogTime,
    'threadId' => (int)$ThreadID
  );
}

$JsonAnnouncements = [];
$Count = 0;
foreach ($News as $NewsItem) {
    list($NewsID, $Title, $Body, $NewsTime) = $NewsItem;
    if (strtotime($NewsTime) > time()) {
        continue;
    }

    $JsonAnnouncements[] = array(
    'newsId' => (int)$NewsID,
    'title' => $Title,
    'bbBody' => $Body,
    'body' => Text::parse($Body),
    'newsTime' => $NewsTime
  );

    if (++$Count > 4) {
        break;
    }
}

json_die("success", array(
  'announcements' => $JsonAnnouncements,
  'blogPosts' => $JsonBlog
));
