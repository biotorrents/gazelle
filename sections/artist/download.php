<?php

#declare(strict_types = 1);

$app = \Gazelle\App::go();

// todo: Freeleech in ratio hit calculations, in addition to a warning of whats freeleech in the Summary.txt
/*
This page is something of a hack so those
easily scared off by funky solutions, don't
touch it! :P

There is a central problem to this page, it's
impossible to order before grouping in SQL, and
it's slow to run sub queries, so we had to get
creative for this one.

The solution I settled on abuses the way
$app->dbOld->to_array() works. What we've done, is
backwards ordering. The results returned by the
query have the best one for each GroupID last,
and while to_array traverses the results, it
overwrites the keys and leaves us with only the
desired result. This does mean however, that
the SQL has to be done in a somewhat backwards
fashion.

Thats all you get for a disclaimer, just
remember, this page isn't for the faint of
heart. -A9
*/

if (
    !isset($_REQUEST['artistid'])
    || !isset($_REQUEST['preference'])
    || !is_numeric($_REQUEST['preference'])
    || !is_numeric($_REQUEST['artistid'])
    || $_REQUEST['preference'] > 2
    || count($_REQUEST['list']) === 0
) {
    error(0);
}

if (!check_perms('zip_downloader')) {
    error(403);
}

$Preferences = array('RemasterTitle DESC', 'Seeders ASC', 'Size ASC');

$ArtistID = $_REQUEST['artistid'];
$Preference = $Preferences[$_REQUEST['preference']];

$app->dbOld->query("
  SELECT Name
  FROM artists_group
  WHERE ArtistID = '$ArtistID'");
list($ArtistName) = $app->dbOld->next_record(MYSQLI_NUM, false);

$app->dbOld->query("
  SELECT GroupID, Importance
  FROM torrents_artists
  WHERE ArtistID = '$ArtistID'");
if (!$app->dbOld->has_results()) {
    error(404);
}
$Releases = $app->dbOld->to_array('GroupID', MYSQLI_ASSOC, false);
$GroupIDs = array_keys($Releases);

$SQL = "
SELECT
  t.GroupID,
  t.ID AS TorrentID,
  t.Media,
  t.Format,
  t.Encoding,
  tg.ReleaseType,
  IF(t.RemasterYear = 0, tg.Year, t.RemasterYear) AS Year,
  tg.Name,
  t.Size
FROM torrents AS t
  JOIN torrents_group AS tg ON tg.ID = t.GroupID AND tg.CategoryID = '1' AND tg.ID IN (".implode(',', $GroupIDs).")
ORDER BY t.GroupID ASC, Rank DESC, t.$Preference
";

$DownloadsQ = $app->dbOld->query($SQL);
$Collector = new TorrentsDL($DownloadsQ, $ArtistName);
while (list($Downloads, $GroupIDs) = $Collector->get_downloads('GroupID')) {
    $Artists = Artists::get_artists($GroupIDs);
    $TorrentIDs = array_keys($GroupIDs);
    foreach ($TorrentIDs as $TorrentID) {
        $TorrentFile = file_get_contents($app->env->torrentStore.'/'.$TorrentID.'.torrent');
        $GroupID = $GroupIDs[$TorrentID];
        $Download =& $Downloads[$GroupID];
        $Download['Artist'] = Artists::display_artists($Artists[$Download['GroupID']], false, true, false);
        if ($Download['Rank'] == 100) {
            $Collector->skip_file($Download);
            continue;
        }
        if ($Releases[$GroupID]['Importance'] == 1) {
            $ReleaseTypeName = $ReleaseTypes[$Download['ReleaseType']];
        } elseif ($Releases[$GroupID]['Importance'] == 2) {
            $ReleaseTypeName = 'Guest Appearance';
        } elseif ($Releases[$GroupID]['Importance'] == 3) {
            $ReleaseTypeName = 'Remixed By';
        }
        $Collector->add_file($TorrentFile, $Download, $ReleaseTypeName);
        unset($Download);
    }
}
$Collector->finalize();
$Settings = array(implode(':', $_REQUEST['list']), $_REQUEST['preference']);
