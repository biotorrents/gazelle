<?php

$app = \Gazelle\App::go();

authorize();
$UserSubscriptions = Subscriptions::get_subscriptions();
if (!empty($UserSubscriptions)) {
    $app->dbOld->query("
    INSERT INTO forums_last_read_topics (UserID, TopicID, PostID)
      SELECT '{$app->userNew->core['id']}', ID, LastPostID
      FROM forums_topics
      WHERE ID IN (".implode(',', $UserSubscriptions).')
    ON DUPLICATE KEY UPDATE
      PostID = LastPostID');
}
$app->dbOld->query("
  INSERT INTO users_comments_last_read (UserID, Page, PageID, PostID)
  SELECT {$app->userNew->core['id']}, t.Page, t.PageID, t.LastPostID
  FROM (
    SELECT
      s.Page,
      s.PageID,
      IFNULL(c.ID, 0) AS LastPostID
    FROM users_subscriptions_comments AS s
      LEFT JOIN comments AS c ON c.Page = s.Page
        AND c.ID = (
            SELECT MAX(ID)
            FROM comments
            WHERE Page = s.Page
              AND PageID = s.PageID
            )
  ) AS t
  ON DUPLICATE KEY UPDATE
    PostID = LastPostID");
$app->cacheNew->delete('subscriptions_user_new_'.$app->userNew->core['id']);
Http::redirect("userhistory.php?action=subscriptions");
