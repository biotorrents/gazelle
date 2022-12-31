<?php

#declare(strict_types=1);


$TorrentID = (int) $_GET['id'];
$TorrentHash = (string) $_GET['hash'];

if ($TorrentID && $TorrentHash) {
    json_die('failure', 'bad parameters');
}

if ($TorrentHash) {
    if (!TorrentFunctions::is_valid_torrenthash($TorrentHash)) {
        json_die('failure', 'bad hash parameter');
    } else {
        $TorrentID = (int) TorrentFunctions::torrenthash_to_torrentid($TorrentHash);
        if (!$TorrentID) {
            json_die('failure', 'bad hash parameter');
        }
    }
}

if ($TorrentID <= 0) {
    json_die('failure', 'bad id parameter');
}

$TorrentCache = TorrentFunctions::get_torrent_info($TorrentID, true, 0, true, true);
if (!$TorrentCache) {
    json_die('failure', 'bad id parameter');
}

list($TorrentDetails, $TorrentList) = $TorrentCache;
if (!isset($TorrentList[$TorrentID])) {
    json_die('failure', 'bad id parameter');
}

$GroupID = $TorrentDetails['ID'];
$Artists = Artists::get_artist($GroupID);

if ($TorrentDetails['category_id'] === 0) {
    $CategoryName = 'Unknown';
} else {
    $CategoryName = $Categories[$TorrentDetails['category_id'] - 1];
}

$TagList = explode('|', $TorrentDetails['GROUP_CONCAT(DISTINCT tags.Name SEPARATOR \'|\')']);

$JsonTorrentDetails = [
  'description'  => Text::parse($TorrentDetails['description']),
  'picture'      => $TorrentDetails['picture'],
  'id'           => (int) $TorrentDetails['id'],
  'title'         => $TorrentDetails['title'],
  'subject'     => $TorrentDetails['subject'],
  'object'       => $TorrentDetails['object'],
  'authors'      => $Artists,
  'year'         => (int) $TorrentDetails['published'],
  'identifier'    => $TorrentDetails['identifier'],
  'categoryId'   => (int) $TorrentDetails['category_id'],
  'icategoryName' => $CategoryName,
  'timestamp'         => $TorrentDetails['timestamp'],
  'bookmarked' => Bookmarks::has_bookmarked('torrent', $GroupID),
  'tagList'         => $TagList
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
$Userinfo = User::user_info($Torrent['UserID']);

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
