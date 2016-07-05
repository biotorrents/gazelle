<?
require(SERVER_ROOT.'/sections/torrents/functions.php');

// What do these two variables even do?
$GroupAllowed = array('WikiBody', 'WikiImage', 'ID', 'Name', 'Year', 'CatalogueNumber', 'ReleaseType', 'CategoryID', 'Time');
$TorrentAllowed = array('ID', 'Media', 'Format', 'Encoding', 'FileCount', 'Size', 'Seeders', 'Leechers', 'Snatched', 'FreeTorrent', 'Time', 'Description', 'FileList', 'FilePath', 'UserID', 'Username');

$TorrentID = (int)$_GET['id'];
$TorrentHash = (string)$_GET['hash'];

if ($TorrentID && $TorrentHash) {
  json_die("failure", "bad parameters");
}

if ($TorrentHash) {
  if (!is_valid_torrenthash($TorrentHash)) {
    json_die("failure", "bad hash parameter");
  } else {
    $TorrentID = (int)torrenthash_to_torrentid($TorrentHash);
    if (!$TorrentID) {
      json_die("failure", "bad hash parameter");
    }
  }
}

if ($TorrentID <= 0) {
  json_die("failure", "bad id parameter");
}

$TorrentCache = get_torrent_info($TorrentID, true, 0, true, true);

if (!$TorrentCache) {
  json_die("failure", "bad id parameter");
}

list($TorrentDetails, $TorrentList) = $TorrentCache;

if (!isset($TorrentList[$TorrentID])) {
  json_die("failure", "bad id parameter");
}

$GroupID = $TorrentDetails['ID'];

$Artists = pullmediainfo(Artists::get_artist($GroupID));

if ($TorrentDetails['CategoryID'] == 0) {
  $CategoryName = "Unknown";
} else {
  $CategoryName = $Categories[$TorrentDetails['CategoryID'] - 1];
}

$TagList = explode('|', $TorrentDetails['GROUP_CONCAT(DISTINCT tags.Name SEPARATOR \'|\')']);

$JsonTorrentDetails = array(
  'wikiBody'        => Text::full_format($TorrentDetails['WikiBody']),
  'wikiImage'       => $TorrentDetails['WikiImage'],
  'id'              => (int)$TorrentDetails['ID'],
  'name'            => $TorrentDetails['Name'],
  'namejp'          => $TorrentDetails['NameJP'],
  'artists'         => $Artists,
  'year'            => (int)$TorrentDetails['Year'],
  'catalogueNumber' => $TorrentDetails['CatalogueNumber'],
  'pages'           => (int)$TorrentDetails['Pages'],
  'categoryId'      => (int)$TorrentDetails['CategoryID'],
  'categoryName'    => $CategoryName,
  'dlsiteId'        => $TorrentDetails['DLSiteID'],
  'time'            => $TorrentDetails['Time'],
  'isBookmarked'    => Bookmarks::has_bookmarked('torrent', $GroupID),
  'tags'            => $TagList
);

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
$JsonTorrentList[] = array(
  'id'          => (int)$Torrent['ID'],
  'infoHash'    => $Torrent['InfoHash'],
  'media'       => $Torrent['Media'],
  'container'   => $Torrent['Container'],
  'codec'       => $Torrent['Codec'],
  'resolution'  => $Torrent['Resolution'],
  'audioFormat' => $Torrent['AudioFormat'],
  'subbing'     => $Torrent['Subbing'],
  'subber'      => $Torrent['Subber'],
  'language'    => $Torrent['Language'],
  'censored'    => (bool)$Torrent['Censored'],
  'archive'     => $Torrent['Archive'],
  'fileCount'   => (int)$Torrent['FileCount'],
  'size'        => (int)$Torrent['Size'],
  'seeders'     => (int)$Torrent['Seeders'],
  'leechers'    => (int)$Torrent['Leechers'],
  'snatched'    => (int)$Torrent['Snatched'],
  'freeTorrent' => ($Torrent['FreeTorrent'] == 1),
  'reported'    => (bool)$Torrent['Reported'],
  'time'        => $Torrent['Time'],
  'description' => $Torrent['Description'],
  'mediaInfo'   => $Torrent['MediaInfo'],
  'fileList'    => $FileList,
  'filePath'    => $Torrent['FilePath'],
  'userId'      => (int)$Torrent['UserID'],
  'username'    => $Userinfo['Username']
);

json_die("success", array('group' => $JsonTorrentDetails, 'torrent' => array_pop($JsonTorrentList)));
