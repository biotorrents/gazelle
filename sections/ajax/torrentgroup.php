<?php

require(SERVER_ROOT.'/sections/torrents/functions.php');

// Seriously what the hell do these do
$GroupAllowed = array('WikiBody', 'WikiImage', 'ID', 'Name', 'Year', 'RecordLabel', 'CatalogueNumber', 'ReleaseType', 'CategoryID', 'Time', 'VanityHouse');
$TorrentAllowed = array('ID', 'Media', 'Format', 'Encoding', 'Remastered', 'RemasterYear', 'RemasterTitle', 'RemasterRecordLabel', 'RemasterCatalogueNumber', 'Scene', 'HasLog', 'HasCue', 'LogScore', 'FileCount', 'Size', 'Seeders', 'Leechers', 'Snatched', 'FreeTorrent', 'Time', 'Description', 'FileList', 'FilePath', 'UserID', 'Username');

$GroupID = (int)$_GET['id'];
$TorrentHash = (string)$_GET['hash'];

if ($GroupID && $TorrentHash) {
	json_die("failure", "bad parameters");
}

if ($TorrentHash) {
	if (!is_valid_torrenthash($TorrentHash)) {
		json_die("failure", "bad hash parameter");
	} else {
		$GroupID = (int)torrenthash_to_groupid($TorrentHash);
		if (!$GroupID) {
			json_die("failure", "bad hash parameter");
		}
	}
}

if ($GroupID <= 0) {
	json_die("failure", "bad id parameter");
}

$TorrentCache = get_group_info($GroupID, true, 0, true, true);

if (!$TorrentCache) {
	json_die("failure", "bad id parameter");
}

list($TorrentDetails, $TorrentList) = $TorrentCache;

$Artiss = pullmediainfo(Artists::get_artist($GroupID));

if ($TorrentDetails['CategoryID'] == 0) {
	$CategoryName = 'Unknown';
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

$JsonTorrentList = array();
foreach ($TorrentList as $Torrent) {
	// Convert file list back to the old format
	$FileList = explode("\n", $Torrent['FileList']);
	foreach ($FileList as &$File) {
		$File = Torrents::filelist_old_format($File);
	}
	unset($File);
	$FileList = implode('|||', $FileList);
	$Userinfo = Users::user_info($Torrent['UserID']);
	$Reports = Torrents::get_reports($Torrent['ID']);
	$Torrent['Reported'] = count($Reports) > 0;
	$JsonTorrentList[] = array(
		'id'          => (int)$Torrent['ID'],
    'infoHash'    => $Torrent['InfoHash'],
		'media'       => $Torrent['Media'],
    'container'   => $Torrent['Container'],
    'codec'       => $Torrent['Codec'],
    'resolution'  => $Torrent['Resolution'],
    'audioFormat' => $Torrent['AudioFormat'],
    'subbing'     => $Torrent['Subbing'],
    'subber'      => $Torrent['Subber']
    'language'    => $Torrent['Language'],
    'censored'    => (bool)$Torrent['Censored'],
    'archive'     => $Torrent['Archive'],
		'fileCount'   => (int)$Torrent['FileCount'],
		'size'        => (int)$Torrent['Size'],
		'seeders'     => (int)$Torrent['Seeders'],
		'leechers'    => (int)$Torrent['Leechers'],
		'snatched'    => (int)$Torrent['Snatched'],
		'freeTorrent' => $Torrent['FreeTorrent'] == 1,
		'reported'    => $Torrent['Reported'],
		'time'        => $Torrent['Time'],
		'description' => $Torrent['Description'],
    'mediaInfo'   => $Torrent['MediaInfo'],
		'fileList'    => $FileList,
		'filePath'    => $Torrent['FilePath'],
		'userId'      => (int)$Torrent['UserID'],
		'username'    => $Userinfo['Username']
	);
}

json_die("success", array('group' => $JsonTorrentDetails, 'torrents' => $JsonTorrentList));
