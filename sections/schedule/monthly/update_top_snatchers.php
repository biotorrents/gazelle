<?
$DB->query('TRUNCATE TABLE top_snatchers;');
$DB->query("
  INSERT INTO top_snatchers (UserID)
  SELECT uid
  FROM xbt_snatched
  GROUP BY uid
  ORDER BY COUNT(uid) DESC
  LIMIT 100;");
?>
