<?php

#declare(strict_types=1);

$app = Gazelle\App::go();



$CollageID = $_POST['collageId'];
if (!is_numeric($CollageID)) {
    error(404);
}

$app->dbOld->query("
  SELECT UserID, CategoryID
  FROM collages
  WHERE ID = '$CollageID'");
list($UserID, $CategoryID) = $app->dbOld->next_record();
if ($CategoryID === '0' && $UserID != $app->user->core['id'] && !check_perms('site_collages_delete')) {
    error(403);
}


$GroupID = $_POST['groupid'];
if (!is_numeric($GroupID)) {
    error(404);
}

if ($_POST['submit'] === 'Remove') {
    $app->dbOld->query("
    DELETE FROM collages_torrents
    WHERE CollageID = '$CollageID'
      AND GroupID = '$GroupID'");
    $Rows = $app->dbOld->affected_rows();
    $app->dbOld->query("
    UPDATE collages
    SET NumTorrents = NumTorrents - $Rows
    WHERE ID = '$CollageID'");
    $app->cache->delete("torrents_details_$GroupID");
    $app->cache->delete("torrent_collages_$GroupID");
    $app->cache->delete("torrent_collages_personal_$GroupID");
} elseif (isset($_POST['drag_drop_collage_sort_order'])) {
    @parse_str($_POST['drag_drop_collage_sort_order'], $Series);
    $Series = @array_shift($Series);
    if (is_array($Series)) {
        $SQL = [];
        foreach ($Series as $Sort => $GroupID) {
            if (is_numeric($Sort) && is_numeric($GroupID)) {
                $Sort = ($Sort + 1) * 10;
                $SQL[] = sprintf('(%d, %d, %d)', $GroupID, $Sort, $CollageID);
            }
        }

        $SQL = '
      INSERT INTO collages_torrents
        (GroupID, Sort, CollageID)
      VALUES
        ' . implode(', ', $SQL) . '
      ON DUPLICATE KEY UPDATE
        Sort = VALUES (Sort)';

        $app->dbOld->query($SQL);
    }
} else {
    $Sort = $_POST['sort'];
    if (!is_numeric($Sort)) {
        error(404);
    }
    $app->dbOld->query("
    UPDATE collages_torrents
    SET Sort = '$Sort'
    WHERE CollageID = '$CollageID'
      AND GroupID = '$GroupID'");
}

$app->cache->delete("collage_$CollageID");
Gazelle\Http::redirect("collages.php?action=manage&collageId=$CollageID");
