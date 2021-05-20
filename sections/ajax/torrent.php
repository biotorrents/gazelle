<?php
#declare(strict_types=1);

require_once SERVER_ROOT.'/sections/torrents/functions.php';

$TorrentID = (int) $_GET['id'];
$TorrentHash = (string) $_GET['hash'];

if ($TorrentID && $TorrentHash) {
    json_die('failure', 'bad parameters');
}

if ($TorrentHash) {
    if (!is_valid_torrenthash($TorrentHash)) {
        json_die('failure', 'bad hash parameter');
    } else {
        $TorrentID = (int) torrenthash_to_torrentid($TorrentHash);
        if (!$TorrentID) {
            json_die('failure', 'bad hash parameter');
        }
    }
}

if ($TorrentID <= 0) {
    json_die('failure', 'bad id parameter');
}

$TorrentCache = get_torrent_info($TorrentID, true, 0, true, true);
if (!$TorrentCache) {
    json_die('failure', 'bad id parameter');
}

list($TorrentDetails, $TorrentList) = $TorrentCache;
if (!isset($TorrentList[$TorrentID])) {
    json_die('failure', 'bad id parameter');
}

$GroupID = $TorrentDetails['ID'];
$Artists = Artists::get_artist($GroupID);

if ($TorrentDetails['CategoryID'] === 0) {
    $CategoryName = 'Unknown';
} else {
    $CategoryName = $Categories[$TorrentDetails['CategoryID'] - 1];
}

$TagList = explode('|', $TorrentDetails['GROUP_CONCAT(DISTINCT tags.Name SEPARATOR \'|\')']);

$JsonTorrentDetails = [
  'description'  => Text::full_format($TorrentDetails['WikiBody']),
  'picture'      => $TorrentDetails['WikiImage'],
  'id'           => (int) $TorrentDetails['ID'],
  'name'         => $TorrentDetails['Name'],
  'organism'     => $TorrentDetails['Title2'],
  'strain'       => $TorrentDetails['NameJP'],
  'authors'      => $Artists,
  'year'         => (int) $TorrentDetails['Year'],
  'accession'    => $TorrentDetails['CatalogueNumber'],
  'categoryId'   => (int) $TorrentDetails['CategoryID'],
  'categoryName' => $CategoryName,
  'time'         => $TorrentDetails['Time'],
  'isBookmarked' => Bookmarks::has_bookmarked('torrent', $GroupID),
  'tags'         => $TagList
];

$Torrent = $TorrentList[$TorrentID];

$Reports = Torrents::get_reports($TorrentID);
$Torrent['Reported'] = (count($Reports) > 0);

// Convert file list back to the old format
$FileList = explode("\n", $Torrent['FileList']);
foreach ($FileList as &$File) {
    $File = Torrents::filelist_old_format($File);
}

unset($File);
$FileList = implode('|||', $FileList);
$Userinfo = Users::user_info($Torrent['UserID']);

$JsonTorrentList[] = [
  'id'          => (int) $Torrent['ID'],
  'infoHash'    => $Torrent['InfoHash'],
  'platform'    => $Torrent['Media'],
  'format'      => $Torrent['Container'],
  'license'     => $Torrent['Codec'],
  'scope'       => $Torrent['Resolution'],
  'annotated'   => (bool) $Torrent['Censored'],
  'archive'     => $Torrent['Archive'],
  'fileCount'   => (int) $Torrent['FileCount'],
  'size'        => (int) $Torrent['Size'],
  'seeders'     => (int) $Torrent['Seeders'],
  'leechers'    => (int) $Torrent['Leechers'],
  'snatched'    => (int) $Torrent['Snatched'],
  'freeTorrent' => ($Torrent['FreeTorrent'] == 1),
  'reported'    => (bool) $Torrent['Reported'],
  'time'        => $Torrent['Time'],
  'description' => $Torrent['Description'],
  'fileList'    => $FileList,
  'filePath'    => $Torrent['FilePath'],
  'userId'      => (int) ($Torrent['Anonymous'] ? 0 : $Torrent['UserID']),
  'username'    => ($Torrent['Anonymous'] ? 'Anonymous' : $Userinfo['Username'])
];

json_die('success', ['group' => $JsonTorrentDetails, 'torrent' => array_pop($JsonTorrentList)]);
