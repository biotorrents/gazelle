<?php
/*
This page is something of a hack so those
easily scared off by funky solutions, don't
touch it! :P

There is a central problem to this page, it's
impossible to order before grouping in SQL, and
it's slow to run sub queries, so we had to get
creative for this one.

The solution I settled on abuses the way
$db->to_array() works. What we've done, is
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
  !isset($_REQUEST['collageid'])
  || !isset($_REQUEST['preference'])
  || !is_number($_REQUEST['preference'])
  || !is_number($_REQUEST['collageid'])
  || $_REQUEST['preference'] > 2
  || count($_REQUEST['list']) === 0
) {
    error(0);
}

if (!check_perms('zip_downloader')) {
    error(403);
}

$Preferences = array('RemasterTitle DESC', 'Seeders ASC', 'Size ASC');

$CollageID = $_REQUEST['collageid'];
$Preference = $Preferences[$_REQUEST['preference']];

$db->query("
  SELECT Name
  FROM collages
  WHERE ID = '$CollageID'");
list($CollageName) = $db->next_record(MYSQLI_NUM, false);

$SQL = "
SELECT
  t.`GroupID`,
  t.`ID` AS `TorrentID`,
  t.`Media`,
  t.`Format`,
  t.`Encoding`,
  IF(
    t.`RemasterYear` = 0,
    tg.`year`,
    t.`RemasterYear`
  ) AS `Year`,
  tg.`title`,
  t.`Size`
FROM
  `torrents` AS t
INNER JOIN `collages_torrents` AS c
ON
  t.`GroupID` = c.`GroupID` AND c.`CollageID` = '$CollageID'
INNER JOIN `torrents_group` AS tg
ON
  tg.`id` = t.`GroupID` AND tg.`category_id` = '1'
ORDER BY
  t.`GroupID` ASC,
  `Rank` DESC,
  t.$Preference
";

$DownloadsQ = $db->query($SQL);
$Collector = new TorrentsDL($DownloadsQ, $CollageName);

while (list($Downloads, $GroupIDs) = $Collector->get_downloads('GroupID')) {
    $Artists = Artists::get_artists($GroupIDs);
    $TorrentIDs = array_keys($GroupIDs);
    foreach ($TorrentIDs as $TorrentID) {
        file_get_contents(torrentStore.'/'.$TorrentID.'.torrent');
        $GroupID = $GroupIDs[$TorrentID];
        $Download =& $Downloads[$GroupID];
        $Download['Artist'] = Artists::display_artists($Artists[$Download['GroupID']], false, true, false);
        if ($Download['Rank'] == 100) {
            $Collector->skip_file($Download);
            continue;
        }
        $Collector->add_file($TorrentFile, $Download);
        unset($Download);
    }
}
$Collector->finalize();
$Settings = array(implode(':', $_REQUEST['list']), $_REQUEST['preference']);
