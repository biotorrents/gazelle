<?
//------------------------ Update Bonus Points -------------------------//

$getUsers = $DB->query("
  SELECT um.ID,
         COUNT(DISTINCT x.fid) AS Torrents,
         SUM(t.Size) AS Size,
         SUM(xs.seedtime) AS Seedtime,
         SUM(t.Seeders) AS Seeders
  FROM users_main AS um
  LEFT JOIN users_info AS i on um.ID = i.UserID
  LEFT JOIN xbt_files_users AS x ON um.ID=x.uid
  LEFT JOIN torrents AS t ON t.ID=x.fid
  LEFT JOIN xbt_snatched AS xs ON x.uid=xs.uid AND x.fid=xs.fid
  WHERE
    um.Enabled = '1'
    AND i.DisableNips = '0'
    AND x.active = 1
    AND x.completed = 0
    AND x.Remaining = 0
  GROUP BY um.ID");
if ($DB->has_results()) {
  $QueryPart = '';
  while (list($UserID, $NumTorr, $TSize, $TTime, $TSeeds) = $DB->next_record()) {
    $Points = intval((0.5 + (0.55*($NumTorr * (sqrt(($TSize/$NumTorr)/1073741824) * pow(1.5,($TTime/$NumTorr)/(24*365))))) / (max(1, sqrt(($TSeeds/$NumTorr)+4)/3)))**0.95);
    $QueryPart .= "WHEN $UserID THEN BonusPoints+$Points ";
    $Cache->delete_value('user_info_heavy_'.$UserID);
  }

  $DB->query("
    UPDATE users_main
    SET BonusPoints = CASE ID "
    .$QueryPart.
    "ELSE BonusPoints END");
}
?>
