<?
//------------------- Ratio requirements -----------------------//

// Clear old seed time history
$DB->query("
  DELETE FROM users_torrent_history
  WHERE Date < DATE('".sqltime()."' - INTERVAL 7 DAY) + 0");

// Store total seeded time for each user in a temp table
$DB->query("TRUNCATE TABLE users_torrent_history_temp");
$DB->query("
  INSERT INTO users_torrent_history_temp
    (UserID, SumTime)
  SELECT UserID, SUM(Time)
  FROM users_torrent_history
  GROUP BY UserID");

// Insert new row with <NumTorrents> = 0 with <Time> being number of seconds short of 72 hours.
// This is where we penalize torrents seeded for less than 72 hours
$DB->query("
  INSERT INTO users_torrent_history
    (UserID, NumTorrents, Date, Time)
  SELECT UserID, 0, UTC_DATE() + 0, 259200 - SumTime
  FROM users_torrent_history_temp
  WHERE SumTime < 259200");

// Set <Weight> to the time seeding <NumTorrents> torrents
$DB->query("
  UPDATE users_torrent_history
  SET Weight = NumTorrents * Time");

// Calculate average time spent seeding each of the currently active torrents.
// This rounds the results to the nearest integer because SeedingAvg is an int column.
$DB->query("TRUNCATE TABLE users_torrent_history_temp");
$DB->query("
  INSERT INTO users_torrent_history_temp
    (UserID, SeedingAvg)
  SELECT UserID, SUM(Weight) / SUM(Time)
  FROM users_torrent_history
  GROUP BY UserID");

// Remove dummy entry for torrents seeded less than 72 hours
$DB->query("
  DELETE FROM users_torrent_history
  WHERE NumTorrents = '0'");

// Get each user's amount of snatches of existing torrents
$DB->query("TRUNCATE TABLE users_torrent_history_snatch");
$DB->query("
  INSERT INTO users_torrent_history_snatch (UserID, NumSnatches)
  SELECT xs.uid, COUNT(DISTINCT xs.fid)
  FROM xbt_snatched AS xs
    JOIN torrents AS t ON t.ID = xs.fid
  GROUP BY xs.uid");

// Get the fraction of snatched torrents seeded for at least 72 hours this week
// Essentially take the total number of hours seeded this week and divide that by 72 hours * <NumSnatches>
$DB->query("
  UPDATE users_main AS um
    JOIN users_torrent_history_temp AS t ON t.UserID = um.ID
    JOIN users_torrent_history_snatch AS s ON s.UserID = um.ID
  SET um.RequiredRatioWork = (1 - (t.SeedingAvg / s.NumSnatches))
  WHERE s.NumSnatches > 0");


// TODO: change from PHP_INT_MAX to INF when we get prepared statements working (because apparently that works)
$DownloadBarrier = PHP_INT_MAX;

foreach (RATIO_REQUIREMENTS as $Requirement) {
  list($Download, $Ratio, $MinRatio) = $Requirement;

  $DB->query("
    UPDATE users_main
    SET RequiredRatio = RequiredRatioWork * $Ratio
    WHERE Downloaded >= $Download
      AND Downloaded < $DownloadBarrier");

  $DB->query("
    UPDATE users_main
    SET RequiredRatio = $MinRatio
    WHERE Downloaded >= $Download
      AND Downloaded < $DownloadBarrier
      AND RequiredRatio < $MinRatio");

  $DownloadBarrier = $Download;
}

?>
