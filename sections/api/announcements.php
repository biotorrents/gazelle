<?php


$app = \Gazelle\App::go();

if (!$News = $app->cache->get('news')) {
    $app->dbOld->query("
    SELECT
      ID,
      Title,
      Body,
      Time
    FROM news
    ORDER BY Time DESC
    LIMIT 5");
    $News = $app->dbOld->to_array(false, MYSQLI_NUM, false);
    $app->cache->set('news', $News, 3600 * 24 * 30);
    $app->cache->set('news_latest_id', $News[0][0], 0);
}

if ($app->user->extra['LastReadNews'] != $News[0][0]) {
    /*
    $app->cacheOld->begin_transaction("user_info_heavy_$UserID");
    $app->cacheOld->update_row(false, array('LastReadNews' => $News[0][0]));
    $app->cacheOld->commit_transaction(0);
    */

    $app->dbOld->query("
    UPDATE users_info
    SET LastReadNews = '".$News[0][0]."'
    WHERE UserID = $UserID");
    $app->user->extra['LastReadNews'] = $News[0][0];
}

if (($Blog = $app->cache->get('blog')) === false) {
    $app->dbOld->query("
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
    $Blog = $app->dbOld->to_array();
    $app->cache->set('blog', $Blog, 1209600);
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
