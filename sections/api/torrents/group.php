<?php

#declare(strict_types=1);

$ENV = ENV::go();

# Either id or hash
$GroupID = (int) $_GET['id'];
$TorrentHash = (string) $_GET['hash'];

# Error if both supplied
if ($GroupID && $TorrentHash) {
    json_die('failure', 'bad parameters');
}

# Get id from hash
if ($TorrentHash) {
    if (!TorrentFunctions::is_valid_torrenthash($TorrentHash)) {
        json_die('failure', 'bad hash parameter');
    } else {
        $GroupID = (int) TorrentFunctions::torrenthash_to_groupid($TorrentHash);
        if (!$GroupID) {
            json_die('failure', 'bad hash parameter');
        }
    }
}

# Error if bad id
if ($GroupID <= 0) {
    json_die('failure', 'bad id parameter');
}

$TorrentCache = TorrentFunctions::get_group_info($GroupID, true, 0, true, true);
if (!$TorrentCache) {
    json_die('failure', 'bad id parameter');
}

# Get torrent details (group, torrents, artists)
list($TorrentDetails, $TorrentList) = $TorrentCache;
$Artists = Artists::get_artist($GroupID);

# Get category name if possible
if ($TorrentDetails['category_id'] === 0) {
    $CategoryName = 'Unknown';
} else {
    $CategoryName = $Categories[$TorrentDetails['category_id'] - 1];
}

# Get tag list (name and id)
$TagIDs = explode('|', $TorrentDetails["GROUP_CONCAT(DISTINCT tags.`ID` SEPARATOR '|')"]);
$TagNames= explode('|', $TorrentDetails["GROUP_CONCAT(DISTINCT tags.`Name` SEPARATOR '|')"]);

$TagList = [];
foreach ($TagIDs as $Key => $ID) {
    array_push(
        $TagList,
        [
            'id'   => $ID,
            'name' => $TagNames[$Key],
        ]
    );
}

# Get citation list (doi and id)
# todo: Update DB schema
$Citations = [];
foreach ($TorrentDetails['Screenshots'] as $Citation) {
    array_push(
        $Citations,
        [
            'id'        => $Citation['ID'],
            'doi'       => $Citation['URI'],
           #'timestamp' => $Citation['Time'],
        ]
    );
}

# Torrent group response
# todo: Add seeding, leeching, snatched
$JsonTorrentDetails = [
    'id'            => (int) $TorrentDetails['id'],
    'identifier'    => $TorrentDetails['identifier'],

    'categoryId'   => (int) $TorrentDetails['category_id'],
    'categoryName' => $CategoryName,

    'title'         => $TorrentDetails['title'],
    'subject'       => $TorrentDetails['subject'],
    'object'        => $TorrentDetails['object'],

    'authors'       => $Artists,
    'year'          => (int) $TorrentDetails['year'],
    'workgroup'     => $TorrentDetails['workgroup'],
    'location'      => $TorrentDetails['location'],

    'citations'     => $Citations,
    'mirrors'       => ($TorrentDetails['Mirrors']) ?: false,

    'description'   => $TorrentDetails['description'],
   #'description'   => Text::parse($TorrentDetails['description']),
    'picture'       => $TorrentDetails['picture'],
    'tagList'      => $TagList,

    'bookmarked'    => Bookmarks::has_bookmarked('torrent', $GroupID),
    'timestamp'     => $TorrentDetails['timestamp'],
];

# Torrents in group
$JsonTorrentList = [];
foreach ($TorrentList as $Torrent) {
    # Convert file list back to the old format
    $FileList = explode("\n", $Torrent['FileList']);

    foreach ($FileList as &$File) {
        $File = Torrents::filelist_old_format($File);
    }

    # todo: Make a nested object
    # todo: Limit to 100 files
    unset($File);
    $FileList = implode('|||', $FileList);
    $Userinfo = User::user_info($Torrent['UserID']);

    $Reports = Torrents::get_reports($Torrent['ID']);
    $Torrent['Reported'] = count($Reports) > 0;

    # Torrent details response
    # todo: Update DB schema
    $JsonTorrentList[] = [
        'id'           => (int) $Torrent['ID'],
        'infoHash'    => $Torrent['InfoHash'],
        'description'  => $Torrent['Description'],

        'platform'     => $Torrent['Media'],
        'format'       => $Torrent['Container'],
        'scope'        => $Torrent['Resolution'],
        'annotated'    => (bool) $Torrent['Censored'],
        'license'      => $Torrent['Codec'],

        'size'         => (int) $Torrent['Size'],
        'archive'      => $Torrent['Archive'],
        'fileCount'   => (int) $Torrent['FileCount'],
        'filePath'    => $Torrent['FilePath'],
        'fileList'    => $FileList,

        'seeders'      => (int) $Torrent['Seeders'],
        'leechers'     => (int) $Torrent['Leechers'],
        'snatched'     => (int) $Torrent['Snatched'],
        'freeTorrent' => ($Torrent['FreeTorrent'] === 1),

        'reported'     => (bool) $Torrent['Reported'],
        'time'         => $Torrent['Time'],

        'userId'      => (int) ($Torrent['Anonymous'] ? 0 : $Torrent['UserID']),
        'username'     => ($Torrent['Anonymous'] ? 'Anonymous' : $Userinfo['Username']),
    ];
}

# Print response
json_die(
    'success',
    [
        'group' => $JsonTorrentDetails,
        'torrents' => $JsonTorrentList,
    ]
);
