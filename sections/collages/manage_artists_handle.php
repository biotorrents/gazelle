<?php

#declare(strict_types=1);

/*
$app = \Gazelle\App::go();

authorize();

$CollageID = $_POST['collageid'];
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

$ArtistID = $_POST['artistid'];
if (!is_numeric($ArtistID)) {
    error(404);
}

if ($_POST['submit'] === 'Remove') {
    $app->dbOld->query("
    DELETE FROM collages_artists
    WHERE CollageID = '$CollageID'
      AND ArtistID = '$ArtistID'");
    $Rows = $app->dbOld->affected_rows();
    $app->dbOld->query("
    UPDATE collages
    SET NumTorrents = NumTorrents - $Rows
    WHERE ID = '$CollageID'");
    $app->cache->delete("artists_collages_$ArtistID");
    $app->cache->delete("artists_collages_personal_$ArtistID");
} elseif (isset($_POST['drag_drop_collage_sort_order'])) {
    @parse_str($_POST['drag_drop_collage_sort_order'], $Series);
    $Series = @array_shift($Series);
    if (is_array($Series)) {
        $SQL = [];
        foreach ($Series as $Sort => $ArtistID) {
            if (is_numeric($Sort) && is_numeric($ArtistID)) {
                $Sort = ($Sort + 1) * 10;
                $SQL[] = sprintf('(%d, %d, %d)', $ArtistID, $Sort, $CollageID);
            }
        }

        $SQL = '
      INSERT INTO collages_artists
        (ArtistID, Sort, CollageID)
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
    UPDATE collages_artists
    SET Sort = '$Sort'
    WHERE CollageID = '$CollageID'
      AND ArtistID = '$ArtistID'");
}

$app->cache->delete("collage_$CollageID");
Http::redirect("collages.php?action=manage_artists&collageid=$CollageID");
*/
